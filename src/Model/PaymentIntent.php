<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Model;

final class PaymentIntent extends WireObject
{
    public string $id;
    public string $object;
    public int $amount;
    public ?string $currency;
    public ?string $status;
    public ?string $clientSecret;
    public bool $automaticOperator;
    /** @var array<int, string> */
    public array $allowedOperators;
    public ?string $selectedOperator;
    /** @var array<string, mixed>|null */
    public ?array $nextAction;
    /** @var array<string, string> */
    public array $metadata;
    public bool $livemode;
    public ?int $created;
    public ?int $expiresAt;

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $pi = new self($data);
        $pi->id = (string) ($data['id'] ?? '');
        $pi->object = (string) ($data['object'] ?? 'payment_intent');
        $pi->amount = (int) ($data['amount'] ?? 0);
        $pi->currency = isset($data['currency']) ? (string) $data['currency'] : null;
        $pi->status = isset($data['status']) ? (string) $data['status'] : null;
        $pi->clientSecret = isset($data['client_secret']) ? (string) $data['client_secret'] : null;
        $pi->automaticOperator = (bool) ($data['automatic_operator'] ?? false);
        $pi->allowedOperators = is_array($data['allowed_operators'] ?? null) ? $data['allowed_operators'] : [];
        $pi->selectedOperator = isset($data['selected_operator']) ? (string) $data['selected_operator'] : null;
        $pi->nextAction = is_array($data['next_action'] ?? null) ? $data['next_action'] : null;
        $pi->metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $pi->livemode = (bool) ($data['livemode'] ?? false);
        $pi->created = isset($data['created']) ? (int) $data['created'] : null;
        $pi->expiresAt = isset($data['expires_at']) ? (int) $data['expires_at'] : null;
        return $pi;
    }
}
