<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Contracts;

use EramDev\Pardakht\Money\Amount;

/**
 * Implemented by gateways that support refunding payments.
 */
interface SupportsRefund
{
    /**
     * Refund a settled payment, either fully or partially.
     */
    public function refund(TransactionInterface $transaction, ?Amount $amount = null): TransactionInterface;
}
