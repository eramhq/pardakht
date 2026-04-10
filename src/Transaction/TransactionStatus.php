<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Transaction;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Verified = 'verified';
    case Settled = 'settled';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Pending => 'در انتظار',
                self::Paid => 'پرداخت شده',
                self::Verified => 'تایید شده',
                self::Settled => 'تسویه شده',
                self::Failed => 'ناموفق',
                self::Refunded => 'بازگشت داده شده',
            };
        }

        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Verified => 'Verified',
            self::Settled => 'Settled',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }
}
