<?php
namespace App\Integrations\Connector;

/**
 * One api_call_logs row per call (metadata only, no bodies), plus a redacted error_log line for failures.
 * Never breaks the call it is logging.
 */
class CallLogger
{
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
}
