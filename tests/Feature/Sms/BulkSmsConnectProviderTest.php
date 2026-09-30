<?php

namespace Tests\Feature\Sms;

use App\Services\Sms\BulkSmsConnectProvider;
use App\Services\Sms\SmsException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BulkSmsConnectProviderTest extends TestCase
{
    private function okBody(): array
    {
        return [
            'status' => 'OK',
            'data' => [['id' => '59813321-1', 'mobile' => '919876543210', 'status' => 'SUBMITTED']],
            'msgid' => '456559813321924',
            'message' => 'message Submitted successfully',
        ];
    }

    public function test_posts_the_documented_payload_and_maps_a_successful_response(): void
    {
        Http::fake([
            'bulksmsconnect.in/*' => Http::response($this->okBody(), 200),
        ]);

        $result = (new BulkSmsConnectProvider)->sendSms('+919876543210', 'hello 123456');

        $this->assertSame('456559813321924', $result->providerMessageId);
        $this->assertSame('59813321-1', $result->recipientId);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bulksmsconnect.in/V2/http-api-post.php'
                && $request['number'] === '919876543210'
                && $request['message'] === 'hello 123456'
                && $request['format'] === 'json';
        });
    }

    public function test_rejects_a_non_indian_recipient_before_calling_the_provider(): void
    {
        Http::fake();

        $this->expectException(SmsException::class);

        try {
            (new BulkSmsConnectProvider)->sendSms('+14155550123', 'x');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_maps_azq10_to_insufficient_credit(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'AZQ10', 'message' => 'You have insufficient credit'], 200),
        ]);

        try {
            (new BulkSmsConnectProvider)->sendSms('+919876543210', 'x');
            $this->fail('Expected SmsException');
        } catch (SmsException $e) {
            $this->assertSame('INSUFFICIENT_CREDIT', $e->errorCode);
        }
    }

    public function test_maps_any_other_azq_status_to_rejected(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'AZQ02', 'message' => 'Invalid Api Key'], 200),
        ]);

        try {
            (new BulkSmsConnectProvider)->sendSms('+919876543210', 'x');
            $this->fail('Expected SmsException');
        } catch (SmsException $e) {
            $this->assertSame('REJECTED', $e->errorCode);
        }
    }

    public function test_treats_a_non_2xx_response_as_unavailable(): void
    {
        Http::fake([
            '*' => Http::response([], 502),
        ]);

        try {
            (new BulkSmsConnectProvider)->sendSms('+919876543210', 'x');
            $this->fail('Expected SmsException');
        } catch (SmsException $e) {
            $this->assertSame('UNAVAILABLE', $e->errorCode);
        }
    }
}
