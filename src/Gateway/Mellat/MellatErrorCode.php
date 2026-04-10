<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Mellat;

enum MellatErrorCode: int
{
    case Success = 0;
    case InvalidCard = 11;
    case InsufficientFunds = 12;
    case InvalidPassword = 13;
    case ExceededRetryLimit = 14;
    case InvalidCardNumber = 15;
    case ExceededWithdrawalLimit = 16;
    case SecurityViolation = 17;
    case ExceededWithdrawalCount = 18;
    case ExceededPaymentLimit = 19;
    case InvalidIssuer = 111;
    case SwitchError = 112;
    case IssuerUnavailable = 113;
    case DuplicateTransaction = 114;
    case TransactionNotFound = 21;
    case InvalidAmount = 23;
    case InvalidMerchant = 24;
    case TerminalNotAllowed = 25;
    case TransactionReversed = 31;
    case FormatError = 32;
    case AccountBlocked = 33;
    case InsufficientPermission = 34;
    case DateExpired = 35;
    case AmountExceeded = 41;
    case InvalidTerminal = 42;
    case SuspiciousTransaction = 43;
    case ResponseTimeout = 44;
    case TransactionCancelled = 45;
    case InvalidResponse = 46;
    case LengthError = 47;
    case AmountMismatch = 48;
    case SystemError = 49;
    case DuplicateTransactionFound = 51;
    case NoRecord = 54;
    case SettlementError = 55;
    case SystemInternalError = 61;
    case MerchantNotFound = 412;
    case MerchantBlocked = 413;
    case MerchantInactive = 414;
    case InvalidInput = 415;
    case ExceededMerchantLimit = 416;
    case InternalSystemError = 417;
    case InvalidPayerIP = 418;
    case ExceededCharacterLimit = 419;
    case InvalidPayerId = 421;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'تراکنش با موفقیت انجام شد',
                self::InvalidCard => 'شماره کارت نامعتبر است',
                self::InsufficientFunds => 'موجودی کافی نیست',
                self::InvalidPassword => 'رمز نادرست است',
                self::ExceededRetryLimit => 'تعداد تلاش‌ها بیش از حد مجاز',
                self::InvalidCardNumber => 'صادرکننده کارت نامعتبر',
                self::ExceededWithdrawalLimit => 'مبلغ برداشت بیش از حد مجاز',
                self::SecurityViolation => 'خطای امنیتی',
                self::ExceededWithdrawalCount => 'تعداد برداشت بیش از حد مجاز',
                self::ExceededPaymentLimit => 'مبلغ پرداخت بیش از حد مجاز',
                self::InvalidIssuer => 'صادرکننده نامعتبر',
                self::SwitchError => 'خطای سوئیچ',
                self::IssuerUnavailable => 'صادرکننده در دسترس نیست',
                self::DuplicateTransaction => 'تراکنش تکراری',
                self::TransactionNotFound => 'تراکنش یافت نشد',
                self::InvalidAmount => 'مبلغ نامعتبر',
                self::InvalidMerchant => 'پذیرنده نامعتبر',
                self::TerminalNotAllowed => 'ترمینال مجاز نیست',
                self::TransactionReversed => 'تراکنش برگشت خورده',
                self::FormatError => 'خطای فرمت',
                self::AccountBlocked => 'حساب مسدود شده',
                self::InsufficientPermission => 'دسترسی کافی نیست',
                self::DateExpired => 'تاریخ انقضا گذشته',
                self::AmountExceeded => 'مبلغ بیش از حد مجاز',
                self::InvalidTerminal => 'ترمینال نامعتبر',
                self::SuspiciousTransaction => 'تراکنش مشکوک',
                self::ResponseTimeout => 'مهلت پاسخ‌دهی گذشته',
                self::TransactionCancelled => 'تراکنش لغو شده',
                self::InvalidResponse => 'پاسخ نامعتبر',
                self::LengthError => 'خطای طول داده',
                self::AmountMismatch => 'مبلغ تطابق ندارد',
                self::SystemError => 'خطای سیستمی',
                self::DuplicateTransactionFound => 'تراکنش تکراری یافت شد',
                self::NoRecord => 'رکوردی یافت نشد',
                self::SettlementError => 'خطای تسویه',
                self::SystemInternalError => 'خطای داخلی سیستم',
                self::MerchantNotFound => 'پذیرنده یافت نشد',
                self::MerchantBlocked => 'پذیرنده مسدود شده',
                self::MerchantInactive => 'پذیرنده غیرفعال',
                self::InvalidInput => 'ورودی نامعتبر',
                self::ExceededMerchantLimit => 'سقف پذیرنده رد شده',
                self::InternalSystemError => 'خطای داخلی سیستم',
                self::InvalidPayerIP => 'آی‌پی پرداخت‌کننده نامعتبر',
                self::ExceededCharacterLimit => 'طول کاراکتر بیش از حد مجاز',
                self::InvalidPayerId => 'شناسه پرداخت‌کننده نامعتبر',
            };
        }

        return match ($this) {
            self::Success => 'Transaction successful',
            self::InvalidCard => 'Invalid card',
            self::InsufficientFunds => 'Insufficient funds',
            self::InvalidPassword => 'Invalid password',
            self::ExceededRetryLimit => 'Exceeded retry limit',
            self::InvalidCardNumber => 'Invalid card number',
            self::ExceededWithdrawalLimit => 'Exceeded withdrawal limit',
            self::SecurityViolation => 'Security violation',
            self::ExceededWithdrawalCount => 'Exceeded withdrawal count',
            self::ExceededPaymentLimit => 'Exceeded payment limit',
            self::InvalidIssuer => 'Invalid issuer',
            self::SwitchError => 'Switch error',
            self::IssuerUnavailable => 'Issuer unavailable',
            self::DuplicateTransaction => 'Duplicate transaction',
            self::TransactionNotFound => 'Transaction not found',
            self::InvalidAmount => 'Invalid amount',
            self::InvalidMerchant => 'Invalid merchant',
            self::TerminalNotAllowed => 'Terminal not allowed',
            self::TransactionReversed => 'Transaction reversed',
            self::FormatError => 'Format error',
            self::AccountBlocked => 'Account blocked',
            self::InsufficientPermission => 'Insufficient permission',
            self::DateExpired => 'Date expired',
            self::AmountExceeded => 'Amount exceeded',
            self::InvalidTerminal => 'Invalid terminal',
            self::SuspiciousTransaction => 'Suspicious transaction',
            self::ResponseTimeout => 'Response timeout',
            self::TransactionCancelled => 'Transaction cancelled',
            self::InvalidResponse => 'Invalid response',
            self::LengthError => 'Length error',
            self::AmountMismatch => 'Amount mismatch',
            self::SystemError => 'System error',
            self::DuplicateTransactionFound => 'Duplicate transaction found',
            self::NoRecord => 'No record found',
            self::SettlementError => 'Settlement error',
            self::SystemInternalError => 'System internal error',
            self::MerchantNotFound => 'Merchant not found',
            self::MerchantBlocked => 'Merchant blocked',
            self::MerchantInactive => 'Merchant inactive',
            self::InvalidInput => 'Invalid input',
            self::ExceededMerchantLimit => 'Exceeded merchant limit',
            self::InternalSystemError => 'Internal system error',
            self::InvalidPayerIP => 'Invalid payer IP',
            self::ExceededCharacterLimit => 'Exceeded character limit',
            self::InvalidPayerId => 'Invalid payer ID',
        };
    }
}
