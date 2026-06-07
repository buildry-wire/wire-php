<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Tests;

use BuildryWire\Wire\WireError;
use PHPUnit\Framework\TestCase;

final class ErrorsTest extends TestCase
{
    public function testParsesEnvelope(): void
    {
        $body = '{"error":{"type":"invalid_request_error","code":"amount_invalid",'
            . '"message":"amount must be positive","param":"amount","request_id":"req_123",'
            . '"doc_url":"https://docs.wire.mn/errors","operator_decline_code":"insufficient_funds"}}';
        $err = WireError::fromResponse(400, $body);

        $this->assertInstanceOf(WireError::class, $err);
        $this->assertSame(400, $err->statusCode);
        $this->assertSame('invalid_request_error', $err->type);
        $this->assertSame('amount_invalid', $err->code);
        $this->assertSame('amount', $err->param);
        $this->assertSame('req_123', $err->requestId);
        $this->assertSame('https://docs.wire.mn/errors', $err->docUrl);
        $this->assertSame('insufficient_funds', $err->operatorDeclineCode);
        $this->assertStringContainsString('amount must be positive', (string) $err);
    }

    public function testFallsBackOnNonJson(): void
    {
        $err = WireError::fromResponse(500, 'not json');
        $this->assertInstanceOf(WireError::class, $err);
        $this->assertSame(500, $err->statusCode);
        $this->assertSame('api_error', $err->type);
    }

    public function testNeverLeaksApiKeyInString(): void
    {
        $err = WireError::fromResponse(401, '{"error":{"type":"api_error","message":"unauthorized"}}');
        $this->assertStringNotContainsString('sk_', (string) $err);
    }
}
