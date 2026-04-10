<?php

declare(strict_types=1);

namespace Eram\Pardakht\Exception;

/**
 * Thrown when a gateway returns an error response.
 */
class GatewayException extends PardakhtException
{
    public function __construct(
        string $message,
        private readonly string $gatewayName,
        private readonly int|string $errorCode = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, \is_int($errorCode) ? $errorCode : 0, $previous);
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function getErrorCode(): int|string
    {
        return $this->errorCode;
    }
}
