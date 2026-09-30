<?php

namespace App\Providers;

use App\Services\Sms\BulkSmsConnectProvider;
use App\Services\Sms\SmsProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsProvider::class, BulkSmsConnectProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Generous baseline for every web request — catches scripted/bot abuse
        // without affecting normal browsing. Keyed by user when logged in
        // (so one admin's heavy PDF/report usage doesn't get lumped in with
        // others behind the same office IP), else by IP.
        RateLimiter::for('web', function ($request) {
            return Limit::perMinute(200)->by($request->user()?->id ?: $request->ip());
        });

        // Unauthenticated auth forms (register, login, forgot/reset password) —
        // low legitimate frequency, prime target for credential stuffing,
        // account-creation spam, and password-reset email bombing.
        RateLimiter::for('auth-forms', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Public marketing-site contact form — no login required, classic spam target.
        RateLimiter::for('contact-form', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Emailing invoices out — authenticated, but still capped so a compromised
        // or scripted account can't be used to spam external inboxes.
        RateLimiter::for('invoice-email', function ($request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // PDF generation is CPU/memory-heavy (DomPDF) — cap it so a runaway
        // script can't exhaust server resources.
        RateLimiter::for('invoice-pdf', function ($request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // OTP sends cost real money per message. Keyed by the target phone
        // number (falling back to IP if none was submitted) so one number
        // can't be bombed by rotating IPs, on top of the per-IP auth-forms limit.
        RateLimiter::for('otp-request', function ($request) {
            return Limit::perHour(5)->by($request->string('phone')->value() ?: $request->ip());
        });
    }
}
