<?php

declare(strict_types=1);

namespace Eram\Pardakht\Event;

use Eram\Pardakht\Contracts\TransactionInterface;

final class PaymentSettled
{
    public function __construct(
        public string $gatewayName,
        public TransactionInterface $transaction,
    ) {
    }
}
