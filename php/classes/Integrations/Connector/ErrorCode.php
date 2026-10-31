<?php
namespace App\Integrations\Connector;

/**
 * Normalized error codes returned by ConnectorClient, with the message shown to the caller
 */
final class ErrorCode
{
    public const CONFIG_NOT_FOUND = 'CONFIG_NOT_FOUND';
    public const CREDENTIALS_MISSING = 'CREDENTIALS_MISSING';
    public const AUTH_UNSUPPORTED = 'AUTH_UNSUPPORTED';
    public const PROTOCOL_UNSUPPORTED = 'PROTOCOL_UNSUPPORTED';
    public const URL_BLOCKED = 'URL_BLOCKED';
    public const INVALID_REQUEST = 'INVALID_REQUEST';
    public const NOT_EXPOSED = 'NOT_EXPOSED';
    public const AUTH_FAILED = 'AUTH_FAILED';
    public const RATE_LIMITED = 'RATE_LIMITED';
    public const CLIENT_ERROR = 'CLIENT_ERROR';
    public const UPSTREAM_ERROR = 'UPSTREAM_ERROR';
    public const TIMEOUT = 'TIMEOUT';
    public const UNREACHABLE = 'UNREACHABLE';
    public const MAPPING_ERROR = 'MAPPING_ERROR';
    public const INTERNAL_ERROR = 'INTERNAL_ERROR';

    private const MESSAGES = [
        self::INTERNAL_ERROR => 'Something went wrong',
        self::CONFIG_NOT_FOUND => 'Connection is not configured',
        self::CREDENTIALS_MISSING => 'Connection credentials are missing',
        self::AUTH_UNSUPPORTED => 'Authentication type is not supported yet',
        self::PROTOCOL_UNSUPPORTED => 'Protocol is not supported yet',
        self::URL_BLOCKED => 'Connection URL is not allowed',
        self::INVALID_REQUEST => 'Invalid request',
        self::NOT_EXPOSED => 'Operation is not available',
        self::AUTH_FAILED => 'The service rejected the credentials',
        self::RATE_LIMITED => 'The service is busy, please try again later',
        self::CLIENT_ERROR => 'The service rejected the request',
        self::UPSTREAM_ERROR => 'The service returned an error',
        self::TIMEOUT => 'The service did not respond in time',
        self::UNREACHABLE => 'Unable to reach the service',
        self::MAPPING_ERROR => 'The request could not be mapped'
    ];

    public static function message(string $code, int $httpStatus = 0): string
    {
        $message = self::MESSAGES[$code] ?? 'Request failed';

        return $httpStatus > 0 && in_array($code, [self::CLIENT_ERROR, self::UPSTREAM_ERROR], true)
            ? $message . ' (' . $httpStatus . ')'
            : $message;
    }

    /**
     * Error code for an HTTP status, null when it is a success
     */
    public static function fromHttpStatus(int $status): ?string
    {
        if ($status >= 200 && $status < 300) {
            return null;
        }
        if ($status === 401 || $status === 403) {
            return self::AUTH_FAILED;
        }
        if ($status === 429) {
            return self::RATE_LIMITED;
        }

        return $status >= 400 && $status < 500 ? self::CLIENT_ERROR : self::UPSTREAM_ERROR;
    }
}
