<?php
namespace App\Integrations\Connector\Adapters;

use App\Integrations\Connector\PreparedRequest;
use App\Integrations\Connector\RawResponse;

/**
 * One per protocol. Never throws: transport failures come back as RawResponse::error().
 */
interface ProtocolAdapter
{
    public function send(PreparedRequest $request): RawResponse;
}
