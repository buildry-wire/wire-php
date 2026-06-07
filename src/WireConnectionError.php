<?php

declare(strict_types=1);

namespace BuildryWire\Wire;

/**
 * Raised when a request cannot reach the API or times out, after retries are
 * exhausted. Distinct from {@see WireError}, which carries a server response.
 */
class WireConnectionError extends WireError
{
    public function __construct(string $message)
    {
        parent::__construct($message, ['type' => 'connection_error']);
    }
}
