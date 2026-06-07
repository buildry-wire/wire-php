<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Resource;

use BuildryWire\Wire\Model\DeletedObject;
use BuildryWire\Wire\Model\WebhookEndpoint;
use BuildryWire\Wire\Wire;

final class WebhookEndpoints
{
    public function __construct(private readonly Wire $client)
    {
    }

    /**
     * @param array<string, mixed> $params Body fields (url, enabled_events, ...).
     */
    public function create(array $params, ?string $idempotencyKey = null): WebhookEndpoint
    {
        $data = $this->client->request('POST', '/v1/webhook_endpoints', [], $params, $idempotencyKey);
        return WebhookEndpoint::fromArray($data);
    }

    public function retrieve(string $id): WebhookEndpoint
    {
        $data = $this->client->request('GET', '/v1/webhook_endpoints/' . rawurlencode($id));
        return WebhookEndpoint::fromArray($data);
    }

    /**
     * @param array<string, mixed> $params Body fields (url, enabled_events, status, ...).
     */
    public function update(string $id, array $params, ?string $idempotencyKey = null): WebhookEndpoint
    {
        $data = $this->client->request('POST', '/v1/webhook_endpoints/' . rawurlencode($id), [], $params, $idempotencyKey);
        return WebhookEndpoint::fromArray($data);
    }

    public function delete(string $id): DeletedObject
    {
        $data = $this->client->request('DELETE', '/v1/webhook_endpoints/' . rawurlencode($id));
        return DeletedObject::fromArray($data);
    }

    /**
     * @return AutoPagingIterator<WebhookEndpoint>
     */
    public function list(int $limit = 0, string $startingAfter = ''): AutoPagingIterator
    {
        return new AutoPagingIterator(
            $this->client,
            '/v1/webhook_endpoints',
            $limit,
            $startingAfter,
            static fn (array $item): WebhookEndpoint => WebhookEndpoint::fromArray($item)
        );
    }
}
