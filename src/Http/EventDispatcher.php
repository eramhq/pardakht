<?php

declare(strict_types=1);

namespace Eram\Pardakht\Http;

/**
 * Minimal event dispatcher contract.
 *
 * Implementations receive payment lifecycle events (PurchaseInitiated,
 * CallbackReceived, PaymentVerified, PaymentSettled, PaymentFailed) and
 * can dispatch them to registered listeners.
 */
interface EventDispatcher
{
    /**
     * Dispatch an event. Returns the event (possibly mutated by listeners).
     */
    public function dispatch(object $event): object;
}
