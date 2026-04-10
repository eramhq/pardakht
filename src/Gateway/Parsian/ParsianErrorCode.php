<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Gateway\Parsian;

enum ParsianErrorCode: int
{
    case Success = 0;
    case Cancelled = -1;
    case NotEnoughBalance = -2;
    case MerchantNotFound = -3;
    case TerminalNotFound = -4;
    case InvalidAmount = -5;
    case InvalidPaymentId = -6;
    case InvalidTransaction = -7;
    case InvalidCardNumber = -8;
    case TransactionTimeout = -9;
    case RepeatTransaction = -10;

    public function message(string $locale = 'fa'): string
    {
        if ($locale === 'fa') {
            return match ($this) {
                self::Success => 'تراکنش موفق',
                self::Cancelled => 'تراکنش لغو شده',
                self::NotEnoughBalance => 'موجودی کافی نیست',
                self::MerchantNotFound => 'پذیرنده یافت نشد',
                self::TerminalNotFound => 'ترمینال یافت نشد',
                self::InvalidAmount => 'مبلغ نامعتبر',
                self::InvalidPaymentId => 'شناسه پرداخت نامعتبر',
                self::InvalidTransaction => 'تراکنش نامعتبر',
                self::InvalidCardNumber => 'شماره کارت نامعتبر',
                self::TransactionTimeout => 'مهلت تراکنش گذشته',
                self::RepeatTransaction => 'تراکنش تکراری',
            };
        }

        return match ($this) {
            self::Success => 'Transaction successful',
            self::Cancelled => 'Transaction cancelled',
            self::NotEnoughBalance => 'Insufficient balance',
            self::MerchantNotFound => 'Merchant not found',
            self::TerminalNotFound => 'Terminal not found',
            self::InvalidAmount => 'Invalid amount',
            self::InvalidPaymentId => 'Invalid payment ID',
            self::InvalidTransaction => 'Invalid transaction',
            self::InvalidCardNumber => 'Invalid card number',
            self::TransactionTimeout => 'Transaction timeout',
            self::RepeatTransaction => 'Repeat transaction',
        };
    }
}
