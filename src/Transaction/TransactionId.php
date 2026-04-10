<?php

declare(strict_types=1);

namespace Eram\Pardakht\Transaction;

/**
 * Value object wrapping a gateway-specific transaction identifier.
 */
final class TransactionId
{
    public function __construct(
        private string $value,
    ) {
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
