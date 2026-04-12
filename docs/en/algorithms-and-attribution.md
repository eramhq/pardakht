# Algorithms & Attribution

## Luhn Algorithm

The `CardNumber` class uses the [Luhn algorithm](https://en.wikipedia.org/wiki/Luhn_algorithm) (ISO/IEC 7812-1) to validate Iranian bank card numbers. This is the same checksum algorithm used worldwide for credit/debit card validation.

## IBAN Validation (ISO 13616)

The `Sheba` class validates Iranian IBANs using the [ISO 13616](https://en.wikipedia.org/wiki/International_Bank_Account_Number#Validating_the_IBAN) mod-97 checksum algorithm. The implementation handles large numbers by processing the numeric string in chunks to avoid integer overflow.

## Bank Identification Numbers (BIN)

Card-to-bank mapping uses publicly available BIN (Bank Identification Number) tables for Iranian banks. The first 6 digits of a card number identify the issuing bank.

## Sheba Bank Codes

Sheba-to-bank mapping uses the bank code embedded in positions 5-7 of the IBAN (after the IR prefix and check digits). These codes are assigned by the Central Bank of Iran (CBI).

## Gateway API Documentation

Each gateway implementation follows its respective official API documentation:

| Gateway | Protocol | API Provider |
|---------|----------|-------------|
| Mellat (Behpardakht) | SOAP | Behpardakht Mellat |
| Saman (Sep) | SOAP | Saman Electronic Payment |
| Parsian (Pec) | SOAP | Parsian E-Commerce |
| Sadad | REST | Bank Melli Iran |
| Pasargad | REST | Bank Pasargad |
| Zarinpal | REST | Zarinpal |
| IDPay | REST | IDPay |
| Zibal | REST | Zibal |
| Pay.ir | REST | Pay.ir |
| NextPay | REST | NextPay |
| Vandar | REST | Vandar |
| Sizpay | REST | Sizpay |

## License

Pardakht is released under the [MIT License](https://opensource.org/licenses/MIT).
