<?php

namespace App\Services\Sms;

interface SmsProvider
{
    /**
     * @throws SmsException
     */
    public function sendSms(string $to, string $message): SmsSendResult;
}
