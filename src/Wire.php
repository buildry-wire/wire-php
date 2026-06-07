<?php

declare(strict_types=1);

namespace BuildryWire\Wire;

use BuildryWire\Wire\Http\CurlHttpClient;
use BuildryWire\Wire\Http\HttpClient;
use BuildryWire\Wire\Resource\Charges;
use BuildryWire\Wire\Resource\Events;
use BuildryWire\Wire\Resource\PaymentIntents;
use BuildryWire\Wire\Resource\WebhookEndpoints;

/**
 * Wire is the API client. Construct it with an API key.
 *
 * The client never logs the key and never returns it in error messages.
 */
final class Wire
{
    public const DEFAULT_BASE_URL = 'https://api.wire.mn';

    private string $apiKey;
    private string $baseUrl;
    private float $timeoutSeconds;
    private int $maxRetries;
    private int $backoffMs;
    private HttpClient $http;
    /** @var callable(int): void Sleep function taking milliseconds; injectable for tests. */
    private $sleeper;

    public readonly PaymentIntents $paymentIntents;
    public readonly Charges $charges;
    public readonly Events $events;
    public readonly WebhookEndpoints $webhookEndpoints;
    public readonly Webhooks $webhooks;

    /**
     * @param array{
     *     baseURL?: string,
     *     timeout?: float,
     *     maxRetries?: int,
     *     backoffMs?: int,
     *     httpClient?: HttpClient,
     *     sleeper?: callable(int): void
     * } $opts
     */
    public function __construct(string $apiKey, array $opts = [])
    {
        $this->apiKey = $apiKey;
        $base = $opts['baseURL'] ?? self::DEFAULT_BASE_URL;
        $this->baseUrl = rtrim($base, '/');
        $this->timeoutSeconds = $opts['timeout'] ?? 30.0;
        $this->maxRetries = $opts['maxRetries'] ?? 2;
        $this->backoffMs = $opts['backoffMs'] ?? 500;
        $this->http = $opts['httpClient'] ?? new CurlHttpClient();
        $this->sleeper = $opts['sleeper'] ?? static function (int $ms): void {
            if ($ms > 0) {
                usleep($ms * 1000);
            }
        };

        $this->paymentIntents = new PaymentIntents($this);
        $this->charges = new Charges($this);
        $this->events = new Events($this);
        $this->webhookEndpoints = new WebhookEndpoints($this);
        $this->webhooks = new Webhooks();
    }

    /**
     * Perform an API request with retries, backoff, and idempotency handling.
     *
     * @param array<string, scalar|null> $query
     * @param array<string, mixed>|null  $body
     *
     * @return array<string, mixed>
     *
     * @throws WireError
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null, ?string $idempotencyKey = null): array
    {
        $url = $this->baseUrl . $path;
        $qs = $this->buildQuery($query);
        if ($qs !== '') {
            $url .= '?' . $qs;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ];

        $bodyStr = null;
        if ($body !== null) {
            $bodyStr = json_encode((object) $body, JSON_THROW_ON_ERROR);
            $headers['Content-Type'] = 'application/json';
        }

        if ($method === 'POST') {
            // Reuse the same Idempotency-Key across all retries of this call.
            $headers['Idempotency-Key'] = $idempotencyKey ?? self::newIdempotencyKey();
        }

        for ($attempt = 0; ; $attempt++) {
            try {
                $resp = $this->http->send($method, $url, $headers, $bodyStr, $this->timeoutSeconds);
            } catch (WireConnectionError $e) {
                if ($attempt < $this->maxRetries) {
                    ($this->sleeper)($this->backoffMs * (2 ** $attempt) + $this->jitterMs());
                    continue;
                }
                throw $e;
            }

            if (($resp->status === 429 || $resp->status >= 500) && $attempt < $this->maxRetries) {
                $retryAfter = self::parseRetryAfter($resp->header('retry-after'));
                $delay = $retryAfter > 0 ? $retryAfter : ($this->backoffMs * (2 ** $attempt) + $this->jitterMs());
                ($this->sleeper)($delay);
                continue;
            }

            if ($resp->status >= 200 && $resp->status < 300) {
                if ($resp->body === '') {
                    return [];
                }
                $decoded = json_decode($resp->body, true);
                return is_array($decoded) ? $decoded : [];
            }

            throw WireError::fromResponse($resp->status, $resp->body);
        }
    }

    /**
     * @param array<string, scalar|null> $query
     */
    private function buildQuery(array $query): string
    {
        $pairs = [];
        foreach ($query as $key => $value) {
            // Drop unset, empty-string, and zero values (mirrors reference SDK).
            if ($value === null || $value === '' || $value === 0) {
                continue;
            }
            $pairs[$key] = $value;
        }
        return http_build_query($pairs);
    }

    private function jitterMs(): int
    {
        // Full jitter up to one backoff base, to avoid thundering herds.
        return random_int(0, $this->backoffMs);
    }

    /**
     * Parse a Retry-After header (delta-seconds) into milliseconds.
     */
    private static function parseRetryAfter(?string $header): int
    {
        if ($header === null || $header === '') {
            return 0;
        }
        if (!preg_match('/^\d+$/', trim($header))) {
            return 0;
        }
        return ((int) $header) * 1000;
    }

    private static function newIdempotencyKey(): string
    {
        return 'idk_' . bin2hex(random_bytes(16));
    }
}
