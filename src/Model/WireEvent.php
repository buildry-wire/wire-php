<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Model;

final class WireEvent extends WireObject
{
    public string $id;
    public string $object;
    public string $type;
    public ?string $apiVersion;
    /** @var array<string, mixed> */
    public array $data;
    public bool $livemode;
    public ?int $created;

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $e = new self($data);
        $e->id = (string) ($data['id'] ?? '');
        $e->object = (string) ($data['object'] ?? 'event');
        $e->type = (string) ($data['type'] ?? '');
        $e->apiVersion = isset($data['api_version']) ? (string) $data['api_version'] : null;
        $e->data = is_array($data['data'] ?? null) ? $data['data'] : [];
        $e->livemode = (bool) ($data['livemode'] ?? false);
        $e->created = isset($data['created']) ? (int) $data['created'] : null;
        return $e;
    }
}
