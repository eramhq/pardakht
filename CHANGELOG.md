# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [1.0.0-beta.1] - 2026-04-12

### Added
- Core contracts: `GatewayInterface`, `SupportsSettlement`, `SupportsRefund`, `TransactionInterface`
- Money value objects: `Amount` (Rial/Toman safe), `Currency` enum
- Banking utilities: `Sheba` (IBAN validation), `CardNumber` (Luhn + bank detection), `BankIdentifier`
- Transaction DTOs: `Transaction`, `TransactionStatus`, `TransactionId`
- SOAP gateways: Mellat (Behpardakht), Saman (Sep), Parsian (Pec)
- REST gateways: Sadad (Bank Melli), Pasargad, Zarinpal, IDPay, Zibal, Pay.ir, NextPay, Vandar, Sizpay
- PSR-14 event system: `PurchaseInitiated`, `CallbackReceived`, `PaymentVerified`, `PaymentSettled`, `PaymentFailed`
- Exception hierarchy with gateway-specific error codes
- Auto-submit HTML form generation for POST-based gateways
- `Pardakht` manager class with gateway factory

[1.0.0-beta.1]: https://github.com/eramhq/pardakht/releases/tag/v1.0.0-beta.1
