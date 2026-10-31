<?php
namespace App\Integrations\Connector\Auth;

use App\Integrations\Connector\PreparedRequest;

/**
 * Custom secret headers. secret {"X-Header-A": "...", "X-Header-B": "..."}
 */
class HeaderAuth implements AuthProvider
{
    public function apply(PreparedRequest $request, array $config, array $secret): bool
    {
        if (empty($secret)) {
            return false;
        }

        foreach ($secret as $name => $value) {
            $request->headers[(string)$name] = (string)$value;
        }

        return true;
    }
}
