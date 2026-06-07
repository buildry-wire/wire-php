<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Resource;

use BuildryWire\Wire\Model\PaymentIntent;
use BuildryWire\Wire\Wire;

final class PaymentIntents
{
    public function __construct(private readonly Wire $client)
    {
    }

    /**
     * @param array<string, mixed> $params Body fields (amount, currency, allowed_operators, ...).
     */
    public function create(array $params, ?string $idempotencyKey = null): PaymentIntent
    {
        $data = $this->client->request('POST', '/v1/payment_intents', [], $params, $idempotencyKey);
        return PaymentIntent::fromArray($data);
    }

    public function retrieve(string $id): PaymentIntent
    {
        $data = $this->client->request('GET', '/v1/payment_intents/' . rawurlencode($id));
        return PaymentIntent::fromArray($data);
    }

    /**
     * @param array<string, mixed> $params Body fields (e.g. return_url).
     */
    public function confirm(string $id, array $params = [], ?string $idempotencyKey = null): PaymentIntent
    {
        $data = $this->client->request('POST', '/v1/payment_intents/' . rawurlencode($id) . '/confirm', [], $params, $idempotencyKey);
        return PaymentIntent::fromArray($data);
    }

    public function cancel(string $id, ?string $idempotencyKey = null): PaymentIntent
    {
        $data = $this->client->request('POST', '/v1/payment_intents/' . rawurlencode($id) . '/cancel', [], [], $idempotencyKey);
        return PaymentIntent::fromArray($data);
    }

    /**
     * @return AutoPagingIterator<PaymentIntent>
     */
    public function list(int $limit = 0, string $startingAfter = ''): AutoPagingIterator
    {
        return new AutoPagingIterator(
            $this->client,
            '/v1/payment_intents',
            $limit,
            $startingAfter,
            static fn (array $item): PaymentIntent => PaymentIntent::fromArray($item)
        );
    }
}
