<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Http;

use BuildryWire\Wire\WireConnectionError;

/**
 * Default transport backed by ext-curl. No third-party dependencies.
 */
final class CurlHttpClient implements HttpClient
{
    public function send(string $method, string $url, array $headers, ?string $body, float $timeoutSeconds): HttpResponse
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new WireConnectionError('request failed: could not initialize curl');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $responseHeaders = [];
        $headerCallback = static function ($_ch, string $line) use (&$responseHeaders): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($line);
        };

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, $headerCallback);
        // Use millisecond timeout so sub-second values are honored.
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int) round($timeoutSeconds * 1000));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, (int) round($timeoutSeconds * 1000));

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $message = curl_error($ch);
            curl_close($ch);
            throw new WireConnectionError('request failed: ' . $message);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return new HttpResponse($status, is_string($raw) ? $raw : '', $responseHeaders);
    }
}
