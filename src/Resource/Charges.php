<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Resource;

use BuildryWire\Wire\Model\Charge;
use BuildryWire\Wire\Wire;

final class Charges
{
    public function __construct(private readonly Wire $client)
    {
    }

    public function retrieve(string $id): Charge
    {
        $data = $this->client->request('GET', '/v1/charges/' . rawurlencode($id));
        return Charge::fromArray($data);
    }

    /**
     * @return AutoPagingIterator<Charge>
     */
    public function list(int $limit = 0, string $startingAfter = ''): AutoPagingIterator
    {
        return new AutoPagingIterator(
            $this->client,
            '/v1/charges',
            $limit,
            $startingAfter,
            static fn (array $item): Charge => Charge::fromArray($item)
        );
    }
}
