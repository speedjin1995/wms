<?php
namespace App\Integrations\Connector;

/**
 * Fully built outgoing request handed to a protocol adapter
 */
class PreparedRequest
{
    public string $url = '';
    public string $method = 'GET';
    /** @var array<string, string> */
    public array $headers = [];
    /** @var array<string, string> query params added by auth (api_key in query) */
    public array $query = [];
    public ?string $body = null;
    public int $timeoutMs = 30000;
    public int $connectTimeoutMs = 10000;
    /** @var string[] curl RESOLVE entries (host:port:ip) pinning the checked IP */
    public array $resolve = [];

    /**
     * URL with the auth query params appended
     */
    public function fullUrl(): string
    {
        if (empty($this->query)) {
            return $this->url;
        }

        return $this->url . (strpos($this->url, '?') === false ? '?' : '&') . http_build_query($this->query);
    }
}
