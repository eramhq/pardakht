<?php

declare(strict_types=1);

namespace Eram\Pardakht\Event;

use Eram\Pardakht\Http\PurchaseRequest;

final class PurchaseInitiated
{
    public function __construct(
        public string $gatewayName,
        public PurchaseRequest $request,
    ) {
    }
}
