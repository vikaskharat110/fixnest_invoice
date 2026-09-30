<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statusCounts = Invoice::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $gstCounts = Invoice::query()
            ->selectRaw('gst_type, count(*) as count')
            ->groupBy('gst_type')
            ->pluck('count', 'gst_type');

        $totalInvoices = $statusCounts->sum();

        $billableQuery = Invoice::query()->where('status', '!=', 'draft');

        $totalRevenue = (clone $billableQuery)->sum('total_amount');
        $totalGst = (clone $billableQuery)->sum('tax_amount');

        $totalReceived = Payment::query()
            ->whereIn('invoice_id', (clone $billableQuery)->pluck('id'))
            ->sum('amount');

        $totalOutstanding = max(0, $totalRevenue - $totalReceived);

        $paidCount = (clone $billableQuery)
            ->withSum('payments', 'amount')
            ->get(['id', 'total_amount'])
            ->filter(function ($invoice) {
                return ($invoice->payments_sum_amount ?? 0) >= $invoice->total_amount - 0.01;
            })
            ->count();

        $pendingCount = (clone $billableQuery)->count() - $paidCount;

        $recentInvoices = Invoice::query()
            ->with('company:id,name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'totalInvoices' => $totalInvoices,
            'draftCount' => $statusCounts['draft'] ?? 0,
            'finalizedCount' => $statusCounts['finalized'] ?? 0,
            'sentCount' => $statusCounts['sent'] ?? 0,
            'archivedCount' => $statusCounts['archived'] ?? 0,
            'gstCount' => $gstCounts['gst'] ?? 0,
            'nonGstCount' => $gstCounts['non_gst'] ?? 0,
            'paidCount' => $paidCount,
            'pendingCount' => $pendingCount,
            'totalRevenue' => $totalRevenue,
            'totalGst' => $totalGst,
            'totalReceived' => $totalReceived,
            'totalOutstanding' => $totalOutstanding,
            'recentInvoices' => $recentInvoices,
        ]);
    }
}