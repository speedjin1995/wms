<?php
namespace App\Integrations\Connector\Adapters;

/**
 * protocol => adapter. Adding a protocol = one adapter class + one entry here + one ENUM value on api_connectors.protocol.
 */
class AdapterRegistry
{
    private const ADAPTERS = [
        'rest' => RestAdapter::class
    ];

    public static function for(string $protocol): ?ProtocolAdapter
    {
        $class = self::ADAPTERS[$protocol] ?? null;

        return $class ? new $class() : null;
    }
}
