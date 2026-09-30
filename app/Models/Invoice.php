<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'order_id',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'buyer_address',
        'buyer_gstin',
        'buyer_state',
        'created_by',
        'invoice_number',
        'number_source',
        'invoice_type',
        'gst_type',
        'tax_type',
        'status',
        'invoice_date',
        'due_date',
        'reference_number',
        'other_references',
        'delivery_note',
        'buyer_order_number',
        'subtotal',
        'tax_amount',
        'cgst_rate',
        'cgst_amount',
        'sgst_rate',
        'sgst_amount',
        'igst_rate',
        'igst_amount',
        'total_amount',
        'amount_in_words',
        'tax_amount_in_words',
        'template',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'cgst_rate' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_rate' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_rate' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(InvoiceHistory::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getAmountPaidAttribute(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function getAmountOutstandingAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->amount_paid);
    }
}