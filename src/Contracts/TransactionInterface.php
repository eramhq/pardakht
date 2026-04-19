<?php

declare(strict_types=1);

namespace Eram\Pardakht\Contracts;

use Eram\Abzar\Money\Amount;
use Eram\Pardakht\Transaction\TransactionId;
use Eram\Pardakht\Transaction\TransactionStatus;

interface TransactionInterface
{
    public function getId(): TransactionId;

    public function getGatewayName(): string;

    public function getAmount(): Amount;

    public function getStatus(): TransactionStatus;

    /**
     * The reference ID returned by the gateway (e.g., RefId, Authority).
     */
    public function getReferenceId(): string;

    /**
     * The tracking code for the end user.
     */
    public function getTrackingCode(): ?string;

    /**
     * The card number used for payment (masked or full, depending on gateway).
     */
    public function getCardNumber(): ?string;

    /**
     * Any extra data returned by the gateway.
     *
     * @return array<string, mixed>
     */
    public function getExtra(): array;

    public function withStatus(TransactionStatus $status): static;
}
