<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway;

use EramDev\Pardakht\Event\PaymentFailed;
use EramDev\Pardakht\Exception\GatewayException;
use EramDev\Pardakht\Exception\VerificationException;
use Psr\EventDispatcher\EventDispatcherInterface;

trait GatewayHelperTrait
{
    protected ?EventDispatcherInterface $eventDispatcher = null;

    protected function dispatch(object $event): void
    {
        $this->eventDispatcher?->dispatch($event);
    }

    protected function failPurchase(string $message, int|string $code = 0): never
    {
        $this->dispatch(new PaymentFailed($this->getName(), $message, $code));

        throw new GatewayException($message, $this->getName(), $code);
    }

    protected function failVerification(string $message, int|string $code = 0): never
    {
        $this->dispatch(new PaymentFailed($this->getName(), $message, $code));

        throw new VerificationException($message, $this->getName(), $code);
    }

    protected function nullIfEmpty(string $value): ?string
    {
        return $value !== '' ? $value : null;
    }
}
