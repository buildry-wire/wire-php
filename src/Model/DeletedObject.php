<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Model;

final class DeletedObject extends WireObject
{
    public string $id;
    public string $object;
    public bool $deleted;

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $d = new self($data);
        $d->id = (string) ($data['id'] ?? '');
        $d->object = (string) ($data['object'] ?? '');
        $d->deleted = (bool) ($data['deleted'] ?? false);
        return $d;
    }
}
