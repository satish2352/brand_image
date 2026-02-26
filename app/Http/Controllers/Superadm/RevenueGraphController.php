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

        $data = DB::table('orders as o')
            ->whereYear('o.created_at', $year)
            ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED']) // only valid payments
            ->select(
                DB::raw('MONTH(o.created_at) as month'),
                DB::raw('SUM(o.grand_total) as total_revenue') //  FINAL AMOUNT WITH GST
            )
            ->groupBy(DB::raw('MONTH(o.created_at)'))
            ->orderBy(DB::raw('MONTH(o.created_at)'))
            ->get();

        $months = [];
        $revenues = [];

        for ($m = 1; $m <= 12; $m++) {
            $months[] = date('M', mktime(0, 0, 0, $m, 1));

            $match = $data->firstWhere('month', $m);
            $revenues[] = $match ? round($match->total_revenue, 2) : 0;
        }

        return view('superadm.reports.revenue-graph', compact(
            'months',
            'revenues',
            'year'
        ));
    }
}
