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
use Eram\Pardakht\Http\CurlHttpClient;
use Eram\Pardakht\Http\EventDispatcher;
use Eram\Pardakht\Http\HttpClient;
use Eram\Pardakht\Http\Logger;
use Eram\Pardakht\Http\NullLogger;
use Eram\Pardakht\Http\SoapClientFactory;

/**
 * Main entry point for the Pardakht payment library.
 *
 * Usage (zero-config):
 *   $pardakht = new Pardakht();
 *   $gateway = $pardakht->create('zarinpal', new ZarinpalConfig('merchant-id'));
 *   $response = $gateway->purchase($request);
 */
final class Pardakht
{
    private HttpClient $httpClient;
    private Logger $logger;
    private ?EventDispatcher $eventDispatcher;
    private ?SoapClientFactory $soapFactory;

    /**
     * All parameters are optional — defaults use native ext-curl and ext-soap.
     */
    public function __construct(
        ?HttpClient $httpClient = null,
        ?Logger $logger = null,
        ?EventDispatcher $eventDispatcher = null,
        ?SoapClientFactory $soapFactory = null,
    ) {
        $this->httpClient = $httpClient ?? new CurlHttpClient();
        $this->logger = $logger ?? new NullLogger();
        $this->eventDispatcher = $eventDispatcher;
        $this->soapFactory = $soapFactory;
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
                $this->soapFactory(),
                $this->logger,
                $this->eventDispatcher,
            ),
            'saman' => new SamanGateway(
                self::ensure($config, SamanConfig::class),
                $this->soapFactory(),
                $this->logger,
                $this->eventDispatcher,
            ),
            'parsian' => new ParsianGateway(
                self::ensure($config, ParsianConfig::class),
                $this->soapFactory(),
                $this->logger,
                $this->eventDispatcher,
            ),
            'sadad' => new SadadGateway(
                self::ensure($config, SadadConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'pasargad' => new PasargadGateway(
                self::ensure($config, PasargadConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'zarinpal' => new ZarinpalGateway(
                self::ensure($config, ZarinpalConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'idpay' => new IDPayGateway(
                self::ensure($config, IDPayConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'zibal' => new ZibalGateway(
                self::ensure($config, ZibalConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'payir' => new PayIrGateway(
                self::ensure($config, PayIrConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'nextpay' => new NextPayGateway(
                self::ensure($config, NextPayConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'vandar' => new VandarGateway(
                self::ensure($config, VandarConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            'sizpay' => new SizpayGateway(
                self::ensure($config, SizpayConfig::class),
                $this->httpClient,
                $this->logger,
                $this->eventDispatcher,
            ),
            default => throw new \InvalidArgumentException(
                \sprintf('Unknown gateway "%s". Available: %s', $gateway, implode(', ', self::available())),
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

    private function soapFactory(): SoapClientFactory
    {
        return $this->soapFactory ??= new SoapClientFactory();
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
}
