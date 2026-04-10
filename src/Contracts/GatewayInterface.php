<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Contracts;

use EramDev\Pardakht\Http\PurchaseRequest;
use EramDev\Pardakht\Http\RedirectResponse;

interface GatewayInterface
{
    /**
     * Get the gateway display name.
     */
    public function getName(): string;

    /**
     * Initiate a purchase and get the redirect response.
     */
    public function purchase(PurchaseRequest $request): RedirectResponse;

    /**
     * Verify a payment after the callback from the gateway.
     * If no callback data is provided, auto-detects from $_POST or $_GET.
     *
     * @param array<string, mixed>|null $callbackData
     */
    public function verify(?array $callbackData = null): TransactionInterface;
}
