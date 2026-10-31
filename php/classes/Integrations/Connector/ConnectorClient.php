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

    public function __construct(ConnectorRepository $repo, ?SecretStore $secrets = null, ?CallLogger $logger = null)
    {
        $this->repo = $repo;
        $this->secrets = $secrets ?? new SecretStore();
        $this->logger = $logger ?? new CallLogger($repo);
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

        $result->callId = bin2hex(random_bytes(8));
        $result->attempts = $call['attempts'];
        $result->latencyMs = (int)round((microtime(true) - $started) * 1000);

        if ($call['connector'] && empty($ctx['preview'])) {
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
