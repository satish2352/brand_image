<?php

namespace App\Http\Controllers\Superadm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class MediaUtilisationReportController extends Controller
{
public function index(Request $request)
{
    // month without year validation
    if ($request->month && !$request->year) {
        return back()
            ->withErrors(['year' => 'Year is required when month is selected'])
            ->withInput();
    }

    $query = DB::table('order_items as oi')
        ->join('orders as o', 'o.id', '=', 'oi.order_id')
        ->join('website_users as u', 'u.id', '=', 'o.user_id')
        ->join('media_management as m', 'm.id', '=', 'oi.media_id')
        ->join('category as c', 'c.id', '=', 'm.category_id')
        ->where('o.payment_status', 'paid')
        ->where('m.is_deleted', 0);

    // Year / Month
    if ($request->year && $request->month) {
        $query->whereYear('oi.from_date', $request->year)
              ->whereMonth('oi.from_date', $request->month);
    } elseif ($request->year) {
        $query->whereYear('oi.from_date', $request->year);
    }

    // From – To
    if ($request->from_date && $request->to_date) {
        $query->whereBetween('oi.from_date', [
            $request->from_date,
            $request->to_date
        ]);
    }

    // Media
    if ($request->media_id) {
        $query->where('m.id', $request->media_id);
    }

    // Category
    if ($request->category_id) {
        $query->where('c.id', $request->category_id);
    }

    // Search
    if ($request->search) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('m.media_code', 'like', "%$search%")
              ->orWhere('m.media_title', 'like', "%$search%")
              ->orWhere('u.name', 'like', "%$search%");
        });
    }

    /* =========================
       BOOKING-WISE SELECT
    ========================== */
    $reports = $query->select(
            'u.name as user_name',
            'm.media_code',
            'm.media_title',
            'c.category_name',
            'm.width',
            'm.height',
            'oi.from_date',
            'oi.to_date',
            DB::raw('DATEDIFF(oi.to_date, oi.from_date) + 1 as booked_days'),
            'oi.price as booking_amount'
        )
        ->orderBy('oi.from_date', 'desc')
        ->paginate(10)
        ->withQueryString();

    $mediaList = DB::table('media_management')
        ->where('is_deleted', 0)
        ->where('is_active', 1)
        ->select('id', 'media_title')
        ->get();

    $categories = DB::table('category')
        ->where('is_deleted', 0)
        ->where('is_active', 1)
        ->get();

    return view(
        'superadm.reports.media-utilisation',
        compact('reports', 'mediaList', 'categories')
    );
}

private function baseQuery(Request $request)
{
    $query = DB::table('order_items as oi')
        ->join('orders as o', 'o.id', '=', 'oi.order_id')
        ->join('website_users as u', 'u.id', '=', 'o.user_id')
        ->join('media_management as m', 'm.id', '=', 'oi.media_id')
        ->join('category as c', 'c.id', '=', 'm.category_id')
        ->where('o.payment_status', 'paid')
        ->where('m.is_deleted', 0);

    if ($request->year && $request->month) {
        $query->whereYear('oi.from_date', $request->year)
              ->whereMonth('oi.from_date', $request->month);
    } elseif ($request->year) {
        $query->whereYear('oi.from_date', $request->year);
    }

    if ($request->from_date && $request->to_date) {
        $query->whereBetween('oi.from_date', [
            $request->from_date,
            $request->to_date
        ]);
    }

    if ($request->media_id) {
        $query->where('m.id', $request->media_id);
    }

    if ($request->category_id) {
        $query->where('c.id', $request->category_id);
    }

    if ($request->search) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('m.media_code', 'like', "%$search%")
              ->orWhere('m.media_title', 'like', "%$search%")
              ->orWhere('u.name', 'like', "%$search%");
        });
    }

    return $query->select(
        'u.name as user_name',
        'm.media_code',
        'm.media_title',
        'c.category_name',
        'm.width',
        'm.height',
        'oi.from_date',
        'oi.to_date',
        DB::raw('DATEDIFF(oi.to_date, oi.from_date) + 1 as booked_days'),
        'oi.price as booking_amount'
    )->orderBy('oi.from_date', 'desc');
}

public function checkExportData(Request $request)
{
    $data = $this->baseQuery($request)->limit(1)->get();

    return response()->json([
        'hasData' => $data->isNotEmpty()
    ]);
}


public function exportExcel(Request $request)
{
    $data = $this->baseQuery($request)->get();

    return Excel::download(
        new \App\Exports\MediaUtilisationExport($data),
        'media_utilisation_report.xlsx'
    );
}

public function exportPdf(Request $request)
{
    $reports = $this->baseQuery($request)->get();

    $pdf = Pdf::loadView(
        'superadm.reports.media-utilisation-pdf',
        compact('reports')
    )->setPaper('A4', 'landscape');

    return $pdf->download('media_utilisation_report.pdf');
}



}
