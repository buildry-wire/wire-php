<?php

declare(strict_types=1);

namespace BuildryWire\Wire;

/**
 * Thrown when a webhook signature does not verify.
 */
final class SignatureVerificationError extends \Exception
{
}
