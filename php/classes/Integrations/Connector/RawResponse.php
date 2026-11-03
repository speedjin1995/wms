<?php
namespace App\Integrations\Connector;

/**
 * What an adapter got back. errorCode is set only for transport failures (timeout, unreachable, ...).
 */
class RawResponse
{
    public int $status = 0;
    /** @var array<string, string> lower-case header names */
    public array $headers = [];
    public string $body = '';
    public ?string $errorCode = null;
    /** Internal detail for the log only, never returned to the caller */
    public string $errorDetail = '';

    public static function error(string $code, string $detail = ''): self
    {
        $response = new self();
        $response->errorCode = $code;
        $response->errorDetail = $detail;

        return $response;
    }
}
