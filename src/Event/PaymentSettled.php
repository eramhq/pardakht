<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Event;

use EramDev\Pardakht\Contracts\TransactionInterface;

final class PaymentSettled
{
    public function __construct(
        public string $gatewayName,
        public TransactionInterface $transaction,
    ) {
    }
}
