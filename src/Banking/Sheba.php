<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Banking;

/**
 * Iranian IBAN (Sheba) value object with validation and bank detection.
 *
 * Format: IR + 2 check digits + 22 digits (total 26 characters).
 */
final class Sheba
{
    private string $value;

    public function __construct(string $sheba)
    {
        $normalized = \strtoupper(\preg_replace('/[\s-]/', '', $sheba) ?? '');

        if (!\str_starts_with($normalized, 'IR')) {
            $normalized = 'IR' . $normalized;
        }

        if (!\preg_match('/^IR\d{24}$/', $normalized)) {
            throw new \InvalidArgumentException(
                'Sheba must be in format IR + 24 digits (26 characters total).'
            );
        }

        if (!self::validateChecksum($normalized)) {
            throw new \InvalidArgumentException('Invalid Sheba checksum.');
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * Returns Sheba without the IR prefix.
     */
    public function digits(): string
    {
        return \substr($this->value, 2);
    }

    public function bankName(): ?string
    {
        return BankIdentifier::fromSheba($this->value);
    }

    /**
     * Returns formatted Sheba: IR06 0120 0100 0000 0326 2007 43
     */
    public function formatted(): string
    {
        $parts = \str_split($this->value, 4);

        return \implode(' ', $parts);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Validate a Sheba string without constructing an object.
     */
    public static function isValid(string $sheba): bool
    {
        try {
            new self($sheba);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Validate the IBAN checksum using ISO 13616 / mod-97 algorithm.
     */
    private static function validateChecksum(string $iban): bool
    {
        // Move the first 4 characters to the end
        $rearranged = \substr($iban, 4) . \substr($iban, 0, 4);

        // Replace letters with numbers (A=10, B=11, ..., Z=35)
        $numeric = '';
        for ($i = 0, $len = \strlen($rearranged); $i < $len; $i++) {
            $char = $rearranged[$i];
            if (\ctype_alpha($char)) {
                $numeric .= (string) (\ord($char) - 55);
            } else {
                $numeric .= $char;
            }
        }

        // Perform mod-97 on the numeric string (handle large numbers in chunks)
        $remainder = '';
        for ($i = 0, $len = \strlen($numeric); $i < $len; $i++) {
            $remainder .= $numeric[$i];
            $remainder = (string) ((int) $remainder % 97);
        }

        return (int) $remainder === 1;
    }
}
