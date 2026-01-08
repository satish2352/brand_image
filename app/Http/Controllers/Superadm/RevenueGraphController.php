<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevenueGraphController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->year ?? now()->year;

        $data = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.payment_status', 'paid')
            ->whereYear('oi.from_date', $year)
            ->select(
                DB::raw("MONTH(oi.from_date) as month"),
                DB::raw("SUM(oi.price) as total_revenue")
            )
            ->groupBy(DB::raw("MONTH(oi.from_date)"))
            ->orderBy(DB::raw("MONTH(oi.from_date)"))
            ->get();

        // Prepare chart arrays
        $months = [];
        $revenues = [];

        for ($m = 1; $m <= 12; $m++) {
            $months[] = date('M', mktime(0,0,0,$m,1));

            $match = $data->firstWhere('month', $m);
            $revenues[] = $match ? (float)$match->total_revenue : 0;
        }

        return view('superadm.reports.revenue-graph', compact(
            'months',
            'revenues',
            'year'
        ));
    }
}
