<?php
namespace App\Integrations\Connector;

use App\Integrations\Connector\Adapters\ProtocolAdapter;

/**
 * Logs in to a vendor for a JWT, caches it encrypted on the credential row, and logs out on request.
 * auth_config {"login_path","token_path","username","username_field","password_field","expires_path","ttl_s","logout_path"}
 * secret {"password": "..."}
 * Login bodies stay out of the call log, so the password and token never reach api_call_logs.
 */
class JwtSession
{
    private const EARLY_EXPIRY_S = 60;
    private const DEFAULT_TTL_S = 3600;
    private const LOCK_WAIT_S = 10;

    private ConnectorRepository $repo;
    private SecretStore $secrets;

    public function __construct(ConnectorRepository $repo, SecretStore $secrets)
    {
        $this->repo = $repo;
        $this->secrets = $secrets;
    }

    /**
     * ['token' => ?string, 'error' => ?string, 'detail' => string]. $rejected is a token the vendor just refused, never reused.
     */
    public function token(array $connector, array $credential, array $config, array $secret, ProtocolAdapter $adapter, ?string $rejected = null): array
    {
        $cached = $this->cachedToken($credential, $rejected);
        if ($cached !== null) {
            return ['token' => $cached, 'error' => null, 'detail' => ''];
        }

        // Parallel requests share one login: a second login can end the first session on some vendors
        $lock = 'wms_jwt_' . $credential['id'];
        if (!$this->repo->acquireLock($lock, self::LOCK_WAIT_S)) {
            return $this->failed(ErrorCode::TIMEOUT, 'timed out waiting for the login lock');
        }

        try {
            $cached = $this->cachedToken($credential, $rejected);
            if ($cached !== null) {
                return ['token' => $cached, 'error' => null, 'detail' => ''];
            }

            return $this->login($connector, $credential, $config, $secret, $adapter);
        } finally {
            $this->repo->releaseLock($lock);
        }
    }

    /**
     * The saved token while it is still valid, else null. A token that cannot be decrypted counts as no token.
     */
    public function cachedToken(array $credential, ?string $rejected = null): ?string
    {
        $row = $this->repo->findToken((int)$credential['id']);
        if (!$row || empty($row['token_cipher']) || strtotime((string)$row['token_expires_at']) <= time() + self::EARLY_EXPIRY_S) {
            return null;
        }

        // The row's key_version belongs to secret, so the token carries its own
        [$version, $cipher] = array_pad(explode(':', $row['token_cipher'], 2), 2, '');
        $token = $this->secrets->decrypt($cipher, (int)$version)['token'] ?? null;

        return is_string($token) && $token !== '' && $token !== $rejected ? $token : null;
    }

    /**
     * Calls logout_path with the token. ['error' => ?string, 'detail' => string, 'status' => int]
     */
    public function sendLogout(array $connector, array $config, string $token, ProtocolAdapter $adapter): array
    {
        $sent = $this->send($connector, (string)$config['logout_path'], '{}', ['Authorization' => 'Bearer ' . $token], $adapter);
        if ($sent['error']) {
            return ['error' => $sent['error'], 'detail' => $sent['detail'], 'status' => 0];
        }

        $status = $sent['response']->status;
        $error = ErrorCode::fromHttpStatus($status);

        return ['error' => $error, 'detail' => $error ? 'logout returned status ' . $status : '', 'status' => $status];
    }

