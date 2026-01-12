<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class RevenueReportController extends Controller
{
    public function index(Request $request)
    {
        // Month without year validation
        if ($request->filled('month') && !$request->filled('year')) {
            return redirect()
                ->back()
                ->withErrors(['year' => 'Year is required when month is selected'])
                ->withInput();
        }

        $type = $request->report_type ?? 'date'; // date | media | user
        $query = $this->baseQuery($request);

        /* ======================
           REPORT TYPE LOGIC
        ======================= */

        if ($type === 'date') {

            $query->select(
                DB::raw("DATE_FORMAT(oi.from_date, '%b %Y') as period"),
                DB::raw('COUNT(oi.id) as total_bookings'),
                DB::raw('SUM(oi.price) as total_revenue'),
                DB::raw('MIN(oi.from_date) as sort_date')
            )
                ->groupBy(DB::raw("DATE_FORMAT(oi.from_date, '%b %Y')"))
                ->orderBy('sort_date', 'desc');
        } elseif ($type === 'media') {

            $query->select(
                'm.id',
                'm.media_code',
                'm.media_title',
                'cat.category_name as category_name',
                's.state_name as state_name',
                'd.district_name as district_name',
                'c.city_name as city_name',
                'a.area_name as area_name',
                'm.width',
                'm.height',
                DB::raw('COUNT(oi.id) as total_bookings'),
                DB::raw('SUM(DATEDIFF(oi.to_date, oi.from_date) + 1) as booked_days'),
                DB::raw('SUM(oi.price) as total_revenue')
            )
                ->groupBy(
                    'm.id',
                    'm.media_code',
                    'm.media_title',
                    'cat.category_name',
                    's.state_name',
                    'd.district_name',
                    'c.city_name',
                    'a.area_name',
                    'm.width',
                    'm.height'
                )
                ->orderByDesc('total_revenue');
        } else { // USER-WISE

            $query->select(
                'u.id',
                'u.name as user_name',
                DB::raw('COUNT(oi.id) as total_bookings'),
                DB::raw('SUM(DATEDIFF(oi.to_date, oi.from_date) + 1) as booked_days'),
                DB::raw('SUM(oi.price) as total_revenue')
            )
                ->groupBy('u.id', 'u.name')
                ->orderByDesc('total_revenue');
        }

        $reports = $query->paginate(10)->withQueryString();

        return view('superadm.reports.revenue-report', compact('reports', 'type'));
    }

    /* ======================
       BASE QUERY
    ======================= */

    private function baseQuery(Request $request)
    {
        $query = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('website_users as u', 'u.id', '=', 'o.user_id')
            ->join('media_management as m', 'm.id', '=', 'oi.media_id')
            ->join('category as cat', 'cat.id', '=', 'm.category_id')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('cities as c', 'c.id', '=', 'm.city_id')
            ->leftJoin('districts as d', 'd.id', '=', 'm.district_id')
            ->leftJoin('states as s', 's.id', '=', 'm.state_id')
            ->where('o.payment_status', 'paid')
            ->where('m.is_deleted', 0);

        // Year filter
        if ($request->year) {
            $query->whereYear('oi.from_date', $request->year);
        }

        // Month filter
        if ($request->month) {
            $query->whereMonth('oi.from_date', $request->month);
        }

        // Search filter (server-side)
        // if ($request->search) {
        //     $search = $request->search;
        //     $query->where(function ($q) use ($search) {
        //         $q->where('m.media_title', 'like', "%$search%")
        //         ->orWhere('m.media_code', 'like', "%$search%")
        //         ->orWhere('u.name', 'like', "%$search%")
        //         ->orWhere('cat.category_name', 'like', "%$search%");
        //     });
        // }

        if ($request->filled('search')) {

            $search = trim($request->search);

            preg_match('/\b(20\d{2})\b/', $search, $yearMatch);
            $searchYear = $yearMatch[1] ?? null;

            $monthMap = [
                'jan' => 1,
                'january' => 1,
                'feb' => 2,
                'february' => 2,
                'mar' => 3,
                'march' => 3,
                'apr' => 4,
                'april' => 4,
                'may' => 5,
                'jun' => 6,
                'june' => 6,
                'jul' => 7,
                'july' => 7,
                'aug' => 8,
                'august' => 8,
                'sep' => 9,
                'september' => 9,
                'oct' => 10,
                'october' => 10,
                'nov' => 11,
                'november' => 11,
                'dec' => 12,
                'december' => 12,
            ];

            $searchMonth = null;
            foreach ($monthMap as $name => $num) {
                if (stripos($search, $name) !== false) {
                    $searchMonth = $num;
                    break;
                }
            }

            $query->where(function ($q) use ($search, $searchYear, $searchMonth) {

                $q->where('m.media_title', 'like', "%{$search}%")
                    ->orWhere('m.media_code', 'like', "%{$search}%")
                    ->orWhere('u.name', 'like', "%{$search}%")
                    ->orWhere('cat.category_name', 'like', "%{$search}%");

                if ($searchYear) {
                    $q->orWhereYear('oi.from_date', $searchYear);
                }

                if ($searchMonth) {
                    $q->orWhereMonth('oi.from_date', $searchMonth);
                }

                if ($searchYear && $searchMonth) {
                    $q->orWhere(function ($qq) use ($searchYear, $searchMonth) {
                        $qq->whereYear('oi.from_date', $searchYear)
                            ->whereMonth('oi.from_date', $searchMonth);
                    });
                }
            });
        }

        return $query;
    }


    private function exportQuery(Request $request)
    {
        $type = $request->report_type ?? 'date';
        $query = $this->baseQuery($request);

        if ($type === 'date') {
            $query->select(
                DB::raw("DATE_FORMAT(oi.from_date, '%b %Y') as period"),
                DB::raw('COUNT(oi.id) as total_bookings'),
                DB::raw('SUM(oi.price) as total_revenue')
            )
                ->groupBy(DB::raw("DATE_FORMAT(oi.from_date, '%b %Y')"))
                ->orderBy(DB::raw('MIN(oi.from_date)'), 'desc');
        } elseif ($type === 'media') {
            $query->select(
                'm.media_code',
                'cat.category_name',
                'm.media_title',
                's.state_name as state_name',
                'd.district_name as district_name',
                'c.city_name as city_name',
                'a.area_name',
                'm.width',
                'm.height',
                DB::raw('COUNT(oi.id) as total_bookings'),
                DB::raw('SUM(DATEDIFF(oi.to_date, oi.from_date) + 1) as booked_days'),
                DB::raw('SUM(oi.price) as total_revenue')
            )
                ->groupBy(
                    'm.media_code',
                    'cat.category_name',
                    'm.media_title',
                    's.state_name',
                    'd.district_name',
                    'c.city_name',
                    'a.area_name',
                    'm.width',
                    'm.height'
                )
                ->orderByDesc('total_revenue');
        } else { // user
            $query->select(
                'u.name as user_name',
                DB::raw('COUNT(oi.id) as total_bookings'),
                DB::raw('SUM(DATEDIFF(oi.to_date, oi.from_date) + 1) as booked_days'),
                DB::raw('SUM(oi.price) as total_revenue')
            )
                ->groupBy('u.name')
                ->orderByDesc('total_revenue');
        }

        return $query->get();
    }


    public function monthDetails(Request $request)
    {
        [$month, $year] = explode(' ', $request->period);

        $monthNum = date('m', strtotime($month));

        $data = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('media_management as m', 'm.id', '=', 'oi.media_id')
            ->join('category as c', 'c.id', '=', 'm.category_id')
            ->where('o.payment_status', 'paid')
            ->whereYear('oi.from_date', $year)
            ->whereMonth('oi.from_date', $monthNum)
            ->select(
                'm.media_code',
                'm.media_title',
                'c.category_name',
                DB::raw('DATEDIFF(oi.to_date, oi.from_date) + 1 as booked_days'),
                'oi.price'
            )
            ->get();

        return response()->json($data);
    }

    public function userDetails(Request $request)
    {
        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('media_management as m', 'm.id', '=', 'oi.media_id')
            ->join('category as cat', 'cat.id', '=', 'm.category_id')
            ->where('o.user_id', $request->user_id)
            ->where('o.payment_status', 'paid')
            ->select(
                'm.media_code',
                'm.media_title',
                'cat.category_name',
                'oi.from_date',
                'oi.to_date',
                DB::raw('DATEDIFF(oi.to_date, oi.from_date)+1 as booked_days'),
                'oi.price'
            )
            ->orderBy('oi.from_date', 'desc')
            ->get();

        return response()->json($rows);
    }


    /* ======================
       EXPORTS
    ======================= */

    public function checkExportData(Request $request)
    {
        $data = $this->exportQuery($request);

        return response()->json([
            'hasData' => $data->isNotEmpty()
        ]);
    }


    public function exportExcel(Request $request)
    {
        $data = $this->exportQuery($request);

        if ($data->isEmpty()) {
            return response()->json(['status' => 'empty']);
        }

        return Excel::download(
            new \App\Exports\RevenueExport($data, $request->report_type),
            'revenue_report.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $data = $this->exportQuery($request);

        if ($data->isEmpty()) {
            return response()->json(['status' => 'empty']);
        }

        $pdf = Pdf::loadView(
            'superadm.reports.revenue-report-pdf',
            [
                'reports' => $data,
                'type'    => $request->report_type
            ]
        )->setPaper('A4', 'landscape');

        return $pdf->download('revenue_report.pdf');
    }
}
