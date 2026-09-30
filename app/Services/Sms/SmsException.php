<?php

namespace App\Services\Sms;

class SmsException extends \RuntimeException
{
    public function __construct(
        public string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function notConfigured(string $message): self
    {
        return new self('NOT_CONFIGURED', $message);
    }

    public static function invalidRecipient(string $message): self
    {
        return new self('INVALID_RECIPIENT', $message);
    }

    public static function insufficientCredit(string $message): self
    {
        return new self('INSUFFICIENT_CREDIT', $message);
    }

    public static function rejected(string $message): self
    {
        return new self('REJECTED', $message);
    }

    public static function unavailable(string $message): self
    {
        return new self('UNAVAILABLE', $message);
    }
}
