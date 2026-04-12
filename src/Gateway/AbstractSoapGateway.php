<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway;

use Eram\Pardakht\Contracts\GatewayInterface;
use Eram\Pardakht\Contracts\TransactionInterface;
use Eram\Pardakht\Exception\ConnectionException;
use Eram\Pardakht\Http\EventDispatcher;
use Eram\Pardakht\Http\Logger;
use Eram\Pardakht\Http\NullLogger;
use Eram\Pardakht\Http\PurchaseRequest;
use Eram\Pardakht\Http\RedirectResponse;
use Eram\Pardakht\Http\SoapClientFactory;

/**
 * Base class for SOAP-based bank payment gateways (Mellat, Parsian, Sadad, etc.).
 */
abstract class AbstractSoapGateway implements GatewayInterface
{
    use GatewayHelperTrait;

    protected SoapClientFactory $soapFactory;
    protected Logger $logger;
    private ?\SoapClient $client = null;

    public function __construct(
        ?SoapClientFactory $soapFactory = null,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
    ) {
        $this->soapFactory = $soapFactory ?? new SoapClientFactory();
        $this->logger = $logger ?? new NullLogger();
        $this->eventDispatcher = $eventDispatcher;
    }

    abstract public function getName(): string;

    abstract public function purchase(PurchaseRequest $request): RedirectResponse;

    /**
     * @param array<string, mixed>|null $callbackData
     */
    abstract public function verify(?array $callbackData = null): TransactionInterface;

    /**
     * Get the WSDL URL for this gateway.
     */
    abstract protected function getWsdlUrl(): string;

    /**
     * Get or create the SoapClient instance.
     */
    protected function getSoapClient(): \SoapClient
    {
        if ($this->client === null) {
            $this->client = $this->soapFactory->create($this->getWsdlUrl());
        }

        return $this->client;
    }

    /**
     * Call a SOAP method with error handling.
     *
     * @param array<string, mixed> $params
     */
    protected function callSoap(string $method, array $params): mixed
    {
        $this->logger->debug('Pardakht: SOAP call', [
            'gateway' => $this->getName(),
            'method' => $method,
        ]);

        try {
            $client = $this->getSoapClient();

            return $client->__soapCall($method, [$params]);
        } catch (\SoapFault $e) {
            $this->client = null;

            throw new ConnectionException(
                \sprintf(
                    'SOAP call %s::%s failed: %s',
                    $this->getName(),
                    $method,
                    $e->getMessage(),
                ),
                0,
                $e,
            );
        }
    }
}
