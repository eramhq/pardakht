<?php

declare(strict_types=1);

namespace Eram\Pardakht\Gateway\Pasargad;

enum PasargadErrorCode: int
{
    case Success = 0;
    case InvalidRequest = -1;
    case InvalidMerchant = -2;
    case InvalidTerminal = -3;
    case InvalidAmount = -4;
    case InvalidAction = -5;
    case InvalidTimeStamp = -6;
    case InvalidSign = -7;
    case DuplicateInvoice = -8;
    case InvoiceNotFound = -9;
    case VerifyFailed = -10;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'تراکنش موفق',
                self::InvalidRequest => 'درخواست نامعتبر',
                self::InvalidMerchant => 'پذیرنده نامعتبر',
                self::InvalidTerminal => 'ترمینال نامعتبر',
                self::InvalidAmount => 'مبلغ نامعتبر',
                self::InvalidAction => 'عملیات نامعتبر',
                self::InvalidTimeStamp => 'زمان نامعتبر',
                self::InvalidSign => 'امضا نامعتبر',
                self::DuplicateInvoice => 'فاکتور تکراری',
                self::InvoiceNotFound => 'فاکتور یافت نشد',
                self::VerifyFailed => 'تایید ناموفق',
            };
        }

        return match ($this) {
            self::Success => 'Transaction successful',
            self::InvalidRequest => 'Invalid request',
            self::InvalidMerchant => 'Invalid merchant',
            self::InvalidTerminal => 'Invalid terminal',
            self::InvalidAmount => 'Invalid amount',
            self::InvalidAction => 'Invalid action',
            self::InvalidTimeStamp => 'Invalid timestamp',
            self::InvalidSign => 'Invalid sign',
            self::DuplicateInvoice => 'Duplicate invoice',
            self::InvoiceNotFound => 'Invoice not found',
            self::VerifyFailed => 'Verification failed',
        };
    }
}
