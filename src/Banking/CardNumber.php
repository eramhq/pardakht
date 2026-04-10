<?php

declare(strict_types=1);

namespace Eram\Pardakht\Banking;

/**
 * Iranian bank card number value object with Luhn validation and bank detection.
 */
final class CardNumber
{
    private string $number;

    public function __construct(string $number)
    {
        $this->number = \preg_replace('/\D/', '', $number) ?? '';

        if (\strlen($this->number) !== 16) {
            throw new \InvalidArgumentException('Card number must be exactly 16 digits.');
        }

        if (!self::luhn($this->number)) {
            throw new \InvalidArgumentException('Card number failed Luhn check.');
        }
    }

    public function number(): string
    {
        return $this->number;
    }

    /**
     * Returns the card number in masked format: 6037-99**-****-1234
     */
    public function masked(): string
    {
        return \substr($this->number, 0, 6)
            . '******'
            . \substr($this->number, 12, 4);
    }

    /**
     * Returns formatted card number: 6037-9912-3456-7890
     */
    public function formatted(): string
    {
        return \implode('-', \str_split($this->number, 4));
    }

    public function bankName(): ?string
    {
        return BankIdentifier::fromCardNumber($this->number);
    }

    public function equals(self $other): bool
    {
        return $this->number === $other->number;
    }

    public function __toString(): string
    {
        return $this->number;
    }

    /**
     * Validate a card number string without constructing an object.
     */
    public static function isValid(string $number): bool
    {
        $digits = \preg_replace('/\D/', '', $number) ?? '';

        return \strlen($digits) === 16 && self::luhn($digits);
    }

    private static function luhn(string $number): bool
    {
        $sum = 0;
        $length = \strlen($number);

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$length - 1 - $i];

            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
