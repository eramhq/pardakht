<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway;

use Eram\Pardakht\Event\PaymentFailed;
use Eram\Pardakht\Exception\GatewayException;
use Eram\Pardakht\Exception\VerificationException;
use Eram\Pardakht\Http\EventDispatcher;

trait GatewayHelperTrait
{
    protected ?EventDispatcher $eventDispatcher = null;

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

    /**
     * @param array<string, mixed>|null $callbackData
     * @return array<string, mixed>
     */
    protected function resolveCallbackData(?array $callbackData): array
    {
        if ($callbackData !== null) {
            return $callbackData;
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        return $_GET;
    }
}
