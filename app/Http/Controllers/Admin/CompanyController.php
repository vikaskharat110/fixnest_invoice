<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::withCount(['invoices', 'salesOrders'])->latest()->paginate(15);

        return view('companies.index', compact('companies'));
    }

    public function create(): View
    {
        return view('companies.form', ['company' => new Company()]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Company::create($data);

        return redirect()->route('companies.index')
            ->with('status', 'Company created successfully.');
    }

    public function edit(Company $company): View
    {
        return view('companies.form', compact('company'));
    }

    public function update(StoreCompanyRequest $request, Company $company): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $company->update($data);

        return redirect()->route('companies.index')
            ->with('status', 'Company updated successfully.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->update(['is_active' => false]);

        return redirect()->route('companies.index')
            ->with('status', 'Company deactivated.');
    }
}
