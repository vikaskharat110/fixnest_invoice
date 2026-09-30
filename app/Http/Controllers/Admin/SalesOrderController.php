<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    public function index(Request $request): View
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $orders = null;
        $groupedCompanies = null;
        $activeCompany = null;

        if ($request->filled('company_id')) {
            $activeCompany = Company::findOrFail($request->integer('company_id'));

            $orders = SalesOrder::query()
                ->where('company_id', $activeCompany->id)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->string('search')->trim()->toString();
                    $q->where('order_number', 'like', "%{$search}%");
                })
                ->orderByDesc('order_date')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();
        } else {
            $orderFilter = function ($q) use ($request) {
                $q->when($request->filled('status'), fn ($qq) => $qq->where('status', $request->string('status')))
                    ->when($request->filled('search'), function ($qq) use ($request) {
                        $search = $request->string('search')->trim()->toString();
                        $qq->where('order_number', 'like', "%{$search}%");
                    });
            };

            $groupedCompanies = Company::query()
                ->with(['salesOrders' => $orderFilter])
                ->withCount(['salesOrders' => $orderFilter])
                ->withSum(['salesOrders' => $orderFilter], 'total_value')
                ->whereHas('salesOrders', $orderFilter)
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->string('search')->trim()->toString();
                    $q->orWhere('name', 'like', "%{$request->string('search')->trim()}%");
                })
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        }

        return view('orders.index', compact('orders', 'companies', 'groupedCompanies', 'activeCompany'));
    }

    public function show(SalesOrder $order): View
    {
        $order->load('company');
        $invoices = $order->invoices()->orderByDesc('invoice_date')->get();

        return view('orders.show', compact('order', 'invoices'));
    }

    public function edit(SalesOrder $order): View
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('orders.edit', compact('order', 'companies'));
    }

    public function update(Request $request, SalesOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'base_value' => ['required', 'numeric', 'min:0.01'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
            'order_date' => ['required', 'date'],
            'status' => ['required', 'in:open,completed,cancelled'],
        ]);

        $validated['total_value'] = round($validated['base_value'] + ($validated['base_value'] * $validated['tax_percent'] / 100), 2);

        $order->update($validated);

        return redirect()->route('orders.show', $order)
            ->with('status', 'Sales order updated.');
    }
}