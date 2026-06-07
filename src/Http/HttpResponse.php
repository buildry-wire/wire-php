<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Http;

/**
 * A minimal HTTP response value object returned by {@see HttpClient}.
 */
final class HttpResponse
{
    /**
     * @param array<string, string> $headers Lower-cased header names => value.
     */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = []
    ) {
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
