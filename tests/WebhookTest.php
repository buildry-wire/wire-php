<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Tests;

use BuildryWire\Wire\SignatureVerificationError;
use BuildryWire\Wire\Webhooks;
use PHPUnit\Framework\TestCase;

final class WebhookTest extends TestCase
{
    /**
     * @return array{secret: string, now: int, tolerance_seconds: int, cases: list<array{name: string, body: string, header: string, valid: bool}>}
     */
    private function vectors(): array
    {
        $json = file_get_contents(__DIR__ . '/data/webhook-signatures.json');
        $this->assertNotFalse($json, 'conformance vector file must be readable');
        return json_decode($json, true);
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: bool, 4: string, 5: int, 6: int}>
     */
    public static function vectorProvider(): array
    {
        $vectors = json_decode(file_get_contents(__DIR__ . '/data/webhook-signatures.json'), true);
        $out = [];
        foreach ($vectors['cases'] as $case) {
            $out[$case['name']] = [
                $case['body'],
                $case['header'],
                $vectors['secret'],
                $case['valid'],
                $case['name'],
                $vectors['tolerance_seconds'],
                $vectors['now'],
            ];
        }
        return $out;
    }

    /**
     * @dataProvider vectorProvider
     */
    public function testConformanceVectors(string $body, string $header, string $secret, bool $valid, string $name, int $tolerance, int $now): void
    {
        $w = new Webhooks();
        $ok = true;
        $event = null;
        try {
            $event = $w->verifyAt($body, $header, $secret, $tolerance, $now);
        } catch (SignatureVerificationError) {
            $ok = false;
        }

        $this->assertSame($valid, $ok, "vector '{$name}' verification result");
        if ($valid) {
            $this->assertNotNull($event);
            $this->assertNotEmpty($event->type);
        }
    }

    public function testVerifyUsesCurrentTimeAndReturnsEvent(): void
    {
        $secret = 'whsec_runtime';
        $now = time();
        $body = '{"id":"evt_1","object":"event","type":"payment_intent.succeeded"}';
        $sig = hash_hmac('sha256', $now . '.' . $body, $secret);
        $header = "t={$now},v1={$sig}";

        $event = (new Webhooks())->verify($body, $header, $secret);
        $this->assertSame('evt_1', $event->id);
        $this->assertSame('payment_intent.succeeded', $event->type);
    }

    public function testRejectsTamperedSignatureWithDefaultTolerance(): void
    {
        $now = time();
        $body = '{"id":"evt_1","object":"event","type":"x"}';
        $header = "t={$now},v1=" . str_repeat('0', 64);

        $this->expectException(SignatureVerificationError::class);
        (new Webhooks())->verify($body, $header, 'whsec_x');
    }
}