    private function login(array $connector, array $credential, array $config, array $secret, ProtocolAdapter $adapter): array
    {
        $loginPath = (string)($config['login_path'] ?? '');
        $tokenPath = (string)($config['token_path'] ?? '');
        $username = (string)($config['username'] ?? '');
        $password = (string)($secret['password'] ?? '');
        if ($loginPath === '' || $tokenPath === '' || $username === '' || $password === '') {
            return $this->failed(ErrorCode::CREDENTIALS_MISSING, 'jwt credential needs login_path, token_path, username and password');
        }

        $body = json_encode([
            (string)($config['username_field'] ?? 'username') => $username,
            (string)($config['password_field'] ?? 'password') => $password
        ]);
        $sent = $this->send($connector, $loginPath, $body, [], $adapter);
        if ($sent['error']) {
            return $this->failed($sent['error'], $sent['detail']);
        }

        $status = $sent['response']->status;
        $error = ErrorCode::fromHttpStatus($status);
        if ($error) {
            return $this->failed($error, 'login returned status ' . $status);
        }

        $decoded = json_decode($sent['response']->body, true);
        $token = MappingEngine::extract($decoded, ['extract' => $tokenPath]);
        if (!is_string($token) || $token === '') {
            return $this->failed(ErrorCode::AUTH_FAILED, 'login reply has no token at token_path');
        }

        $encrypted = $this->secrets->encrypt(['token' => $token]);
        $saved = $this->repo->saveToken((int)$credential['id'], $encrypted['version'] . ':' . $encrypted['cipher'], date('Y-m-d H:i:s', $this->expiresAt($decoded, $config, $token)));
        if (!$saved) {
            error_log('JwtSession - unable to cache token for credential ' . $credential['id']);
        }

        return ['token' => $token, 'error' => null, 'detail' => ''];
    }

    /**
     * ['response' => ?RawResponse, 'error' => ?string, 'detail' => string]
     */
    private function send(array $connector, string $path, string $body, array $headers, ProtocolAdapter $adapter): array
    {
        $options = json_decode((string)$connector['options'], true);
        $defaultHeaders = json_decode((string)$connector['default_headers'], true);

        $request = new PreparedRequest();
        $request->method = 'POST';
        $request->url = rtrim($connector['base_url'], '/') . '/' . ltrim($path, '/');
        $request->body = $body;
        $request->timeoutMs = max(1000, (int)$connector['timeout_ms']);
        $request->connectTimeoutMs = min(10000, max(1000, (int)$connector['connect_timeout_ms']));
        $request->headers = array_merge(
            ['Accept' => 'application/json'],
            is_array($defaultHeaders) ? $defaultHeaders : [],
            ['Content-Type' => 'application/json'],
            $headers
        );

        $guard = UrlGuard::check($request->url, $connector['base_url'], (bool)$connector['allow_http'], !empty($options['allow_private']));
        if ($guard['error']) {
            return ['response' => null, 'error' => $guard['error'], 'detail' => 'blocked ' . $request->url];
        }
        $request->resolve = $guard['resolve'];

        $response = $adapter->send($request);
        if ($response->errorCode) {
            return ['response' => null, 'error' => $response->errorCode, 'detail' => (string)$response->errorDetail];
        }

        return ['response' => $response, 'error' => null, 'detail' => ''];
    }

    /**
     * expires_path value, else the JWT's own exp, else ttl_s. Only sets the cache time; the vendor still checks the token.
     */
    private function expiresAt($decoded, array $config, string $token): int
    {
        if (!empty($config['expires_path'])) {
            $at = $this->parseExpiry(MappingEngine::extract($decoded, ['extract' => (string)$config['expires_path']]));
            if ($at !== null) {
                return $at;
            }
        }

        $parts = explode('.', $token);
        $claims = count($parts) === 3 ? json_decode((string)base64_decode(strtr($parts[1], '-_', '+/')), true) : null;
        if (is_numeric($claims['exp'] ?? null) && $claims['exp'] > 0) {
            return (int)$claims['exp'];
        }

        $ttl = (int)($config['ttl_s'] ?? 0);

        return time() + ($ttl > 0 ? $ttl : self::DEFAULT_TTL_S);
    }

    /**
     * A date string, a Unix time in seconds or milliseconds, or seconds from now. Null when unreadable.
     */
    private function parseExpiry($value): ?int
    {
        if (is_numeric($value)) {
            $number = (int)$value;
            if ($number <= 0) {
                return null;
            }
            if ($number < 1000000000) {
                return time() + $number;
            }

            return $number > 100000000000 ? (int)($number / 1000) : $number;
        }

        // shortcut: a date with no timezone is read as the server's Asia/Kuala_Lumpur, add an auth_config timezone when a vendor needs it
        $at = is_string($value) && $value !== '' ? strtotime($value) : false;

        return $at === false ? null : $at;
    }

    private function failed(string $error, string $detail): array
    {
        return ['token' => null, 'error' => $error, 'detail' => $detail];
    }
}
