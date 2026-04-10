<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\IDPay;

enum IDPayErrorCode: int
{
    case Pending = 1;
    case PaymentPending = 2;
    case Cancelled = 3;
    case CardError = 5;
    case SystemFailure = 6;
    case UserCancelled = 7;
    case AmountLessThanMin = 8;
    case WaitThenRetry = 9;
    case PaymentSuccess = 10;
    case AlreadyVerified = 100;
    case NotVerified = 101;
    case VerificationTimedOut = 200;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Pending => 'پرداخت ایجاد شده',
                self::PaymentPending => 'در انتظار پرداخت',
                self::Cancelled => 'لغو شده',
                self::CardError => 'خطای کارت',
                self::SystemFailure => 'خطای سیستمی',
                self::UserCancelled => 'لغو توسط کاربر',
                self::AmountLessThanMin => 'مبلغ کمتر از حداقل',
                self::WaitThenRetry => 'صبر کنید و مجدداً تلاش کنید',
                self::PaymentSuccess => 'پرداخت موفق',
                self::AlreadyVerified => 'قبلاً تایید شده',
                self::NotVerified => 'تایید نشده',
                self::VerificationTimedOut => 'مهلت تایید گذشته',
            };
        }

        return match ($this) {
            self::Pending => 'Payment created',
            self::PaymentPending => 'Pending payment',
            self::Cancelled => 'Cancelled',
            self::CardError => 'Card error',
            self::SystemFailure => 'System failure',
            self::UserCancelled => 'User cancelled',
            self::AmountLessThanMin => 'Amount less than minimum',
            self::WaitThenRetry => 'Wait and retry',
            self::PaymentSuccess => 'Payment successful',
            self::AlreadyVerified => 'Already verified',
            self::NotVerified => 'Not verified',
            self::VerificationTimedOut => 'Verification timed out',
        };
    }
}
