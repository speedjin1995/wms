<?php
namespace App\Controllers;

use App\Integrations\LlmExtractor;
use App\Services\IntegrationService;

/**
 * Tenant settings for the AI DO scan connection: own key overrides the platform default
 */
class IntegrationController extends BaseController
{
    private const CODE = 'do_extract';
    private const NAME = 'AI DO Scan';

    private IntegrationService $service;

    public function __construct(IntegrationService $service)
    {
        $this->service = $service;
    }

    public function get(): array
    {
        return ['status' => 'success', 'data' => $this->service->getForTenant(self::CODE)];
    }

    public function save(): array
    {
        return $this->service->saveOverride(
            self::CODE,
            self::NAME,
            trim($_POST['provider'] ?? ''),
            trim($_POST['model'] ?? ''),
            trim($_POST['api_key'] ?? '')
        );
    }

    public function remove(): array
    {
        return $this->service->removeOverride(self::CODE);
    }

    /**
     * Check the typed key (or the stored / platform one when blank) with a tiny call
     */
    public function test(): array
    {
        $key = trim($_POST['api_key'] ?? '');

        if ($key !== '') {
            $provider = trim($_POST['provider'] ?? '');
            if (!isset(LlmExtractor::PROVIDERS[$provider])) {
                return ['status' => 'failed', 'message' => 'Invalid provider'];
            }
            $config = [
                'base_url' => '',
                'credentials' => ['key' => $key],
                'options' => ['provider' => $provider, 'model' => trim($_POST['model'] ?? ''), 'timeout' => 30]
            ];
        } else {
            $config = $this->service->getConfig(self::CODE);
            if (!$config) {
                return ['status' => 'failed', 'message' => 'AI extraction is not configured'];
            }
        }

        return (new LlmExtractor())->ping($config)
            ? ['status' => 'success', 'message' => 'Connection OK']
            : ['status' => 'failed', 'message' => 'Connection failed. Check the provider, model and key.'];
    }
}
