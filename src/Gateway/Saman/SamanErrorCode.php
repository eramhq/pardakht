<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Saman;

enum SamanErrorCode: int
{
    case Cancelled = -1;
    case Success = 0;
    case Failed = -2;
    case TerminalError = -3;
    case DuplicateTrackingCode = -4;
    case InvalidSender = -5;
    case TerminalError2 = -6;
    case InvalidTransaction = -7;
    case InvalidAddress = -8;
    case InvalidMerchantOrIP = -9;
    case InvalidToken = -10;
    case NotRedirected = -11;
    case MaxTimeExceeded = -12;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Cancelled => 'تراکنش توسط خریدار لغو شد',
                self::Success => 'تراکنش موفق',
                self::Failed => 'تراکنش ناموفق',
                self::TerminalError => 'خطای ترمینال',
                self::DuplicateTrackingCode => 'کد رهگیری تکراری',
                self::InvalidSender => 'ارسال‌کننده نامعتبر',
                self::TerminalError2 => 'خطای ترمینال',
                self::InvalidTransaction => 'تراکنش نامعتبر',
                self::InvalidAddress => 'آدرس نامعتبر',
                self::InvalidMerchantOrIP => 'پذیرنده یا آی‌پی نامعتبر',
                self::InvalidToken => 'توکن نامعتبر',
                self::NotRedirected => 'عدم ارجاع',
                self::MaxTimeExceeded => 'زمان حداکثر سپری شده',
            };
        }

        return match ($this) {
            self::Cancelled => 'Transaction cancelled by buyer',
            self::Success => 'Transaction successful',
            self::Failed => 'Transaction failed',
            self::TerminalError, self::TerminalError2 => 'Terminal error',
            self::DuplicateTrackingCode => 'Duplicate tracking code',
            self::InvalidSender => 'Invalid sender',
            self::InvalidTransaction => 'Invalid transaction',
            self::InvalidAddress => 'Invalid address',
            self::InvalidMerchantOrIP => 'Invalid merchant or IP',
            self::InvalidToken => 'Invalid token',
            self::NotRedirected => 'Not redirected',
            self::MaxTimeExceeded => 'Maximum time exceeded',
        };
    }
}
