<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected array $validTypes = ['gst', 'revenue', 'outstanding', 'trend'];

    public function index(Request $request): View
    {
        $type = in_array($request->get('type'), $this->validTypes, true) ? $request->get('type') : 'gst';

        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $report = match ($type) {
            'gst' => $this->buildGstSummary($request),
            'revenue' => $this->buildRevenue($request),
            'outstanding' => $this->buildOutstanding($request, false),
            'trend' => $this->buildTrend($request),
        };

        return view('reports.index', [
            'type' => $type,
            'companies' => $companies,
            'report' => $report,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $type = in_array($request->get('type'), $this->validTypes, true) ? $request->get('type') : 'gst';

        $report = match ($type) {
            'gst' => $this->buildGstSummary($request),
            'revenue' => $this->buildRevenue($request),
            'outstanding' => $this->buildOutstanding($request, true),
            'trend' => $this->buildTrend($request),
        };

        $filename = Str::slug($report['title']) . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $report['headers']);
            foreach ($report['rows'] as $row) {
                fputcsv($handle, $row);
            }
            if (!empty($report['totalsRow'])) {
                fputcsv($handle, $report['totalsRow']);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function baseInvoiceQuery(Request $request)
    {
        return Invoice::query()
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('invoice_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('invoice_date', '<=', $request->date('date_to')));
    }

    protected function buildGstSummary(Request $request): array
    {
        $includeDrafts = $request->boolean('include_drafts');

        $rows = $this->baseInvoiceQuery($request)
            ->when(!$includeDrafts, fn ($q) => $q->where('status', '!=', 'draft'))
            ->select(
                DB::raw("DATE_FORMAT(invoice_date, '%Y-%m') as period"),
                DB::raw('SUM(subtotal) as taxable_value'),
                DB::raw('SUM(cgst_amount) as cgst'),
                DB::raw('SUM(sgst_amount) as sgst'),
                DB::raw('SUM(igst_amount) as igst'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(total_amount) as total_value'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $totals = [
            'taxable_value' => $rows->sum('taxable_value'),
            'cgst' => $rows->sum('cgst'),
            'sgst' => $rows->sum('sgst'),
            'igst' => $rows->sum('igst'),
            'total_tax' => $rows->sum('total_tax'),
            'total_value' => $rows->sum('total_value'),
            'invoice_count' => $rows->sum('invoice_count'),
        ];

        return [
            'title' => 'GST Summary',
            'headers' => ['Period', 'Invoices', 'Taxable Value', 'CGST', 'SGST', 'IGST', 'Total Tax', 'Total Value'],
            'rows' => $rows->map(fn ($r) => [
                $r->period, $r->invoice_count,
                number_format($r->taxable_value, 2), number_format($r->cgst, 2),
                number_format($r->sgst, 2), number_format($r->igst, 2),
                number_format($r->total_tax, 2), number_format($r->total_value, 2),
            ])->all(),
            'totalsRow' => [
                'Total', $totals['invoice_count'],
                number_format($totals['taxable_value'], 2), number_format($totals['cgst'], 2),
                number_format($totals['sgst'], 2), number_format($totals['igst'], 2),
                number_format($totals['total_tax'], 2), number_format($totals['total_value'], 2),
            ],
            'totals' => $totals,
        ];
    }

    protected function buildRevenue(Request $request): array
    {
        $rows = $this->baseInvoiceQuery($request)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->join('companies', 'companies.id', '=', 'invoices.company_id')
            ->select(
                'companies.name as entity_name',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(invoices.subtotal) as subtotal'),
                DB::raw('SUM(invoices.tax_amount) as tax_amount'),
                DB::raw('SUM(invoices.total_amount) as total_amount')
            )
            ->groupBy('companies.id', 'companies.name')
            ->orderByDesc('total_amount')
            ->get();

        $totals = [
            'invoice_count' => $rows->sum('invoice_count'),
            'subtotal' => $rows->sum('subtotal'),
            'tax_amount' => $rows->sum('tax_amount'),
            'total_amount' => $rows->sum('total_amount'),
        ];

        return [
            'title' => 'Revenue by Company',
            'headers' => ['Company', 'Invoices', 'Subtotal', 'Tax', 'Total'],
            'rows' => $rows->map(fn ($r) => [
                $r->entity_name, $r->invoice_count,
                number_format($r->subtotal, 2), number_format($r->tax_amount, 2), number_format($r->total_amount, 2),
            ])->all(),
            'totalsRow' => [
                'Total', $totals['invoice_count'],
                number_format($totals['subtotal'], 2), number_format($totals['tax_amount'], 2), number_format($totals['total_amount'], 2),
            ],
            'totals' => $totals,
        ];
    }

    protected function buildOutstanding(Request $request, bool $forExport = false): array
    {
        $baseQuery = fn () => Invoice::query()
            ->whereIn('status', ['finalized', 'sent'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')));

        $today = now()->startOfDay();

        // Summary cards always reflect the FULL filtered set, not just the current page.
        $totalOutstanding = $baseQuery()->sum('total_amount');
        $count = $baseQuery()->count();
        $overdueCount = $baseQuery()->whereNotNull('due_date')->whereDate('due_date', '<', $today)->count();

        $paginator = null;

        if ($forExport) {
            $invoices = $baseQuery()->with('company:id,name')->orderBy('due_date')->get();
        } else {
            $paginator = $baseQuery()->with('company:id,name')
                ->orderBy('due_date')
                ->paginate(50)
                ->withQueryString();
            $invoices = collect($paginator->items());
        }

        $rows = $invoices->map(function ($inv) use ($today) {
            $daysOverdue = $inv->due_date ? $today->diffInDays($inv->due_date, false) : null;
            $overdueLabel = $daysOverdue === null
                ? '—'
                : ($daysOverdue < 0 ? abs((int) $daysOverdue) . ' days overdue' : 'Due in ' . (int) $daysOverdue . ' days');

            return [
                $inv->invoice_number,
                $inv->buyer_name,
                $inv->company->name,
                $inv->invoice_date->format('d M Y'),
                $inv->due_date?->format('d M Y') ?? '—',
                $overdueLabel,
                number_format($inv->total_amount, 2),
                ucfirst($inv->status),
            ];
        });

        return [
            'title' => 'Outstanding Invoices',
            'headers' => ['Invoice #', 'Buyer', 'Company', 'Invoice Date', 'Due Date', 'Status', 'Amount', 'Invoice Status'],
            'rows' => $rows->all(),
            'totalsRow' => ['Total', '', '', '', '', '', number_format($totalOutstanding, 2), ''],
            'totals' => ['total_outstanding' => $totalOutstanding, 'count' => $count, 'overdue_count' => $overdueCount],
            'paginator' => $paginator,
        ];
    }
    protected function buildTrend(Request $request): array
    {
        $rows = $this->baseInvoiceQuery($request)
            ->where('status', '!=', 'draft')
            ->select(
                DB::raw("DATE_FORMAT(invoice_date, '%Y-%m') as period"),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(subtotal) as subtotal'),
                DB::raw('SUM(tax_amount) as tax_amount'),
                DB::raw('SUM(total_amount) as total_amount')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $maxTotal = $rows->max('total_amount') ?: 1;

        $totals = [
            'invoice_count' => $rows->sum('invoice_count'),
            'subtotal' => $rows->sum('subtotal'),
            'tax_amount' => $rows->sum('tax_amount'),
            'total_amount' => $rows->sum('total_amount'),
        ];

        return [
            'title' => 'Monthly Trend',
            'headers' => ['Period', 'Invoices', 'Subtotal', 'Tax', 'Total'],
            'rows' => $rows->map(fn ($r) => [
                $r->period, $r->invoice_count,
                number_format($r->subtotal, 2), number_format($r->tax_amount, 2), number_format($r->total_amount, 2),
            ])->all(),
            'totalsRow' => [
                'Total', $totals['invoice_count'],
                number_format($totals['subtotal'], 2), number_format($totals['tax_amount'], 2), number_format($totals['total_amount'], 2),
            ],
            'totals' => $totals,
            'chartRows' => $rows->map(fn ($r) => [
                'period' => $r->period,
                'total' => (float) $r->total_amount,
                'pct' => round(((float) $r->total_amount / $maxTotal) * 100, 1),
            ])->all(),
        ];
    }
}