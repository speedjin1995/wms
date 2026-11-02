<?php
namespace App\Integrations\Connector;

use App\Integrations\Connector\Adapters\AdapterRegistry;
use App\Integrations\Connector\Auth\AuthRegistry;

/**
 * The one entry point for calling an outside API defined in api_connectors.
 * Never throws: every failure comes back as a NormalizedResult with an ErrorCode.
 *
 *   $client = new ConnectorClient(new ConnectorRepository($db, $company, $user, $role));
 *   $result = $client->execute('weather', 'current', ['city' => 'Ipoh']);
 */
class ConnectorClient
{
    private ConnectorRepository $repo;
    private SecretStore $secrets;
    private CallLogger $logger;
    private JwtSession $jwt;

    public function __construct(ConnectorRepository $repo, ?SecretStore $secrets = null, ?CallLogger $logger = null)
    {
        $this->repo = $repo;
        $this->secrets = $secrets ?? new SecretStore();
        $this->logger = $logger ?? new CallLogger($repo);
        $this->jwt = new JwtSession($repo, $this->secrets);
    }

    /**
     * $ctx['exposed_only']: tenant-facing calls may only run operations flagged exposed
     */
    public function execute(string $connectorCode, string $operationCode, array $payload = [], array $ctx = []): NormalizedResult
    {
        $started = microtime(true);
        $redactor = new Redactor();
        $call = ['connector' => null, 'operation' => null, 'attempts' => 0, 'req_bytes' => 0, 'resp_bytes' => 0, 'detail' => ''];

        try {
            $result = $this->run($connectorCode, $operationCode, $payload, $ctx, $call, $redactor);
        } catch (\UnexpectedValueException $e) {
            // Thrown by MappingEngine and buildRequest for bad specs or missing path values
            $call['detail'] = $e->getMessage();
            $result = NormalizedResult::failure(ErrorCode::MAPPING_ERROR);
        } catch (\Throwable $e) {
            $call['detail'] = $e->getMessage();
            $result = NormalizedResult::failure(ErrorCode::INTERNAL_ERROR);
        }

        return $this->finish($connectorCode, $result, $call, $redactor, $started, !empty($ctx['preview']));
    }

    /**
     * Ends a jwt connector's session: calls logout_path if there is one and a token to end, and always forgets the cached token.
     * Logged only when a request was sent.
     */
    public function logout(string $connectorCode): NormalizedResult
    {
        $started = microtime(true);
        $redactor = new Redactor();
        $call = ['connector' => null, 'operation' => null, 'attempts' => 0, 'req_bytes' => 0, 'resp_bytes' => 0, 'detail' => ''];

        try {
            $result = $this->runLogout($connectorCode, $call, $redactor);
        } catch (\Throwable $e) {
            $call['detail'] = $e->getMessage();
            $result = NormalizedResult::failure(ErrorCode::INTERNAL_ERROR);
        }

        return $this->finish($connectorCode, $result, $call, $redactor, $started, false);
    }

    private function runLogout(string $connectorCode, array &$call, Redactor $redactor): NormalizedResult
    {
        $connector = $this->repo->findConnector($connectorCode);
        if (!$connector) {
            return NormalizedResult::failure(ErrorCode::CONFIG_NOT_FOUND);
        }

        $credential = $this->repo->findCredential((int)$connector['id']);
        if (!$credential) {
            return NormalizedResult::failure(ErrorCode::CREDENTIALS_MISSING);
        }
        if ($credential['auth_type'] !== 'jwt') {
            return NormalizedResult::failure(ErrorCode::AUTH_UNSUPPORTED);
        }

        $adapter = AdapterRegistry::for($connector['protocol']);
        if (!$adapter) {
            return NormalizedResult::failure(ErrorCode::PROTOCOL_UNSUPPORTED);
        }

        $config = $this->json($credential['auth_config']);
        $token = $this->jwt->cachedToken($credential);

        $result = new NormalizedResult();
        $result->ok = true;
        if ($token !== null && !empty($config['logout_path'])) {
            $redactor->addSecrets([$token]);
            $call['connector'] = $connector;
            $call['attempts'] = 1;
            $call['req_at'] = date('Y-m-d H:i:s');
            $sent = $this->jwt->sendLogout($connector, $config, $token, $adapter);
            $call['resp_at'] = date('Y-m-d H:i:s');

            if ($sent['error']) {
                $call['detail'] = $sent['detail'];
                $result = NormalizedResult::failure($sent['error'], '', $sent['status']);
            } else {
                $result->httpStatus = $sent['status'];
            }
        }

        // Cleared even when the vendor call failed: the next call logs in again
        $this->repo->clearToken((int)$credential['id']);

        return $result;
    }

