<?php

declare(strict_types=1);

namespace Eram\Pardakht\Transaction;

use Eram\Pardakht\Contracts\TransactionInterface;
use Eram\Pardakht\Money\Amount;

final class Transaction implements TransactionInterface
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        private TransactionId $id,
        private string $gatewayName,
        private Amount $amount,
        private TransactionStatus $status,
        private string $referenceId,
        private ?string $trackingCode = null,
        private ?string $cardNumber = null,
        private array $extra = [],
    ) {}

    public function getId(): TransactionId
    {
        return $this->id;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getStatus(): TransactionStatus
    {
        return $this->status;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function getTrackingCode(): ?string
    {
        return $this->trackingCode;
    }

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    public function withStatus(TransactionStatus $status): static
    {
        return new self(
            $this->id,
            $this->gatewayName,
            $this->amount,
            $status,
            $this->referenceId,
            $this->trackingCode,
            $this->cardNumber,
            $this->extra,
        );
    }

    public function withTrackingCode(string $trackingCode): self
    {
        return new self(
            $this->id,
            $this->gatewayName,
            $this->amount,
            $this->status,
            $this->referenceId,
            $trackingCode,
            $this->cardNumber,
            $this->extra,
        );
    }
}
