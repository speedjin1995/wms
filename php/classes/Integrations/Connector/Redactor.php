<?php
namespace App\Integrations\Connector;

/**
 * Masks secrets in anything that is logged or returned: known secret values,
 * sensitive headers and sensitive query params.
 */
class Redactor
{
    private const MASK = '***';
    private const SENSITIVE_HEADERS = ['authorization', 'proxy-authorization', 'cookie', 'set-cookie'];
    private const SENSITIVE_PARAMS = 'key|token|api_key|apikey|access_token|refresh_token|signature|sig|client_secret|password|secret';

    /** @var string[] */
    private array $values = [];

    public function __construct(array $secrets = [])
    {
        $this->addSecrets($secrets);
    }

    /**
     * Remember every string value in $secrets (nested arrays too) so it is masked wherever it shows up
     */
    public function addSecrets(array $secrets): void
    {
        array_walk_recursive($secrets, function ($value) {
            // Very short values would mask ordinary text
            if (is_string($value) && strlen($value) >= 4) {
                $this->values[] = $value;
                $this->values[] = base64_encode($value);
                $this->values[] = rawurlencode($value);
            }
        });

        $this->values = array_values(array_unique($this->values));
        usort($this->values, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
    }

    public function text(string $text): string
    {
        if (!empty($this->values)) {
            $text = str_replace($this->values, self::MASK, $text);
        }

        return (string)preg_replace('/([?&](?:' . self::SENSITIVE_PARAMS . ')=)[^&\s"\'#]*/i', '$1' . self::MASK, $text);
    }

    /**
     * @param array<string, string> $headers
     */
    public function headers(array $headers): array
    {
        $masked = [];
        foreach ($headers as $name => $value) {
            $lower = strtolower((string)$name);
            $sensitive = in_array($lower, self::SENSITIVE_HEADERS, true)
                || preg_match('/(key|token|secret|signature|password)/', $lower);
            $masked[$name] = $sensitive ? self::MASK : $this->text((string)$value);
        }

        return $masked;
    }

    /**
     * Strings and nested arrays, keys left as they are
     */
    public function data($data)
    {
        if (is_string($data)) {
            return $this->text($data);
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->data($value);
            }
        }

        return $data;
    }
}
