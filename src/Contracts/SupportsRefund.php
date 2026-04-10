<?php

declare(strict_types=1);

namespace Eram\Pardakht\Contracts;

use Eram\Pardakht\Money\Amount;

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
