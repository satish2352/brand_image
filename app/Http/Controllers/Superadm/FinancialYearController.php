<?php

namespace App\Http\Controllers\Superadm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\FinancialYearService;
use Illuminate\Validation\Rule;
use Exception;

class FinancialYearController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new FinancialYearService();
    }

    public function index()
    {
        $years = $this->service->list();
        return view('superadm.financial_year.list', compact('years'));
    }

    public function create()
    {
        return view('superadm.financial_year.create');
    }

    public function save(Request $req)
    {
        $req->validate([
            'year' => [
                'required',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique('financial_years', 'year')->where(fn($q) => $q->where('is_deleted', 0))->ignore($req->id),
                function ($attribute, $value, $fail) {
                    // Split the years
                    [$start, $end] = explode('-', $value);
                    if ((int)$start >= (int)$end) {
                        $fail('The financial year must start with a smaller year and end with a larger year.');
                    }
                }
            ],
        ], [
            'year.required' => 'Enter financial year',
            'year.regex' => 'Format must be YYYY-YYYY (e.g., 2025-2026)',
            'year.unique' => 'This financial year already exists.',
        ]);

        $this->service->save($req);
        return redirect()->route('financial-year.list')->with('success', 'Financial Year added.');
    }

    public function edit($encodedId)
    {
        $id = base64_decode($encodedId);
        $data = $this->service->edit($id);
        return view('superadm.financial_year.edit', compact('data', 'encodedId'));
    }

    public function update(Request $req)
    {
        $req->validate([
            'year' => [
                'required',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique('financial_years', 'year')->where(fn($q) => $q->where('is_deleted', 0))->ignore($req->id),
                function ($attribute, $value, $fail) {
                    // Split the years
                    [$start, $end] = explode('-', $value);
                    if ((int)$start >= (int)$end) {
                        $fail('The financial year must start with a smaller year and end with a larger year.');
                    }
                }
            ],
        ], [
            'year.required' => 'Enter financial year',
            'year.regex' => 'Format must be YYYY-YYYY (e.g., 2025-2026)',
            'year.unique' => 'This financial year already exists.',
        ]);

            // Get the old financial year before updating
    $oldData = $this->service->edit($req->id);

    $this->service->update($req);

    return redirect()->route('financial-year.list')
                     ->with('success', "Financial Year {$oldData->year} updated successfully to {$req->year}.");
    }

public function delete(Request $req)
{
    $req->validate(['id' => 'required']);

    $data = $this->service->edit(base64_decode($req->id));
    $this->service->delete($req);

    return response()->json([
        'status' => true,
        'message' => "Financial Year {$data->year} deleted successfully."
    ]);
}


public function updateStatus(Request $req)
{
    $id = base64_decode($req->id);
    $data = $this->service->edit($id);

    $this->service->updateStatus($req);

    $statusText = $req->is_active ? 'activated' : 'deactivated';

    return response()->json([
        'status' => true,
        'message' => "Financial Year {$data->year} {$statusText} successfully."
    ]);
}

}
