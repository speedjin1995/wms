<?php
namespace App\Integrations\Connector\Auth;

use App\Integrations\Connector\PreparedRequest;

/**
 * Adds one auth scheme to an outgoing request.
 * $config is the plain auth_config JSON, $secret the decrypted secret JSON.
 */
interface AuthProvider
{
    /**
     * False when the credential is missing something the scheme needs
     */
    public function apply(PreparedRequest $request, array $config, array $secret): bool;
}
