<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Sadad;

enum SadadErrorCode: int
{
    case Success = 0;
    case Cancelled = -1;
    case InvalidMerchant = 1;
    case InvalidAmount = 2;
    case InvalidOrder = 3;
    case InvalidTerminal = 4;
    case InvalidIP = 5;
    case InvalidSign = 6;
    case AccessDenied = 7;
    case InvalidCallbackUrl = 8;
    case DuplicateRequest = 9;
    case TokenNotFound = 10;
    case InvalidToken = 11;
    case TokenExpired = 12;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'تراکنش موفق',
                self::Cancelled => 'تراکنش توسط خریدار لغو شد',
                self::InvalidMerchant => 'پذیرنده نامعتبر',
                self::InvalidAmount => 'مبلغ نامعتبر',
                self::InvalidOrder => 'سفارش نامعتبر',
                self::InvalidTerminal => 'ترمینال نامعتبر',
                self::InvalidIP => 'آی‌پی نامعتبر',
                self::InvalidSign => 'امضا نامعتبر',
                self::AccessDenied => 'دسترسی رد شد',
                self::InvalidCallbackUrl => 'آدرس بازگشت نامعتبر',
                self::DuplicateRequest => 'درخواست تکراری',
                self::TokenNotFound => 'توکن یافت نشد',
                self::InvalidToken => 'توکن نامعتبر',
                self::TokenExpired => 'توکن منقضی شده',
            };
        }

        return match ($this) {
            self::Success => 'Transaction successful',
            self::Cancelled => 'Transaction cancelled by buyer',
            self::InvalidMerchant => 'Invalid merchant',
            self::InvalidAmount => 'Invalid amount',
            self::InvalidOrder => 'Invalid order',
            self::InvalidTerminal => 'Invalid terminal',
            self::InvalidIP => 'Invalid IP',
            self::InvalidSign => 'Invalid sign',
            self::AccessDenied => 'Access denied',
            self::InvalidCallbackUrl => 'Invalid callback URL',
            self::DuplicateRequest => 'Duplicate request',
            self::TokenNotFound => 'Token not found',
            self::InvalidToken => 'Invalid token',
            self::TokenExpired => 'Token expired',
        };
    }
}
