<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Tests;

use BuildryWire\Wire\Http\HttpResponse;
use BuildryWire\Wire\Wire;
use BuildryWire\Wire\WireConnectionError;
use BuildryWire\Wire\WireError;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private function wire(callable $handler, array $opts = []): array
    {
        $mock = new MockHttpClient($handler);
        $client = new Wire('sk_test_123', array_merge([
            'baseURL' => 'https://api.example.test',
            'httpClient' => $mock,
            'backoffMs' => 1,
            // No-op sleeper so tests do not actually wait.
            'sleeper' => static function (int $ms): void {},
        ], $opts));
        return [$client, $mock];
    }

    public function testSendsAuthAndDecodes(): void
    {
        $auth = '';
        [$client, $mock] = $this->wire(function (int $attempt, array $call) use (&$auth): HttpResponse {
            $auth = $call['headers']['Authorization'] ?? '';
            return MockHttpClient::json(200, ['id' => 'pi_1', 'object' => 'payment_intent', 'amount' => 50000]);
        });

        $pi = $client->paymentIntents->retrieve('pi_1');
        $this->assertSame('Bearer sk_test_123', $auth);
        $this->assertSame('pi_1', $pi->id);
        $this->assertSame(50000, $pi->amount);
    }

    public function testRetriesOn503(): void
    {
        $n = 0;
        [$client, $mock] = $this->wire(function (int $attempt, array $call) use (&$n): HttpResponse {
            $n++;
            if ($n < 3) {
                return MockHttpClient::json(503, []);
            }
            return MockHttpClient::json(200, ['id' => 'pi_1', 'object' => 'payment_intent']);
        }, ['maxRetries' => 3]);

        $client->paymentIntents->retrieve('pi_1');
        $this->assertSame(3, $n);
    }

    public function testRetriesOn429HonoringRetryAfter(): void
    {
        $delays = [];
        $n = 0;
        $mock = new MockHttpClient(function (int $attempt, array $call) use (&$n): HttpResponse {
            $n++;
            if ($n < 2) {
                return new HttpResponse(429, '', ['retry-after' => '2']);
            }
            return MockHttpClient::json(200, ['id' => 'pi_1', 'object' => 'payment_intent']);
        });
        $client = new Wire('sk_test_123', [
            'httpClient' => $mock,
            'maxRetries' => 3,
            'backoffMs' => 1,
            'sleeper' => static function (int $ms) use (&$delays): void {
                $delays[] = $ms;
            },
        ]);

        $client->paymentIntents->retrieve('pi_1');
        $this->assertSame([2000], $delays, 'Retry-After (2s) should override backoff');
    }

    public function testDoesNotRetryOn400(): void
    {
        $n = 0;
        [$client, $mock] = $this->wire(function (int $attempt, array $call) use (&$n): HttpResponse {
            $n++;
            return MockHttpClient::json(400, ['error' => ['type' => 'invalid_request_error', 'message' => 'bad']]);
        }, ['maxRetries' => 3]);

        $this->expectException(WireError::class);
        try {
            $client->paymentIntents->retrieve('x');
        } finally {
            $this->assertSame(1, $n);
        }
    }

    public function testRetriesOnNetworkErrorThenThrowsConnectionError(): void
    {
        $n = 0;
        [$client, $mock] = $this->wire(function (int $attempt, array $call) use (&$n): WireConnectionError {
            $n++;
            return new WireConnectionError('boom');
        }, ['maxRetries' => 2]);

        $this->expectException(WireConnectionError::class);
        try {
            $client->paymentIntents->retrieve('x');
        } finally {
            // initial attempt + 2 retries
            $this->assertSame(3, $n);
        }
    }

    public function testSendsIdempotencyKeyOnPost(): void
    {
        $key = null;
        [$client, $mock] = $this->wire(function (int $attempt, array $call) use (&$key): HttpResponse {
            $key = $call['headers']['Idempotency-Key'] ?? null;
            return MockHttpClient::json(200, ['id' => 'pi_1', 'object' => 'payment_intent']);
        });

        $client->paymentIntents->create(['amount' => 1]);
        $this->assertNotEmpty($key);
        $this->assertStringStartsWith('idk_', $key);
    }

    public function testReusesIdempotencyKeyAcrossRetries(): void
    {
        $keys = [];
        $n = 0;
        [$client, $mock] = $this->wire(function (int $attempt, array $call) use (&$keys, &$n): HttpResponse {
            $n++;
            $keys[] = $call['headers']['Idempotency-Key'] ?? null;
            if ($n < 3) {
                return MockHttpClient::json(503, []);
            }
            return MockHttpClient::json(200, ['id' => 'pi_1', 'object' => 'payment_intent']);
        }, ['maxRetries' => 3]);

        $client->paymentIntents->create(['amount' => 1]);
        $this->assertCount(3, $keys);
        $this->assertSame($keys[0], $keys[1]);
        $this->assertSame($keys[1], $keys[2]);
    }

    public function testCallerSuppliedIdempotencyKeyIsUsed(): void
    {
        $key = null;
        [$client, $mock] = $this->wire(function (int $attempt, array $call) use (&$key): HttpResponse {
            $key = $call['headers']['Idempotency-Key'] ?? null;
            return MockHttpClient::json(200, ['id' => 'pi_1', 'object' => 'payment_intent']);
        });

        $client->paymentIntents->create(['amount' => 1], 'idk_custom');
        $this->assertSame('idk_custom', $key);
    }
}
