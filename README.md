# Pardakht

A unified, type-safe, zero-dependency PHP library for Iranian payment gateways.

[![Tests](https://github.com/eramhq/pardakht/actions/workflows/tests.yml/badge.svg)](https://github.com/eramhq/pardakht/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net/)

**Documentation:** [English](docs/en/README.md) | [فارسی](docs/fa/README.md)

## What is Pardakht?

Pardakht (پرداخت, "payment" in Farsi) is an omni-gateway PHP library that gives you a single, consistent API across **all major Iranian payment providers** — bank gateways (SOAP) and modern payment services (REST) alike. Write your payment logic once, swap the gateway with one line of config.

**Why Pardakht?**

- **Zero Composer dependencies** — relies only on standard PHP extensions (`ext-curl`, `ext-json`, `ext-openssl`, `ext-soap`). No Guzzle, no framework coupling, no supply-chain risk.
- **Rial/Toman safety** — the `Amount` value object eliminates the 10x conversion bugs that plague Iranian e-commerce.
- **One interface, every gateway** — `purchase()` → `verify()` → optionally `settle()`. Same flow whether it's Zarinpal or Mellat.
- **Framework-agnostic** — works with Laravel, Symfony, or plain PHP. Plug in your own HTTP client, logger, or event dispatcher.
- **Fully tested** — unit tests for every gateway, PHPStan static analysis, PER-CS2 code style.

## Install

```bash
composer require eram/pardakht
```

## Supported Gateways

| Alias | Gateway | Protocol | Settlement | Docs |
|-------|---------|----------|------------|------|
| `zarinpal` | Zarinpal | REST | Auto | [Guide](docs/en/gateways/zarinpal.md) |
| `mellat` | Mellat (Behpardakht) | SOAP | **Required** | [Guide](docs/en/gateways/mellat.md) |
| `saman` | Saman (Sep) | SOAP | Auto | [Guide](docs/en/gateways/saman.md) |
| `parsian` | Parsian (Pec) | SOAP | **Required** | [Guide](docs/en/gateways/parsian.md) |
| `sadad` | Sadad (Bank Melli) | REST | Auto | [Guide](docs/en/gateways/sadad.md) |
| `pasargad` | Pasargad | REST | Auto | [Guide](docs/en/gateways/pasargad.md) |
| `idpay` | IDPay | REST | Auto | [Guide](docs/en/gateways/idpay.md) |
| `zibal` | Zibal | REST | Auto | [Guide](docs/en/gateways/zibal.md) |
| `payir` | Pay.ir | REST | Auto | [Guide](docs/en/gateways/payir.md) |
| `nextpay` | NextPay | REST | Auto | [Guide](docs/en/gateways/nextpay.md) |
| `vandar` | Vandar | REST | Auto | [Guide](docs/en/gateways/vandar.md) |
| `sizpay` | Sizpay | REST | Auto | [Guide](docs/en/gateways/sizpay.md) |

## Quick Start

The flow is always: **create → purchase → redirect → verify** (and optionally **settle** for bank gateways).

See the [Getting Started](docs/en/getting-started.md) guide for a complete walkthrough with code examples.

## Documentation

Full documentation with API reference, cookbook, gateway guides, and more:

- [English Documentation](docs/en/README.md)
- [مستندات فارسی](docs/fa/README.md)

## About Eram

[Eram](https://github.com/eramhq) is a small engineering team building open-source developer tools for the Persian ecosystem. Our projects — [pardakht](https://github.com/eramhq/pardakht), [daynum](https://github.com/eramhq/daynum), [persian-kit](https://github.com/eramhq/persian-kit) — solve the everyday problems that Iranian developers run into: payment integration, calendar conversion, and string/number localization. Everything we ship is MIT-licensed, zero-dependency where possible, and built to be boring infrastructure you never have to think about.

## License

[MIT](LICENSE)
