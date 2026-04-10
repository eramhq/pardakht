<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Event;

use EramDev\Pardakht\Http\PurchaseRequest;

final class PurchaseInitiated
{
    public function __construct(
        public string $gatewayName,
        public PurchaseRequest $request,
    ) {
    }
}
