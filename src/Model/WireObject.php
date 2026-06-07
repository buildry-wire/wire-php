<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Model;

/**
 * Base for API resource models. Holds the full decoded payload so that fields
 * added by the API in the future remain accessible via {@see WireObject::raw()}
 * even before the SDK is updated.
 */
abstract class WireObject
{
    /** @param array<string, mixed> $raw */
    protected function __construct(protected array $raw)
    {
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->raw;
    }

    public function get(string $key): mixed
    {
        return $this->raw[$key] ?? null;
    }
}
