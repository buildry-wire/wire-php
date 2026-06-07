<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Model;

final class WebhookEndpoint extends WireObject
{
    public string $id;
    public string $object;
    public ?string $url;
    /** @var array<int, string> */
    public array $enabledEvents;
    public ?string $status;
    public ?string $secret;
    public bool $livemode;
    public ?int $created;

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $w = new self($data);
        $w->id = (string) ($data['id'] ?? '');
        $w->object = (string) ($data['object'] ?? 'webhook_endpoint');
        $w->url = isset($data['url']) ? (string) $data['url'] : null;
        $w->enabledEvents = is_array($data['enabled_events'] ?? null) ? $data['enabled_events'] : [];
        $w->status = isset($data['status']) ? (string) $data['status'] : null;
        $w->secret = isset($data['secret']) ? (string) $data['secret'] : null;
        $w->livemode = (bool) ($data['livemode'] ?? false);
        $w->created = isset($data['created']) ? (int) $data['created'] : null;
        return $w;
    }
}
