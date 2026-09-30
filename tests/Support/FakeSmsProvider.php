<?php

namespace Tests\Support;

use App\Services\Sms\SmsProvider;
use App\Services\Sms\SmsSendResult;

class FakeSmsProvider implements SmsProvider
{
    /** @var array<int, array{to: string, message: string}> */
    public array $sent = [];

    public function sendSms(string $to, string $message): SmsSendResult
    {
        $this->sent[] = ['to' => $to, 'message' => $message];

        return new SmsSendResult(providerMessageId: 'fake-'.count($this->sent));
    }

    public function lastCode(): string
    {
        preg_match('/(\d{6})/', end($this->sent)['message'], $matches);

        return $matches[1];
    }
}
