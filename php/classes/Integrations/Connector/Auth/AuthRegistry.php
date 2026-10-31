<?php
namespace App\Integrations\Connector\Auth;

/**
 * auth_type => provider. Types without a provider yet (oauth2_cc, ws_security, mtls) return null.
 */
class AuthRegistry
{
    private const PROVIDERS = [
        'none' => NoneAuth::class,
        'api_key' => ApiKeyAuth::class,
        'bearer' => BearerAuth::class,
        'basic' => BasicAuth::class,
        'header' => HeaderAuth::class
    ];

    public static function for(string $authType): ?AuthProvider
    {
        $class = self::PROVIDERS[$authType] ?? null;

        return $class ? new $class() : null;
    }
}
