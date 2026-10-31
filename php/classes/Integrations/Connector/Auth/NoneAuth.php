<?php
namespace App\Integrations\Connector\Auth;

use App\Integrations\Connector\PreparedRequest;

class NoneAuth implements AuthProvider
{
    public function apply(PreparedRequest $request, array $config, array $secret): bool
    {
        return true;
    }
}
