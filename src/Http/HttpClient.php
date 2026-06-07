<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Http;

use BuildryWire\Wire\WireConnectionError;

/**
 * Transport abstraction. Implementations perform a single HTTP request with no
 * retry logic of their own — retries are handled by the {@see \BuildryWire\Wire\Wire}
 * client. Inject a custom implementation in tests to mock the network.
 */
interface HttpClient
{
    /**
     * Perform one request.
     *
     * @param array<string, string> $headers
     *
     * @throws WireConnectionError on network failure or timeout.
     */
    public function send(string $method, string $url, array $headers, ?string $body, float $timeoutSeconds): HttpResponse;
}
