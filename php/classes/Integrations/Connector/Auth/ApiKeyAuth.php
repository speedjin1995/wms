<?php
namespace App\Integrations\Connector\Auth;

use App\Integrations\Connector\PreparedRequest;

/**
 * config {"in": "header"|"query", "name": "x-api-key"}, secret {"key": "..."}
 */
class ApiKeyAuth implements AuthProvider
{
    public function apply(PreparedRequest $request, array $config, array $secret): bool
    {
        $key = (string)($secret['key'] ?? '');
        if ($key === '') {
            return false;
        }

        if (($config['in'] ?? 'header') === 'query') {
            $request->query[(string)($config['name'] ?? 'key')] = $key;
        } else {
            $request->headers[(string)($config['name'] ?? 'x-api-key')] = $key;
        }

        return true;
    }
}
