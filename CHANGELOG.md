# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

## [1.0.0-beta.2] - 2026-04-19

### Changed
- Now depends on [`eram/abzar`](https://github.com/eramhq/abzar-php) `^0.6@beta` for shared Iranian utilities.
- `Eram\Pardakht\Money\Amount` → **moved** to `Eram\Abzar\Money\Amount`. Update imports.

### Removed
- `Eram\Pardakht\Money\Amount` — use `Eram\Abzar\Money\Amount`.
- `Eram\Pardakht\Money\Currency` — use `Eram\Abzar\Money\Unit` (`TOMAN`/`RIAL`) paired with `Eram\Abzar\Money\Currency::format()` for display. The abzar unit enum was unused in pardakht's own source, so this is a cleanup.
- `Eram\Pardakht\Banking\CardNumber` — use `Eram\Abzar\Validation\CardNumber`. Note: `masked()` / `formatted()` output uses space separators (`"6037 9912 3456 7893"`) rather than dashes.
- `Eram\Pardakht\Banking\Sheba` — use `Eram\Abzar\Validation\Iban`.
- `Eram\Pardakht\Banking\BankIdentifier` — use `Eram\Abzar\Validation\CardNumber::tryFrom($str)?->bank()` and `Validation\Iban::tryFrom($str)?->bank()`, which both return the Persian bank name.
- `Eram\Pardakht\Exception\InvalidAmountException` — negative amounts now throw `Eram\Abzar\Exception\FormatException` with `ErrorCode::AMOUNT_NEGATIVE`. Note that this exception does **not** extend `PardakhtException`, so host apps catching `PardakhtException` must also catch `Eram\Abzar\Exception\AbzarException`.

### Docs
- Reworded "zero dependencies" / "zero Composer dependencies" claims across `README.md`, `docs/en/README.md`, `docs/en/getting-started.md`, `docs/en/faq.md`, and the composer `description` to reflect the single `eram/abzar` runtime dependency.

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
