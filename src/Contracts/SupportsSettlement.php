<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Contracts;

/**
 * Implemented by gateways that require a separate settlement step after verification.
 *
 * Iranian bank gateways like Mellat, Parsian, and Sadad require this.
 * If not settled within the gateway's timeout window (typically 15-30 minutes),
 * the payment is automatically reversed.
 */
interface SupportsSettlement
{
    /**
     * Settle/finalize a verified payment.
     */
    public function settle(TransactionInterface $transaction): TransactionInterface;
}
