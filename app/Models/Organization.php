<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'gst_number',
        'pan_number',
        'support_email',
        'address',
        'logo_path',
        'signature_path',
        'bank_details',
        'invoice_prefix',
        'next_invoice_number',
        'invoice_number_period',
        'next_order_number',
    ];

    /**
     * Whether the normal sequential invoice numbering has issued its first
     * number yet. Once true, the configured starting number is locked.
     *
     * The period marker alone isn't enough: in "never reset" mode no period is
     * ever recorded, but the counter still moves past 1 after the first invoice.
     * Random invoices never touch either field, so they don't count as started.
     */
    public function invoiceSequenceStarted(): bool
    {
        return $this->invoice_number_period !== null || (int) $this->next_invoice_number > 1;
    }

    /**
     * The whole app has exactly one issuing business — this is the singleton
     * row for it, created lazily with sane numbering defaults on first access.
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'invoice_prefix' => 'INV',
        ]);
    }
}
