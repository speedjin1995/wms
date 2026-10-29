<?php
namespace App\Integrations\Connector\Adapters;

use App\Integrations\Connector\ErrorCode;
use App\Integrations\Connector\PreparedRequest;
use App\Integrations\Connector\RawResponse;

/**
 * JSON / form / raw over HTTP(S) with curl. Redirects are not followed (SSRF), and the IP checked by UrlGuard is pinned.
 */
class RestAdapter implements ProtocolAdapter
{
    public function send(PreparedRequest $request): RawResponse
    {
        $lines = [];
        foreach ($request->headers as $name => $value) {
            // Stops header injection through config or secrets
            if (!preg_match('/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+$/', (string)$name) || preg_match('/[\r\n]/', (string)$value)) {
                return RawResponse::error(ErrorCode::INVALID_REQUEST, 'Invalid header ' . $name);
            }
            $lines[] = $name . ': ' . $value;
        }
        // No 100-continue round trip on larger bodies
        $lines[] = 'Expect:';

        $response = new RawResponse();
        $ch = curl_init($request->fullUrl());
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $request->method,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_CONNECTTIMEOUT_MS => $request->connectTimeoutMs,
            CURLOPT_TIMEOUT_MS => $request->timeoutMs,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_RESOLVE => $request->resolve,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use ($response) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $response->headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($line);
            }
        ]);
        if ($request->body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request->body);
        }

        $raw = curl_exec($ch);

        if ($raw === false) {
            $errno = curl_errno($ch);
            $detail = curl_error($ch);
            curl_close($ch);
            return RawResponse::error($errno === CURLE_OPERATION_TIMEDOUT ? ErrorCode::TIMEOUT : ErrorCode::UNREACHABLE, $detail);
        }

        $response->status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $response->body = (string)$raw;
        curl_close($ch);

        return $response;
    }
}
