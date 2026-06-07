<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Tests;

use BuildryWire\Wire\Http\HttpResponse;
use BuildryWire\Wire\Wire;
use PHPUnit\Framework\TestCase;

final class ResourcesTest extends TestCase
{
    private function wire(callable $handler): array
    {
        $mock = new MockHttpClient($handler);
        $client = new Wire('sk_test_123', [
            'baseURL' => 'https://api.example.test',
            'httpClient' => $mock,
            'backoffMs' => 1,
            'sleeper' => static function (int $ms): void {},
        ]);
        return [$client, $mock];
    }

    public function testCreatesPaymentIntent(): void
    {
        [$client, $mock] = $this->wire(function (int $attempt, array $call): HttpResponse {
            $this->assertSame('POST', $call['method']);
            $this->assertSame('https://api.example.test/v1/payment_intents', $call['url']);
            $this->assertJson($call['body']);
            $decoded = json_decode($call['body'], true);
            $this->assertSame(['sandbox'], $decoded['allowed_operators']);
            return MockHttpClient::json(200, [
                'id' => 'pi_1',
                'object' => 'payment_intent',
                'amount' => 50000,
                'status' => 'requires_payment_method',
            ]);
        });

        $pi = $client->paymentIntents->create([
            'amount' => 50000,
            'currency' => 'MNT',
            'allowed_operators' => ['sandbox'],
        ]);
        $this->assertSame('pi_1', $pi->id);
        $this->assertSame('requires_payment_method', $pi->status);
    }

    public function testConfirmAndCancelHitCorrectPaths(): void
    {
        $paths = [];
        [$client, $mock] = $this->wire(function (int $attempt, array $call) use (&$paths): HttpResponse {
            $paths[] = $call['url'];
            return MockHttpClient::json(200, ['id' => 'pi_1', 'object' => 'payment_intent', 'status' => 'succeeded']);
        });

        $client->paymentIntents->confirm('pi_1', ['return_url' => 'https://example.test/return']);
        $client->paymentIntents->cancel('pi_1');

        $this->assertSame('https://api.example.test/v1/payment_intents/pi_1/confirm', $paths[0]);
        $this->assertSame('https://api.example.test/v1/payment_intents/pi_1/cancel', $paths[1]);
    }

    public function testAutoPaginatesList(): void
    {
        [$client, $mock] = $this->wire(function (int $attempt, array $call): HttpResponse {
            if (!str_contains($call['url'], 'starting_after')) {
                return MockHttpClient::json(200, [
                    'object' => 'list',
                    'has_more' => true,
                    'data' => [['id' => 'ch_1', 'object' => 'charge']],
                ]);
            }
            return MockHttpClient::json(200, [
                'object' => 'list',
                'has_more' => false,
                'data' => [['id' => 'ch_2', 'object' => 'charge']],
            ]);
        });

        $ids = [];
        foreach ($client->charges->list(1) as $charge) {
            $ids[] = $charge->id;
        }
        $this->assertSame(['ch_1', 'ch_2'], $ids);
    }

    public function testListStopsOnEmptyData(): void
    {
        [$client, $mock] = $this->wire(function (int $attempt, array $call): HttpResponse {
            return MockHttpClient::json(200, ['object' => 'list', 'has_more' => true, 'data' => []]);
        });

        $ids = [];
        foreach ($client->paymentIntents->list() as $pi) {
            $ids[] = $pi->id;
        }
        $this->assertSame([], $ids);
        // Guards against an infinite loop when has_more is true but data is empty.
        $this->assertCount(1, $mock->calls);
    }

    public function testDeletesWebhookEndpoint(): void
    {
        [$client, $mock] = $this->wire(function (int $attempt, array $call): HttpResponse {
            $this->assertSame('DELETE', $call['method']);
            return MockHttpClient::json(200, ['id' => 'we_1', 'object' => 'webhook_endpoint', 'deleted' => true]);
        });

        $deleted = $client->webhookEndpoints->delete('we_1');
        $this->assertTrue($deleted->deleted);
        $this->assertSame('we_1', $deleted->id);
    }

    public function testRetrievesEvent(): void
    {
        [$client, $mock] = $this->wire(function (int $attempt, array $call): HttpResponse {
            $this->assertSame('https://api.example.test/v1/events/evt_1', $call['url']);
            return MockHttpClient::json(200, ['id' => 'evt_1', 'object' => 'event', 'type' => 'payment_intent.succeeded']);
        });

        $event = $client->events->retrieve('evt_1');
        $this->assertSame('payment_intent.succeeded', $event->type);
    }
}