    private function finish(string $connectorCode, NormalizedResult $result, array $call, Redactor $redactor, float $started, bool $preview): NormalizedResult
    {
        $result->callId = bin2hex(random_bytes(8));
        $result->attempts = $call['attempts'];
        $result->latencyMs = (int)round((microtime(true) - $started) * 1000);

        if ($call['connector'] && !$preview) {
            $this->logger->log($result, $call, $redactor);
        } elseif ($call['detail'] !== '') {
            error_log('ConnectorClient ' . $connectorCode . ' - ' . $redactor->text($call['detail']));
        }

        return $result;
    }

    private function run(string $connectorCode, string $operationCode, array $payload, array $ctx, array &$call, Redactor $redactor): NormalizedResult
    {
        $connector = $this->repo->findConnector($connectorCode);
        if (!$connector) {
            return NormalizedResult::failure(ErrorCode::CONFIG_NOT_FOUND);
        }
        $call['connector'] = $connector;

        $operation = $this->repo->findOperation((int)$connector['id'], $operationCode);
        if (!$operation) {
            return NormalizedResult::failure(ErrorCode::CONFIG_NOT_FOUND);
        }
        $call['operation'] = $operation;

        if (!empty($ctx['exposed_only']) && !(int)$operation['exposed']) {
            return NormalizedResult::failure(ErrorCode::NOT_EXPOSED);
        }

        $adapter = AdapterRegistry::for($connector['protocol']);
        if (!$adapter) {
            return NormalizedResult::failure(ErrorCode::PROTOCOL_UNSUPPORTED);
        }

        $credential = $this->repo->findCredential((int)$connector['id']);
        if (!$credential) {
            return NormalizedResult::failure(ErrorCode::CREDENTIALS_MISSING);
        }

        $auth = AuthRegistry::for($credential['auth_type']);
        if (!$auth) {
            return NormalizedResult::failure(ErrorCode::AUTH_UNSUPPORTED);
        }

        $secret = $this->secrets->decrypt($credential['secret'], $credential['key_version'] !== null ? (int)$credential['key_version'] : null);
        if ($secret === null) {
            $call['detail'] = 'unable to decrypt credential ' . $credential['id'];
            return NormalizedResult::failure(ErrorCode::CREDENTIALS_MISSING);
        }
        $redactor->addSecrets($secret);

        $mappingCtx = [
            'input' => $payload,
            'config' => $this->json($connector['options']),
            'credential' => $this->json($credential['auth_config'])
        ];
        $request = $this->buildRequest($connector, $operation, $payload, $mappingCtx);

        $guard = UrlGuard::check($request->url, $connector['base_url'], (bool)$connector['allow_http'], !empty($this->json($connector['options'])['allow_private']));
        if ($guard['error']) {
            $call['detail'] = 'blocked ' . $request->url;
            return NormalizedResult::failure($guard['error']);
        }
        $request->resolve = $guard['resolve'];

        $isJwt = $credential['auth_type'] === 'jwt';
        if ($isJwt) {
            // Preview sends nothing, so no login either
            $session = !empty($ctx['preview'])
                ? ['token' => 'preview-token', 'error' => null, 'detail' => '']
                : $this->jwt->token($connector, $credential, $this->json($credential['auth_config']), $secret, $adapter);
            if ($session['error']) {
                $call['detail'] = $session['detail'];
                return NormalizedResult::failure($session['error']);
            }
            $redactor->addSecrets([$session['token']]);
            $secret['token'] = $session['token'];
        }

        $plainHeaders = $request->headers;
        if (!$auth->apply($request, $this->json($credential['auth_config']), $secret)) {
            return NormalizedResult::failure(ErrorCode::CREDENTIALS_MISSING);
        }
        // Whatever auth put on the wire (e.g. the Basic header) is masked too
        $redactor->addSecrets(array_values(array_diff_assoc($request->headers, $plainHeaders)));
        $redactor->addSecrets(array_values($request->query));

        // Preview shows what would be sent, with secrets masked, and sends nothing
        if (!empty($ctx['preview'])) {
            $result = new NormalizedResult();
            $result->ok = true;
            $result->data = [
                'method' => $request->method,
                'url' => $redactor->text($request->fullUrl()),
                'headers' => $redactor->data($request->headers),
                'body' => $request->body === null ? null : $redactor->text($request->body)
            ];

            return $result;
        }

        $call['attempts'] = 1;
        $call['req_bytes'] = strlen((string)$request->body);
        $call['req_body'] = $request->body;
        $call['req_at'] = date('Y-m-d H:i:s');
        $response = $adapter->send($request);

        // The cached token may have been ended early by the vendor: log in again and resend once
        if ($isJwt && !$response->errorCode && $response->status === 401) {
            $session = $this->jwt->token($connector, $credential, $this->json($credential['auth_config']), $secret, $adapter, $secret['token']);
            if ($session['error']) {
                $call['detail'] = $session['detail'];
                return NormalizedResult::failure($session['error']);
            }
            $redactor->addSecrets([$session['token']]);
            $secret['token'] = $session['token'];
            $auth->apply($request, $this->json($credential['auth_config']), $secret);
            $call['attempts'] = 2;
            $response = $adapter->send($request);
        }
        $call['resp_at'] = date('Y-m-d H:i:s');
        $call['resp_bytes'] = strlen($response->body);
        $call['resp_body'] = $response->body;

        if ($response->errorCode) {
            $call['detail'] = $response->errorDetail;
            return NormalizedResult::failure($response->errorCode);
        }

        $result = new NormalizedResult();
        $result->httpStatus = $response->status;
        $decoded = $this->decodeBody($response->body);

        $errorCode = ErrorCode::fromHttpStatus($response->status);
        if ($errorCode) {
            $result->errorCode = $errorCode;
            $result->message = ErrorCode::message($errorCode, $response->status);
            // Upstream bodies can echo credentials back
            $result->data = $redactor->data($decoded);
            $result->message = $this->errorMessage($operation, $decoded, $redactor) ?? $result->message;
        } else {
            $result->ok = true;
            $result->data = $redactor->data($this->mapResponse($operation, $decoded));
        }

        return $result;
    }

