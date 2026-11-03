<?php
namespace App\Integrations\Connector;

/**
 * The only place secrets are encrypted or decrypted.
 * AES-256-GCM stored as base64(iv | tag | ciphertext); keys are versioned in php/config/secret.php.
 */
class SecretStore
{
    /** @var array<int, string> */
    private array $keys;
    private int $current;

    /**
     * Keys come from php/config/secret.php unless given (tests, key rotation tool)
     */
    public function __construct(?array $keys = null, ?int $current = null)
    {
        if ($keys === null) {
            $file = __DIR__ . '/../../../config/secret.php';
            if (is_file($file)) {
                require_once $file;
            }
            $keys = defined('WMS_SECRET_KEYS') ? WMS_SECRET_KEYS : [];
            $current = defined('WMS_SECRET_KEY_CURRENT') ? WMS_SECRET_KEY_CURRENT : 0;
        }

        $this->keys = array_filter($keys, function ($key) {
            return is_string($key) && strlen($key) === 32;
        });
        $this->current = (int)$current;
    }

    /**
     * ['cipher' => string, 'version' => int]. Throws when no valid current key is configured.
     */
    public function encrypt(array $data): array
    {
        if (!isset($this->keys[$this->current])) {
            throw new \RuntimeException('SecretStore - no valid current key configured');
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt(json_encode($data), 'aes-256-gcm', $this->keys[$this->current], OPENSSL_RAW_DATA, $iv, $tag);

        return ['cipher' => base64_encode($iv . $tag . $cipher), 'version' => $this->current];
    }

    /**
     * Decoded secret, [] when nothing is stored, null when it cannot be decrypted
     */
    public function decrypt(?string $cipher, ?int $version): ?array
    {
        if ($cipher === null || $cipher === '') {
            return [];
        }

        $key = $this->keys[(int)$version] ?? null;
        $raw = base64_decode($cipher, true);
        if ($key === null || $raw === false || strlen($raw) < 29) {
            return null;
        }

        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        if ($plain === false) {
            return null;
        }

        $data = json_decode($plain, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Last 4 characters, so pages can show which secret is saved without decrypting it
     */
    public static function hint(string $secret): string
    {
        return strlen($secret) > 8 ? '...' . substr($secret, -4) : '...';
    }
}
