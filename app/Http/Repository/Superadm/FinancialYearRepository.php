<?php

namespace App\Http\Repository\Superadm;

use App\Models\FinancialYear;
use Exception;
use Illuminate\Support\Facades\Log;

class FinancialYearRepository
{
    public function list()
    {
        return FinancialYear::where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function save($data)
    {
        return FinancialYear::create($data);
    }

    public function edit($id)
    {
        return FinancialYear::where('id', $id)->first();
    }

    public function update($data, $id)
    {
        return FinancialYear::where('id', $id)->update($data);
    }

    public function delete($data, $id)
    {
        return FinancialYear::where('id', $id)->update($data);
    }

    public function updateStatus($data, $id)
    {
        return FinancialYear::where('id', $id)->update($data);
    }
}
