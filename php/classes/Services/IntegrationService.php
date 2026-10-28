<?php
namespace App\Services;

use App\Integrations\LlmExtractor;

require_once __DIR__ . '/../../config/secret.php';

/**
 * api_integrations rows: company = 0 is the platform default (set in wms_admin),
 * a row for the session company overrides it. Secrets are encrypted and never returned to the page.
 */
class IntegrationService extends BaseService
{
    /**
     * Connection config for the session company, own row first, else the platform default.
     * credentials / headers / options come back decoded.
     */
    public function getConfig(string $code): ?array
    {
        $row = $this->fetchOne(
            "SELECT company, method, base_url, auth_type, credentials, headers, options FROM api_integrations
            WHERE code = ? AND company IN (?, 0) AND deleted = 0 ORDER BY company DESC LIMIT 1",
            'si',
            [$code, $this->company]
        );

        if (!$row) {
            return null;
        }

        $credentials = $row['credentials'] ? $this->decrypt($row['credentials']) : '';
        if ($credentials === null) {
            error_log('IntegrationService - unable to decrypt ' . $code . ' for company ' . $row['company']);
            return null;
        }

        $row['credentials'] = json_decode($credentials, true) ?: [];
        $row['headers'] = json_decode($row['headers'] ?? '', true) ?: [];
        $row['options'] = json_decode($row['options'] ?? '', true) ?: [];

        return $row;
    }

    /**
     * What the tenant page shows. Nothing about the platform key is returned.
     */
    public function getForTenant(string $code): array
    {
        $own = $this->fetchOne(
            "SELECT secret_hint, options FROM api_integrations WHERE code = ? AND company = ? AND deleted = 0",
            'si',
            [$code, $this->company]
        );

        if ($own) {
            $options = json_decode($own['options'] ?? '', true) ?: [];
            return [
                'source' => 'own',
                'provider' => $options['provider'] ?? '',
                'model' => $options['model'] ?? '',
                'secret_hint' => $own['secret_hint'] ?? ''
            ];
        }

        $platform = $this->fetchOne("SELECT id FROM api_integrations WHERE code = ? AND company = 0 AND deleted = 0", 's', [$code]);

        return ['source' => $platform ? 'platform' : 'none', 'provider' => '', 'model' => '', 'secret_hint' => ''];
    }

    /**
     * Save the tenant's own LLM key. A blank key keeps the stored one.
     */
    public function saveOverride(string $code, string $name, string $provider, string $model, string $secret): array
    {
        if (!isset(LlmExtractor::PROVIDERS[$provider])) {
            return ['status' => 'failed', 'message' => 'Invalid provider'];
        }

        $own = $this->fetchOne(
            "SELECT id FROM api_integrations WHERE code = ? AND company = ? AND deleted = 0",
            'si',
            [$code, $this->company]
        );

        if (!$own && $secret === '') {
            return ['status' => 'failed', 'message' => 'Please enter the API key'];
        }

        $data = [
            'method' => 'rest',
            'base_url' => LlmExtractor::PROVIDERS[$provider]['base_url'],
            'auth_type' => 'api_key',
            'options' => json_encode(['provider' => $provider, 'model' => $model]),
            'modified_by' => $this->user
        ];

        if ($secret !== '') {
            $data['credentials'] = $this->encrypt(json_encode(['key' => $secret]));
            $data['secret_hint'] = '...' . substr($secret, -4);
        }

        if ($own) {
            $ok = $this->updateRow('api_integrations', $data, 'id = ? AND company = ?', 'ii', [$own['id'], $this->company]);
        } else {
            $data['company'] = $this->company;
            $data['code'] = $code;
            $data['name'] = $name;
            $data['created_by'] = $this->user;
            $ok = $this->insertRow('api_integrations', $data) > 0;
        }

        return $ok
            ? ['status' => 'success', 'message' => 'Saved']
            : ['status' => 'failed', 'message' => 'Failed to save'];
    }

    /**
     * Drop the tenant's own key so the platform default is used again
     */
    public function removeOverride(string $code): array
    {
        $ok = $this->executeWrite(
            "UPDATE api_integrations SET deleted = 1, modified_by = ? WHERE code = ? AND company = ? AND deleted = 0",
            'isi',
            [$this->user, $code, $this->company]
        );

        return $ok
            ? ['status' => 'success', 'message' => 'Using platform default']
            : ['status' => 'failed', 'message' => 'Failed to update'];
    }

    /**
     * AES-256-GCM, stored as base64(iv | tag | ciphertext). Same format as wms_admin encryptSecret().
     */
    private function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', WMS_SECRET_KEY, OPENSSL_RAW_DATA, $iv, $tag);

        return base64_encode($iv . $tag . $cipher);
    }

    private function decrypt(string $stored): ?string
    {
        $raw = base64_decode($stored, true);

        if ($raw === false || strlen($raw) < 29) {
            return null;
        }

        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', WMS_SECRET_KEY, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));

        return $plain === false ? null : $plain;
    }
}
