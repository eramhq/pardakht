<?php

declare(strict_types=1);

namespace Eram\Pardakht;

use Eram\Pardakht\Contracts\GatewayInterface;
use Eram\Pardakht\Gateway\IDPay\IDPayConfig;
use Eram\Pardakht\Gateway\IDPay\IDPayGateway;
use Eram\Pardakht\Gateway\Mellat\MellatConfig;
use Eram\Pardakht\Gateway\Mellat\MellatGateway;
use Eram\Pardakht\Gateway\NextPay\NextPayConfig;
use Eram\Pardakht\Gateway\NextPay\NextPayGateway;
use Eram\Pardakht\Gateway\Parsian\ParsianConfig;
use Eram\Pardakht\Gateway\Parsian\ParsianGateway;
use Eram\Pardakht\Gateway\Pasargad\PasargadConfig;
use Eram\Pardakht\Gateway\Pasargad\PasargadGateway;
use Eram\Pardakht\Gateway\PayIr\PayIrConfig;
use Eram\Pardakht\Gateway\PayIr\PayIrGateway;
use Eram\Pardakht\Gateway\Sadad\SadadConfig;
use Eram\Pardakht\Gateway\Sadad\SadadGateway;
use Eram\Pardakht\Gateway\Saman\SamanConfig;
use Eram\Pardakht\Gateway\Saman\SamanGateway;
use Eram\Pardakht\Gateway\Sizpay\SizpayConfig;
use Eram\Pardakht\Gateway\Sizpay\SizpayGateway;
use Eram\Pardakht\Gateway\Vandar\VandarConfig;
use Eram\Pardakht\Gateway\Vandar\VandarGateway;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalConfig;
use Eram\Pardakht\Gateway\Zarinpal\ZarinpalGateway;
use Eram\Pardakht\Gateway\Zibal\ZibalConfig;
use Eram\Pardakht\Gateway\Zibal\ZibalGateway;
use Eram\Pardakht\Http\SoapClientFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Main entry point for the Pardakht payment library.
 *
 * Usage (zero-config with Guzzle installed):
 *   $pardakht = new Pardakht();
 *   $gateway = $pardakht->create('zarinpal', new ZarinpalConfig('merchant-id'));
 *   $response = $gateway->purchase($request);
 */
final class Pardakht
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private ?LoggerInterface $logger;
    private ?EventDispatcherInterface $eventDispatcher;
    private SoapClientFactory $soapFactory;

    /**
     * All parameters are optional — Guzzle is auto-discovered if installed.
     */
    public function __construct(
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?SoapClientFactory $soapFactory = null,
    ) {
        if ($httpClient === null || $requestFactory === null || $streamFactory === null) {
            self::assertGuzzleInstalled();
            /** @var \GuzzleHttp\Psr7\HttpFactory $factory */
            $factory = new \GuzzleHttp\Psr7\HttpFactory();
        }

        $this->httpClient = $httpClient ?? new \GuzzleHttp\Client();
        $this->requestFactory = $requestFactory ?? $factory;
        $this->streamFactory = $streamFactory ?? $factory;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->soapFactory = $soapFactory ?? new SoapClientFactory();
    }

    /**
     * Create a gateway instance by name.
     *
     * @param object $config Gateway-specific config DTO (e.g., ZarinpalConfig, MellatConfig).
     */
    public function create(string $gateway, object $config): GatewayInterface
    {
        return match ($gateway) {
            'mellat' => new MellatGateway(
                self::ensure($config, MellatConfig::class),
                $this->soapFactory, $this->logger, $this->eventDispatcher,
            ),
            'saman' => new SamanGateway(
                self::ensure($config, SamanConfig::class),
                $this->soapFactory, $this->logger, $this->eventDispatcher,
            ),
            'parsian' => new ParsianGateway(
                self::ensure($config, ParsianConfig::class),
                $this->soapFactory, $this->logger, $this->eventDispatcher,
            ),
            'sadad' => new SadadGateway(
                self::ensure($config, SadadConfig::class),
                $this->httpClient, $this->requestFactory, $this->streamFactory,
                $this->logger, $this->eventDispatcher,
            ),
            'pasargad' => new PasargadGateway(
                self::ensure($config, PasargadConfig::class),
                $this->httpClient, $this->requestFactory, $this->streamFactory,
                $this->logger, $this->eventDispatcher,
            ),
            'zarinpal' => new ZarinpalGateway(
                self::ensure($config, ZarinpalConfig::class),
                $this->httpClient, $this->requestFactory, $this->streamFactory,
                $this->logger, $this->eventDispatcher,
            ),
            'idpay' => new IDPayGateway(
                self::ensure($config, IDPayConfig::class),
                $this->httpClient, $this->requestFactory, $this->streamFactory,
                $this->logger, $this->eventDispatcher,
            ),
            'zibal' => new ZibalGateway(
                self::ensure($config, ZibalConfig::class),
                $this->httpClient, $this->requestFactory, $this->streamFactory,
                $this->logger, $this->eventDispatcher,
            ),
            'payir' => new PayIrGateway(
                self::ensure($config, PayIrConfig::class),
                $this->httpClient, $this->requestFactory, $this->streamFactory,
                $this->logger, $this->eventDispatcher,
            ),
            'nextpay' => new NextPayGateway(
                self::ensure($config, NextPayConfig::class),
                $this->httpClient, $this->requestFactory, $this->streamFactory,
                $this->logger, $this->eventDispatcher,
            ),
            'vandar' => new VandarGateway(
                self::ensure($config, VandarConfig::class),
                $this->httpClient, $this->requestFactory, $this->streamFactory,
                $this->logger, $this->eventDispatcher,
            ),
            'sizpay' => new SizpayGateway(
                self::ensure($config, SizpayConfig::class),
                $this->httpClient, $this->requestFactory, $this->streamFactory,
                $this->logger, $this->eventDispatcher,
            ),
            default => throw new \InvalidArgumentException(
                \sprintf('Unknown gateway "%s". Available: %s', $gateway, \implode(', ', self::available())),
            ),
        };
    }

    /**
     * @return list<string>
     */
    public static function available(): array
    {
        return [
            'mellat', 'saman', 'parsian', 'sadad', 'pasargad',
            'zarinpal', 'idpay', 'zibal', 'payir', 'nextpay', 'vandar', 'sizpay',
        ];
    }

    /**
     * @template T of object
     * @param class-string<T> $expected
     * @return T
     */
    private static function ensure(object $config, string $expected): object
    {
        if (!$config instanceof $expected) {
            throw new \InvalidArgumentException(
                \sprintf('Expected %s, got %s', $expected, $config::class),
            );
        }

        return $config;
    }

    private static function assertGuzzleInstalled(): void
    {
        if (!\class_exists(\GuzzleHttp\Client::class)) {
            throw new \RuntimeException(
                'No PSR-18 HTTP client provided. Install Guzzle (composer require guzzlehttp/guzzle guzzlehttp/psr7) '
                . 'or pass your own ClientInterface, RequestFactoryInterface, and StreamFactoryInterface.',
            );
        }
    }
}
