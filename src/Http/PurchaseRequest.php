<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Http;

use EramDev\Pardakht\Money\Amount;

/**
 * Immutable DTO representing a purchase request to a gateway.
 */
final class PurchaseRequest
{
    /**
     * @param array<string, mixed> $extra Gateway-specific extra parameters.
     */
    public function __construct(
        private Amount $amount,
        private string $callbackUrl,
        private string $orderId,
        private string $description = '',
        private ?string $mobile = null,
        private ?string $email = null,
        private array $extra = [],
    ) {
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}
