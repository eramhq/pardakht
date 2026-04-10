<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway;

use EramDev\Pardakht\Contracts\GatewayInterface;
use EramDev\Pardakht\Contracts\TransactionInterface;
use EramDev\Pardakht\Exception\ConnectionException;
use EramDev\Pardakht\Http\PurchaseRequest;
use EramDev\Pardakht\Http\RedirectResponse;
use EramDev\Pardakht\Http\SoapClientFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base class for SOAP-based bank payment gateways (Mellat, Parsian, Sadad, etc.).
 */
abstract class AbstractSoapGateway implements GatewayInterface
{
    use GatewayHelperTrait;

    protected SoapClientFactory $soapFactory;
    protected LoggerInterface $logger;
    private ?\SoapClient $client = null;

    public function __construct(
        ?SoapClientFactory $soapFactory = null,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null,
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
     * Auto-detect callback data from the current request.
     *
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
