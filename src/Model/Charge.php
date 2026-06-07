<?php

declare(strict_types=1);

namespace BuildryWire\Wire\Model;

final class Charge extends WireObject
{
    public string $id;
    public string $object;
    public ?string $paymentIntent;
    public ?string $operator;
    public ?string $operatorChargeId;
    public ?string $status;
    public int $amount;
    public int $fee;
    public int $amountRefunded;
    public ?string $failureCode;
    public ?string $failureMessage;
    public bool $livemode;
    public ?int $created;

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $c = new self($data);
        $c->id = (string) ($data['id'] ?? '');
        $c->object = (string) ($data['object'] ?? 'charge');
        $c->paymentIntent = isset($data['payment_intent']) ? (string) $data['payment_intent'] : null;
        $c->operator = isset($data['operator']) ? (string) $data['operator'] : null;
        $c->operatorChargeId = isset($data['operator_charge_id']) ? (string) $data['operator_charge_id'] : null;
        $c->status = isset($data['status']) ? (string) $data['status'] : null;
        $c->amount = (int) ($data['amount'] ?? 0);
        $c->fee = (int) ($data['fee'] ?? 0);
        $c->amountRefunded = (int) ($data['amount_refunded'] ?? 0);
        $c->failureCode = isset($data['failure_code']) ? (string) $data['failure_code'] : null;
        $c->failureMessage = isset($data['failure_message']) ? (string) $data['failure_message'] : null;
        $c->livemode = (bool) ($data['livemode'] ?? false);
        $c->created = isset($data['created']) ? (int) $data['created'] : null;
        return $c;
    }
}
