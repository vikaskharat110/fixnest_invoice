<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BulkSmsConnectProvider implements SmsProvider
{
    private const SEND_PATH = '/http-api-post.php';

    /**
     * @throws SmsException
     */
    public function sendSms(string $to, string $message): SmsSendResult
    {
        $apiKey = config('services.bulksmsconnect.api_key');
        $senderId = config('services.bulksmsconnect.sender_id');

        if (! $apiKey || ! $senderId) {
            throw SmsException::notConfigured('SMS_API_KEY / SMS_SENDER_ID are not set');
        }

        $number = $this->toProviderNumber($to);
        $baseUrl = rtrim(config('services.bulksmsconnect.base_url'), '/');
        $timeoutMs = (int) config('services.bulksmsconnect.timeout_ms', 8000);

        try {
            $response = Http::timeout(intdiv($timeoutMs, 1000) ?: 1)
                ->acceptJson()
                ->post($baseUrl.self::SEND_PATH, [
                    'apikey' => $apiKey,
                    'senderid' => $senderId,
                    'number' => $number,
                    'message' => $message,
                    'format' => 'json',
                ]);
        } catch (Throwable $e) {
            Log::error('SMS provider unreachable or timed out', ['error' => $e->getMessage()]);

            throw SmsException::unavailable('SMS provider is unreachable or timed out');
        }

        if ($response->failed()) {
            Log::error('SMS provider returned non-2xx', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw SmsException::unavailable("SMS provider responded with HTTP {$response->status()}");
        }

        $body = $response->json() ?? [];
        $status = $body['status'] ?? null;

        if ($status !== 'OK') {
            $providerStatus = $status ?? 'UNKNOWN';
            $providerMessage = $body['message'] ?? 'no message';
            $noCredit = $providerStatus === 'AZQ10' || preg_match('/insufficient credit/i', (string) $providerMessage);

            Log::error('SMS provider rejected the send', [
                'providerStatus' => $providerStatus,
                'providerMessage' => $providerMessage,
            ]);

            throw $noCredit
                ? SmsException::insufficientCredit("SMS provider rejected the send ({$providerStatus}: {$providerMessage})")
                : SmsException::rejected("SMS provider rejected the send ({$providerStatus}: {$providerMessage})");
        }

        return new SmsSendResult(
            providerMessageId: isset($body['msgid']) ? (string) $body['msgid'] : '',
            recipientId: isset($body['data'][0]['id']) ? (string) $body['data'][0]['id'] : null,
        );
    }

    /**
     * @throws SmsException
     */
    private function toProviderNumber(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (! preg_match('/^91\d{10}$/', $digits)) {
            throw SmsException::invalidRecipient("Not a deliverable Indian mobile number: {$phone}");
        }

        return $digits;
    }
}
