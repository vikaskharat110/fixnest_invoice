<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'sendOtp'])
        ->middleware(['throttle:auth-forms', 'throttle:otp-request'])
        ->name('register.send-otp');

    Route::get('register/verify', [RegisteredUserController::class, 'showVerify'])
        ->name('register.verify');

    Route::post('register/verify', [RegisteredUserController::class, 'verifyOtp'])
        ->middleware('throttle:auth-forms')
        ->name('register.verify.store');

    Route::post('register/verify/resend', [RegisteredUserController::class, 'resendOtp'])
        ->middleware(['throttle:auth-forms', 'throttle:otp-request'])
        ->name('register.verify.resend');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:auth-forms');

    Route::post('login/otp/send', [LoginOtpController::class, 'send'])
        ->middleware(['throttle:auth-forms', 'throttle:otp-request'])
        ->name('login.otp.send');

    Route::get('login/otp/verify', [LoginOtpController::class, 'show'])
        ->name('login.otp.verify');

    Route::post('login/otp/verify', [LoginOtpController::class, 'verify'])
        ->middleware('throttle:auth-forms')
        ->name('login.otp.verify.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'sendOtp'])
        ->middleware(['throttle:auth-forms', 'throttle:otp-request'])
        ->name('password.otp.send');

    Route::get('forgot-password/verify', [PasswordResetLinkController::class, 'showVerify'])
        ->name('password.otp.verify');

    Route::post('forgot-password/verify', [PasswordResetLinkController::class, 'verifyOtp'])
        ->middleware('throttle:auth-forms')
        ->name('password.otp.verify.store');

    Route::get('reset-password', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:auth-forms')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
