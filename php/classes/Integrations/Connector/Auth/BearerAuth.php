<?php
namespace App\Integrations\Connector\Auth;

use App\Integrations\Connector\PreparedRequest;

/**
 * secret {"token": "..."}
 */
class BearerAuth implements AuthProvider
{
    public function apply(PreparedRequest $request, array $config, array $secret): bool
    {
        $token = (string)($secret['token'] ?? '');
        if ($token === '') {
            return false;
        }

        $request->headers['Authorization'] = 'Bearer ' . $token;

        return true;
    }
}