    /**
     * Without request_spec the payload passes straight through: query string for GET / DELETE, JSON (or form) body otherwise.
     * With request_spec, the mapping picks path, query, headers and body.
     */
    private function buildRequest(array $connector, array $operation, array $payload, array $mappingCtx): PreparedRequest
    {
        $options = $this->json($operation['options']);
        $spec = $this->json($operation['request_spec']);
        $mapped = $spec ? MappingEngine::render($spec, $mappingCtx) : null;
        $isBodiless = in_array($operation['http_method'], ['GET', 'DELETE'], true);

        $request = new PreparedRequest();
        $request->method = $operation['http_method'];
        $request->url = rtrim($connector['base_url'], '/');
        $path = $mapped ? $this->fillPath($operation['path_template'], $mapped['path'] ?? []) : $operation['path_template'];
        if ($path !== '') {
            $request->url .= '/' . ltrim($path, '/');
        }
        $request->timeoutMs = max(1000, (int)($operation['timeout_ms'] ?: $connector['timeout_ms']));
        $request->connectTimeoutMs = min(10000, max(1000, (int)$connector['connect_timeout_ms']));
        $request->headers = array_merge(
            ['Accept' => 'application/json'],
            $this->json($connector['default_headers']),
            is_array($options['headers'] ?? null) ? $options['headers'] : [],
            array_map('strval', $mapped['headers'] ?? [])
        );

        $query = $mapped ? ($mapped['query'] ?? []) : ($isBodiless ? $payload : []);
        if (!empty($query)) {
            $request->url .= (strpos($request->url, '?') === false ? '?' : '&') . http_build_query($query);
        }

        $body = $mapped ? ($mapped['body'] ?? []) : $payload;
        if (!$isBodiless) {
            if (($options['body_format'] ?? 'json') === 'form') {
                $request->body = http_build_query($body);
                $request->headers['Content-Type'] = 'application/x-www-form-urlencoded';
            } else {
                $request->body = json_encode($body ?: new \stdClass());
                $request->headers['Content-Type'] = 'application/json';
            }
        }

        return $request;
    }

    private function fillPath(string $template, array $values): string
    {
        return preg_replace_callback('/\{([A-Za-z0-9_]+)\}/', function ($m) use ($values) {
            if (!array_key_exists($m[1], $values)) {
                throw new \UnexpectedValueException('Missing path value ' . $m[1]);
            }

            return rawurlencode((string)$values[$m[1]]);
        }, $template);
    }

    /**
     * Response mapped by response_spec on success. Passes the decoded body through when there is no spec.
     */
    private function mapResponse(array $operation, $decoded)
    {
        $spec = $this->json($operation['response_spec']);

        return $spec ? MappingEngine::extract($decoded, $spec) : $decoded;
    }

    /**
     * error_spec {"message": "path"} picks the upstream error text. Null when absent or not a string.
     */
    private function errorMessage(array $operation, $decoded, Redactor $redactor): ?string
    {
        $spec = $this->json($operation['error_spec']);
        if (!isset($spec['message'])) {
            return null;
        }

        $message = MappingEngine::extract($decoded, ['extract' => $spec['message']]);

        return is_string($message) && $message !== '' ? $redactor->text($message) : null;
    }

    /**
     * Decoded JSON when the body is JSON, else the raw text, null when empty
     */
    private function decodeBody(string $body)
    {
        if (trim($body) === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $body;
    }

    private function json(?string $value): array
    {
        $decoded = json_decode((string)$value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
