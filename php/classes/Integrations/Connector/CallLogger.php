<?php
namespace App\Integrations\Connector;

/**
 * One api_call_logs row per call (metadata plus redacted, capped bodies), plus a redacted error_log line for failures.
 * Never breaks the call it is logging.
 */
class CallLogger
{
    private const BODY_LIMIT = 8192;
    private const BLOB_MIN = 1024;

    private ConnectorRepository $repo;

    public function __construct(ConnectorRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * $call: connector, operation, req_bytes, resp_bytes, detail (internal error text, redacted here)
     */
    public function log(NormalizedResult $result, array $call, Redactor $redactor): void
    {
        $errorMessage = null;
        if (!$result->ok) {
            $errorMessage = $result->message . ($call['detail'] !== '' ? ' - ' . $call['detail'] : '');
            $errorMessage = mb_substr($redactor->text($errorMessage), 0, 255);
        }

        try {
            $this->repo->insertCallLog([
                'call_id' => $result->callId,
                'connector_id' => $call['connector']['id'],
                'operation_id' => $call['operation']['id'] ?? null,
                'ok' => $result->ok ? 1 : 0,
                'error_code' => $result->errorCode,
                'http_status' => $result->httpStatus ?: null,
                'attempts' => $result->attempts,
                'latency_ms' => $result->latencyMs,
                'req_bytes' => $call['req_bytes'],
                'resp_bytes' => $call['resp_bytes'],
                'request_body' => $this->body($call['req_body'] ?? null, $redactor),
                'response_body' => $this->body($call['resp_body'] ?? null, $redactor),
                'request_at' => $call['req_at'] ?? null,
                'response_at' => $call['resp_at'] ?? null,
                'error_message' => $errorMessage
            ]);
        } catch (\Throwable $e) {
            error_log('CallLogger - ' . $e->getMessage());
        }

        if (!$result->ok) {
            error_log(json_encode([
                'evt' => 'api_call',
                'call_id' => $result->callId,
                'connector' => $call['connector']['code'],
                'op' => $call['operation']['code'] ?? null,
                'error_code' => $result->errorCode,
                'http' => $result->httpStatus,
                'ms' => $result->latencyMs,
                'attempts' => $result->attempts,
                'error' => $errorMessage
            ]));
        }
    }

    // Redacted before the cut, so a secret is never shown half-way at the end of the kept part
    private function body(?string $text, Redactor $redactor): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        return mb_strcut($redactor->text($this->expandJson($text)), 0, self::BODY_LIMIT);
    }

    // LLM replies hold JSON as a string; store it as real JSON. Anything that is not JSON stays as it is.
    private function expandJson(string $text): string
    {
        $decoded = json_decode($text);
        if (!is_array($decoded) && !is_object($decoded)) {
            return $text;
        }

        return json_encode($this->expandValue($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $text;
    }

    // A photo would fill the whole log body and the cut would break its JSON. Plain or data: URI form.
    private function isBase64Blob(string $value): bool
    {
        if (strlen($value) < self::BLOB_MIN) {
            return false;
        }

        $start = strncmp($value, 'data:', 5) === 0 ? strpos($value, ';base64,') : false;
        $data = $start === false ? $value : substr($value, $start + 8);

        return strspn($data, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=_-') === strlen($data);
    }

    private function expandValue($value)
    {
        if (is_string($value)) {
            if ($this->isBase64Blob($value)) {
                return '[base64 omitted, ' . strlen($value) . ' bytes]';
            }

            $trimmed = ltrim($value);
            if ($trimmed === '' || ($trimmed[0] !== '{' && $trimmed[0] !== '[')) {
                return $value;
            }

            $decoded = json_decode($trimmed);
            if (!is_array($decoded) && !is_object($decoded)) {
                return $value;
            }
            $value = $decoded;
        }

        if (is_array($value) || is_object($value)) {
            foreach ($value as &$item) {
                $item = $this->expandValue($item);
            }
            unset($item);
        }

        return $value;
    }
}
