<?php
namespace App\Integrations\Connector;

/**
 * Same shape for every connector and protocol
 */
class NormalizedResult
{
    public bool $ok = false;
    /** @var mixed decoded JSON array, or the raw string */
    public $data = null;
    public ?string $errorCode = null;
    public string $message = '';
    public int $httpStatus = 0;
    public string $callId = '';
    public int $latencyMs = 0;
    public int $attempts = 0;

    public static function failure(string $errorCode, string $callId = '', int $httpStatus = 0): self
    {
        $result = new self();
        $result->errorCode = $errorCode;
        $result->message = ErrorCode::message($errorCode, $httpStatus);
        $result->httpStatus = $httpStatus;
        $result->callId = $callId;

        return $result;
    }

    /**
     * House JSON response: {status, message, data, meta, error_code}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->ok ? 'success' : 'failed',
            'message' => $this->ok ? 'Success' : $this->message,
            'data' => $this->data,
            'meta' => [
                'call_id' => $this->callId,
                'http_status' => $this->httpStatus,
                'latency_ms' => $this->latencyMs,
                'attempts' => $this->attempts
            ],
            'error_code' => $this->errorCode
        ];
    }
}
