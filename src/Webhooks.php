<?php

declare(strict_types=1);

namespace BuildryWire\Wire;

use BuildryWire\Wire\Model\WireEvent;

/**
 * Verifies inbound webhook signatures.
 *
 * Signature scheme: header `WirePayment-Signature: t=<unix>,v1=<hex>` where
 * `hex = HMAC-SHA256(secret, "<t>.<rawBody>")`. Verification runs on the RAW
 * request body, before any JSON parsing, and fails closed on any error.
 */
final class Webhooks
{
    public const SIGNATURE_HEADER = 'WirePayment-Signature';
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    /**
     * Verify a webhook and return the parsed event. `$payload` is the raw body.
     *
     * @throws SignatureVerificationError
     */
    public function verify(string $payload, string $header, string $secret, int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS): WireEvent
    {
        return $this->verifyAt($payload, $header, $secret, $toleranceSeconds, time());
    }

    /**
     * Testable core taking an explicit `$now` (unix seconds).
     *
     * @throws SignatureVerificationError
     */
    public function verifyAt(string $payload, string $header, string $secret, int $toleranceSeconds, int $now): WireEvent
    {
        [$t, $v1] = self::parseHeader($header);
        if ($t === null || $v1 === null || $v1 === '') {
            throw new SignatureVerificationError('malformed signature header');
        }

        if (abs($now - $t) > $toleranceSeconds) {
            throw new SignatureVerificationError('timestamp outside tolerance');
        }

        $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);

        // Constant-time comparison; fails closed.
        if (!hash_equals($expected, $v1)) {
            throw new SignatureVerificationError('signature mismatch');
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            throw new SignatureVerificationError('payload is not valid JSON');
        }

        return WireEvent::fromArray($decoded);
    }

    /**
     * @return array{0: ?int, 1: ?string}
     */
    private static function parseHeader(string $header): array
    {
        $t = null;
        $v1 = null;
        foreach (explode(',', $header) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) !== 2) {
                continue;
            }
            [$key, $value] = $kv;
            if ($key === 't') {
                if (!preg_match('/^\d+$/', $value)) {
                    return [null, null];
                }
                $t = (int) $value;
            } elseif ($key === 'v1') {
                $v1 = $value;
            }
        }
        return [$t, $v1];
    }
}
