<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Resource;

use BuildryWire\Wire\Model\WireEvent;
use BuildryWire\Wire\Wire;

final class Events
{
    public function __construct(private readonly Wire $client)
    {
    }

    public function retrieve(string $id): WireEvent
    {
        $data = $this->client->request('GET', '/v1/events/' . rawurlencode($id));
        return WireEvent::fromArray($data);
    }

    /**
     * @return AutoPagingIterator<WireEvent>
     */
    public function list(int $limit = 0, string $startingAfter = ''): AutoPagingIterator
    {
        return new AutoPagingIterator(
            $this->client,
            '/v1/events',
            $limit,
            $startingAfter,
            static fn (array $item): WireEvent => WireEvent::fromArray($item)
        );
    }
}
