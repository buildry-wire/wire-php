<?php

declare(strict_types=1);

namespace BuildryWire\Wire;

/**
 * A typed error returned by the Wire API.
 *
 * Decoded from the {"error": {...}} envelope. The API key is never included
 * in the message or any property.
 */
class WireError extends \Exception
{
    public string $type;
    public ?string $code;
    public ?string $param;
    public ?string $requestId;
    public ?string $docUrl;
    public ?string $operatorDeclineCode;
    public ?int $statusCode;

    /**
     * @param array{
     *     type?: ?string,
     *     code?: ?string,
     *     param?: ?string,
     *     requestId?: ?string,
     *     docUrl?: ?string,
     *     operatorDeclineCode?: ?string,
     *     statusCode?: ?int
     * } $opts
     */
    public function __construct(string $message, array $opts = [])
    {
        parent::__construct($message);
        $this->type = $opts['type'] ?? 'api_error';
        $this->code = $opts['code'] ?? null;
        $this->param = $opts['param'] ?? null;
        $this->requestId = $opts['requestId'] ?? null;
        $this->docUrl = $opts['docUrl'] ?? null;
        $this->operatorDeclineCode = $opts['operatorDeclineCode'] ?? null;
        $this->statusCode = $opts['statusCode'] ?? null;
    }

    public function __toString(): string
    {
        return sprintf(
            'WireError: %s (type=%s, code=%s, status=%s, request_id=%s)',
            $this->getMessage(),
            $this->type,
            $this->code ?? '',
            $this->statusCode === null ? '' : (string) $this->statusCode,
            $this->requestId ?? ''
        );
    }

    /**
     * Decode the Wire error envelope; fall back to a generic error.
     */
    public static function fromResponse(int $status, string $body): self
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded) && isset($decoded['error']) && is_array($decoded['error'])) {
            $e = $decoded['error'];
            return new self(
                isset($e['message']) && is_string($e['message']) ? $e['message'] : 'request failed',
                [
                    'type' => isset($e['type']) ? (string) $e['type'] : null,
                    'code' => isset($e['code']) ? (string) $e['code'] : null,
                    'param' => isset($e['param']) ? (string) $e['param'] : null,
                    'requestId' => isset($e['request_id']) ? (string) $e['request_id'] : null,
                    'docUrl' => isset($e['doc_url']) ? (string) $e['doc_url'] : null,
                    'operatorDeclineCode' => isset($e['operator_decline_code'])
                        ? (string) $e['operator_decline_code']
                        : null,
                    'statusCode' => $status,
                ]
            );
        }

        return new self(
            sprintf('unexpected response (status %d)', $status),
            ['type' => 'api_error', 'statusCode' => $status]
        );
    }
}
