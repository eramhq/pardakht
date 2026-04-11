# Contributing to Pardakht

Thank you for your interest in contributing to Pardakht!

## Adding a New Gateway

1. Create a directory under `src/Gateway/YourGateway/`
2. Create three files:
   - `YourGatewayConfig.php` — readonly DTO with gateway credentials
   - `YourGatewayGateway.php` — extends `AbstractGateway` (REST) or `AbstractSoapGateway` (SOAP)
   - `YourGatewayErrorCode.php` — enum with bilingual error messages
3. Register the gateway alias in `src/Pardakht.php`
4. Add unit tests in `tests/Unit/Gateway/YourGatewayGatewayTest.php`
5. Update the gateway table in `README.md`

## Development Setup

```bash
git clone https://github.com/eramhq/pardakht.git
cd pardakht
composer install
```

## Running Tests

```bash
vendor/bin/phpunit
```

## Code Quality

```bash
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix
```

## Pull Request Guidelines

- One feature/fix per PR
- All tests must pass
- PHPStan level 9 must pass
- Follow PER-CS2 coding style
- Add tests for new gateways
- Error code enums must have both Persian and English messages
