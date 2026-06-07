<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Resource;

use BuildryWire\Wire\Wire;

/**
 * Lazily yields every item across pages, following `has_more` via
 * `starting_after`. Each raw item is mapped through the supplied factory.
 *
 * @template T
 * @implements \IteratorAggregate<int, T>
 */
final class AutoPagingIterator implements \IteratorAggregate
{
    /**
     * @param callable(array<string, mixed>): T $factory
     */
    public function __construct(
        private readonly Wire $client,
        private readonly string $path,
        private readonly int $limit,
        private readonly string $startingAfter,
        private $factory
    ) {
    }

    /**
     * @return \Generator<int, T>
     */
    public function getIterator(): \Generator
    {
        $after = $this->startingAfter;
        for (;;) {
            $page = $this->client->request('GET', $this->path, [
                'limit' => $this->limit > 0 ? $this->limit : null,
                'starting_after' => $after !== '' ? $after : null,
            ]);

            $data = is_array($page['data'] ?? null) ? $page['data'] : [];
            foreach ($data as $item) {
                if (is_array($item) && isset($item['id'])) {
                    $after = (string) $item['id'];
                }
                yield ($this->factory)(is_array($item) ? $item : []);
            }

            $hasMore = (bool) ($page['has_more'] ?? false);
            if (!$hasMore || count($data) === 0) {
                return;
            }
        }
    }

    /**
     * Materialize all items into an array. Use with care on large result sets.
     *
     * @return array<int, T>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }
}
