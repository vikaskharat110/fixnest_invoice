<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $pdfContent,
        public string $pdfFilename,
        public ?string $customSubject = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->customSubject
                ?? "Invoice {$this->invoice->invoice_number} from " . Organization::current()->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: ['invoice' => $this->invoice, 'organization' => Organization::current()],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}