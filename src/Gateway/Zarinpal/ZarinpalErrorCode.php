<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Zarinpal;

enum ZarinpalErrorCode: int
{
    case Success = 100;
    case AlreadyVerified = 101;
    case InvalidMerchant = -1;
    case InvalidIP = -2;
    case InsufficientLevel = -3;
    case InvalidAmount = -4;
    case InvalidCallbackUrl = -11;
    case InvalidAuthority = -12;
    case InvalidGateway = -15;
    case TooManyRequests = -16;
    case UserBlocked = -21;
    case InvalidInput = -22;
    case SessionExpired = -33;
    case AmountMismatch = -34;
    case ExceedsSplitLimit = -40;
    case InvalidRequest = -50;
    case UserCancelled = -51;
    case InternalError = -54;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'تراکنش موفق',
                self::AlreadyVerified => 'تراکنش قبلاً تایید شده',
                self::InvalidMerchant => 'مرچنت کد نامعتبر',
                self::InvalidIP => 'آی‌پی نامعتبر',
                self::InsufficientLevel => 'سطح تایید ناکافی',
                self::InvalidAmount => 'مبلغ نامعتبر',
                self::InvalidCallbackUrl => 'آدرس بازگشت نامعتبر',
                self::InvalidAuthority => 'authority نامعتبر',
                self::InvalidGateway => 'درگاه نامعتبر',
                self::TooManyRequests => 'تعداد درخواست بیش از حد مجاز',
                self::UserBlocked => 'کاربر مسدود شده',
                self::InvalidInput => 'ورودی نامعتبر',
                self::SessionExpired => 'جلسه منقضی شده',
                self::AmountMismatch => 'مبلغ تطابق ندارد',
                self::ExceedsSplitLimit => 'سقف تسهیم رد شده',
                self::InvalidRequest => 'درخواست نامعتبر',
                self::UserCancelled => 'پرداخت توسط کاربر لغو شد',
                self::InternalError => 'خطای داخلی',
            };
        }

        return match ($this) {
            self::Success => 'Transaction successful',
            self::AlreadyVerified => 'Transaction already verified',
            self::InvalidMerchant => 'Invalid merchant ID',
            self::InvalidIP => 'Invalid IP address',
            self::InsufficientLevel => 'Insufficient verification level',
            self::InvalidAmount => 'Invalid amount',
            self::InvalidCallbackUrl => 'Invalid callback URL',
            self::InvalidAuthority => 'Invalid authority',
            self::InvalidGateway => 'Invalid gateway',
            self::TooManyRequests => 'Too many requests',
            self::UserBlocked => 'User blocked',
            self::InvalidInput => 'Invalid input',
            self::SessionExpired => 'Session expired',
            self::AmountMismatch => 'Amount mismatch',
            self::ExceedsSplitLimit => 'Exceeds split limit',
            self::InvalidRequest => 'Invalid request',
            self::UserCancelled => 'Payment cancelled by user',
            self::InternalError => 'Internal error',
        };
    }
}
