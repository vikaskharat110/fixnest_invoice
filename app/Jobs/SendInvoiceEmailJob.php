<?php

namespace App\Jobs;

use App\Mail\InvoiceMailable;
use App\Models\EmailLog;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(public int $emailLogId) {}

    public function handle(): void
    {
        $emailLog = EmailLog::with(['invoice.company', 'invoice.items'])
            ->findOrFail($this->emailLogId);

        $emailLog->increment('attempts');

        $invoice = $emailLog->invoice;
        $organization = Organization::current();

        $logoBase64 = null;
        if ($organization->logo_path && Storage::disk('public')->exists($organization->logo_path)) {
            $ext = pathinfo($organization->logo_path, PATHINFO_EXTENSION);
            $logoBase64 = "data:image/{$ext};base64," . base64_encode(Storage::disk('public')->get($organization->logo_path));
        }

        $signatureBase64 = null;
        if ($organization->signature_path && Storage::disk('public')->exists($organization->signature_path)) {
            $ext = pathinfo($organization->signature_path, PATHINFO_EXTENSION);
            $signatureBase64 = "data:image/{$ext};base64," . base64_encode(Storage::disk('public')->get($organization->signature_path));
        }

        $pdfContent = Pdf::loadView('invoices.pdf', compact('invoice', 'logoBase64', 'signatureBase64', 'organization'))
            ->setPaper('a4')
            ->output();

        $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number) . '.pdf';

        $mailable = new InvoiceMailable($invoice, $pdfContent, $safeFilename, $emailLog->subject);

        $mail = Mail::to($emailLog->sent_to);

        if ($emailLog->cc) {
            $mail->cc(array_map('trim', explode(',', $emailLog->cc)));
        }
        if ($emailLog->bcc) {
            $mail->bcc(array_map('trim', explode(',', $emailLog->bcc)));
        }

        $mail->send($mailable);

        $emailLog->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        if ($invoice->status === 'finalized') {
            $invoice->update(['status' => 'sent']);
        }
    }

    public function failed(Throwable $exception): void
    {
        $emailLog = EmailLog::find($this->emailLogId);

        if ($emailLog) {
            $emailLog->update([
                'status' => 'failed',
                'failure_reason' => $exception->getMessage(),
            ]);
        }
    }
}