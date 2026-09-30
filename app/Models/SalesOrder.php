<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class SalesOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'order_number',
        'base_value',
        'tax_percent',
        'total_value',
        'description',
        'order_date',
        'status',
    ];
    protected $casts = [
        'order_date' => 'date',
        'base_value' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];
    // Appended so the wizard (which serializes companies -> customers -> salesOrders
    // straight to JSON for Alpine) gets these without extra plumbing.
    //
    // IMPORTANT — decision (updated): total_value is now TAX-INCLUSIVE, computed as
    // base_value + (base_value * tax_percent / 100) when the order is created or
    // edited. Every capacity check in the app (this model, the wizard's live
    // warning, and InvoiceController's server-side checks) now compares an
    // invoice's tax-inclusive total_amount against this same tax-inclusive figure —
    // one definition of "capacity", used everywhere, no more base-vs-total split.
    protected $appends = [
        'amount_billed',
        'amount_remaining',
        'amount_received',
        'amount_outstanding',
    ];
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'order_id');
    }
    /**
     * Tax-inclusive total billed against this order so far (sum of every
     * non-draft invoice's total_amount). Compared directly against total_value,
     * since both are tax-inclusive now.
     */
    public function getAmountBilledAttribute(): float
    {
        return (float) $this->invoices()->where('status', '!=', 'draft')->sum('total_amount');
    }
    public function getAmountRemainingAttribute(): float
    {
        return max(0, (float) $this->total_value - $this->amount_billed);
    }
    public function getAmountReceivedAttribute(): float
    {
        return (float) Payment::whereIn('invoice_id', $this->invoices()->where('status', '!=', 'draft')->pluck('id'))
            ->sum('amount');
    }
    public function getAmountOutstandingAttribute(): float
    {
        return max(0, $this->amount_billed - $this->amount_received);
    }
}