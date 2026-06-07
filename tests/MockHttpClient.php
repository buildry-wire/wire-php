<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Tests;

use BuildryWire\Wire\Http\HttpClient;
use BuildryWire\Wire\Http\HttpResponse;
use BuildryWire\Wire\WireConnectionError;

/**
 * Records every request and returns responses produced by an injected handler.
 * Mirrors the role of the node test server, without a real socket.
 */
final class MockHttpClient implements HttpClient
{
    /** @var list<array{method: string, url: string, headers: array<string, string>, body: ?string}> */
    public array $calls = [];

    /** @var callable(int, array{method: string, url: string, headers: array<string, string>, body: ?string}): (HttpResponse|WireConnectionError) */
    private $handler;

    /**
     * @param callable(int, array{method: string, url: string, headers: array<string, string>, body: ?string}): (HttpResponse|WireConnectionError) $handler
     *   Called with the zero-based attempt index and the request. Return an
     *   HttpResponse to respond, or a WireConnectionError to simulate a network failure.
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    public function send(string $method, string $url, array $headers, ?string $body, float $timeoutSeconds): HttpResponse
    {
        $attempt = count($this->calls);
        $call = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];
        $this->calls[] = $call;

        $result = ($this->handler)($attempt, $call);
        if ($result instanceof WireConnectionError) {
            throw $result;
        }
        return $result;
    }

    /** Build a JSON HttpResponse helper. */
    public static function json(int $status, mixed $payload, array $headers = []): HttpResponse
    {
        return new HttpResponse($status, json_encode($payload), $headers);
    }
}
