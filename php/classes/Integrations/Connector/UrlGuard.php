<?php
namespace App\Integrations\Connector;

/**
 * SSRF checks before any request leaves the server:
 * https only (unless allow_http), host pinned to the connector base_url,
 * no private / reserved IPs (unless allow_private), and the checked IP is pinned for curl.
 */
class UrlGuard
{
    /**
     * ['error' => ?string, 'resolve' => string[] curl RESOLVE entries]
     */
    public static function check(string $url, string $baseUrl, bool $allowHttp, bool $allowPrivate): array
    {
        $parts = parse_url($url);
        $base = parse_url($baseUrl);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if ($host === '' || !in_array($scheme, $allowHttp ? ['https', 'http'] : ['https'], true)) {
            return ['error' => ErrorCode::URL_BLOCKED, 'resolve' => []];
        }

        // The path must not move the call to another host
        if ($host !== strtolower($base['host'] ?? '') || isset($parts['user']) || isset($parts['pass'])) {
            return ['error' => ErrorCode::URL_BLOCKED, 'resolve' => []];
        }

        $hostIp = trim($host, '[]');
        $isIp = filter_var($hostIp, FILTER_VALIDATE_IP) !== false;
        if ($isIp) {
            $ips = [$hostIp];
        } else {
            $ips = gethostbynamel($host) ?: [];
            if (empty($ips)) {
                return ['error' => ErrorCode::UNREACHABLE, 'resolve' => []];
            }
        }

        if (!$allowPrivate) {
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    return ['error' => ErrorCode::URL_BLOCKED, 'resolve' => []];
                }
            }
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        // IP literals need no pinning; hostnames are pinned so DNS cannot change between check and call
        return ['error' => null, 'resolve' => $isIp ? [] : [$host . ':' . $port . ':' . $ips[0]]];
    }
}
