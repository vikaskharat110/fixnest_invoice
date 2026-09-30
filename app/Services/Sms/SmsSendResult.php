<?php

namespace App\Services\Sms;

final readonly class SmsSendResult
{
    public function __construct(
        public string $providerMessageId,
        public ?string $recipientId = null,
    ) {
    }
}
