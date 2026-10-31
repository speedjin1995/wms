<?php
namespace App\Integrations\Connector\Auth;

use App\Integrations\Connector\PreparedRequest;

/**
 * config {"username": "..."}, secret {"password": "..."}
 */
class BasicAuth implements AuthProvider
{
    public function apply(PreparedRequest $request, array $config, array $secret): bool
    {
        $username = (string)($config['username'] ?? '');
        $password = (string)($secret['password'] ?? '');
        if ($username === '' || $password === '') {
            return false;
        }

        $request->headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);

        return true;
    }
}
