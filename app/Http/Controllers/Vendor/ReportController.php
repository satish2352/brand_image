<?php

namespace App\Http\Controllers\Vendor;

use App\Exports\VendorBrandedExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Media Utilisation Report (PRD 2.6) — the vendor's own inventory only.
 *
 * For each media item: days booked and blocked inside the chosen period
 * (ranges clipped to the period), the users who booked it, and utilisation =
 * booked days / days in period.
 */
class ReportController extends Controller
{
    private function vendor()
    {
        return auth('vendor')->user();
    }

    /** [from, to] from the filters: explicit dates win, then year/month, then this month. */
    private function period(Request $request): array
    {
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->startOfDay();
        } elseif ($request->filled('year') && $request->filled('month')) {
            $from = Carbon::create((int) $request->year, (int) $request->month, 1);
            $to   = $from->copy()->endOfMonth()->startOfDay();
        } elseif ($request->filled('year')) {
            $from = Carbon::create((int) $request->year, 1, 1);
            $to   = $from->copy()->endOfYear()->startOfDay();
        } else {
            $from = now()->startOfMonth();
            $to   = now()->endOfMonth()->startOfDay();
        }

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function build(Request $request): array
    {
        $vendor = $this->vendor();
        [$from, $to] = $this->period($request);
        $days = $from->diffInDays($to) + 1;

        $media = DB::table('media_management as m')
            ->leftJoin('areas as a', 'a.id', '=', 'm.area_id')
            ->leftJoin('cities as cty', 'cty.id', '=', 'm.city_id')
            ->where('m.vendor_id', $vendor->id)
            ->where('m.is_deleted', 0)
            ->where('m.approval_status', 'approved')
            ->when($request->filled('media_id'), fn ($q) => $q->where('m.id', (int) $request->media_id))
            ->orderBy('m.hoarding_code')
            ->get(['m.id', 'm.hoarding_code', 'm.media_code', 'm.media_title', 'a.area_name', 'cty.city_name']);

        $ids = $media->pluck('id');

        $ranges = DB::table('media_booked_date')
            ->whereIn('media_id', $ids)
            ->where('is_active', 1)->where('is_deleted', 0)
            ->where('from_date', '<=', $to->toDateString())
            ->where('to_date', '>=', $from->toDateString())
            ->get()->groupBy('media_id');

        $users = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('website_users as u', 'u.id', '=', 'o.user_id')
            ->whereIn('oi.media_id', $ids)
            ->whereIn('o.payment_status', ['PAID', 'ADMIN_BOOKED'])
            ->where('oi.is_deleted', 0)
            ->where('oi.from_date', '<=', $to->toDateString())
            ->where('oi.to_date', '>=', $from->toDateString())
            ->get(['oi.media_id', 'u.name', 'o.order_no'])
            ->groupBy('media_id');

        $rows = $media->map(function ($m) use ($ranges, $users, $from, $to, $days) {
            $booked = 0;
            $blocked = 0;
            foreach ($ranges->get($m->id, []) as $r) {
                $start = max($from->timestamp, Carbon::parse($r->from_date)->startOfDay()->timestamp);
                $end   = min($to->timestamp, Carbon::parse($r->to_date)->startOfDay()->timestamp);
                $span  = (int) round(($end - $start) / 86400) + 1;
                if (($r->status_type ?? 'booked') === 'blocked') {
                    $blocked += max(0, $span);
                } else {
                    $booked += max(0, $span);
                }
            }
            $booked  = min($booked, $days);
            $blocked = min($blocked, $days - $booked);

            $bookers = collect($users->get($m->id, []));

            return (object) [
                'id'       => $m->id,
                'code'     => $m->hoarding_code ?: $m->media_code,
                'title'    => $m->media_title,
                'location' => trim(($m->area_name ?? '') . ', ' . ($m->city_name ?? ''), ', '),
                'users'    => $bookers->pluck('name')->filter()->unique()->implode(', '),
                'orders'   => $bookers->pluck('order_no')->unique()->count(),
                'booked'   => $booked,
                'blocked'  => $blocked,
                'free'     => $days - $booked - $blocked,
                'pct'      => $days ? (int) round($booked / $days * 100) : 0,
            ];
        });

        if ($term = trim((string) $request->q)) {
            $rows = $rows->filter(fn ($r) => stripos($r->code . ' ' . $r->title . ' ' . $r->location . ' ' . $r->users, $term) !== false)->values();
        }

        return [
            'rows'    => $rows,
            'from'    => $from,
            'to'      => $to,
            'days'    => $days,
            'summary' => [
                'media'    => $rows->count(),
                'booked'   => $rows->sum('booked'),
                'avg'      => $rows->count() ? (int) round($rows->avg('pct')) : 0,
                'bookings' => $rows->sum('orders'),
            ],
        ];
    }

    public function utilisation(Request $request)
    {
        $vendor = $this->vendor();

        // The figures are worked out per media for the period (and the summary needs them all),
        // so the table is split into pages here; Excel / PDF export still gets every row.
        $data = $this->build($request);
        $perPage = 15;
        $page = max(1, (int) $request->query('page', 1));
        $data['rows'] = (new \Illuminate\Pagination\LengthAwarePaginator(
            $data['rows']->forPage($page, $perPage)->values(), $data['rows']->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        ));
        if ($data['rows']->isEmpty() && $page > 1) {
            return redirect()->to($data['rows']->url($data['rows']->lastPage()));
        }

        return view('vendor.reports.utilisation', $data + [
            'allMedia' => DB::table('media_management')->where('vendor_id', $vendor->id)->where('is_deleted', 0)
                ->where('approval_status', 'approved')->orderBy('hoarding_code')->get(['id', 'hoarding_code', 'media_code', 'media_title']),
            'years'    => getYears(),
            'months'   => getMonths(),
        ]);
    }

    public function export(Request $request, string $format)
    {
        $vendor = $this->vendor();
        $data   = $this->build($request);
        $name   = Str::slug($vendor->vendor_name) . '-utilisation-' . $data['from']->format('Ymd') . '-' . $data['to']->format('Ymd');

        if ($format === 'pdf') {
            return Pdf::loadView('vendor.reports.utilisation-pdf', $data + ['vendor' => $vendor])
                ->setPaper('a4', 'landscape')
                ->download($name . '.pdf');
        }

        $rows = $data['rows']->values()->map(fn ($r, $i) => [
            $i + 1, $r->code, $r->title, $r->location, $r->users ?: '—', $r->booked, $r->blocked, $r->free, $r->pct . '%',
        ])->all();

        return Excel::download(new VendorBrandedExport(
            $vendor,
            'Media Utilisation ' . $data['from']->format('d M Y') . ' to ' . $data['to']->format('d M Y'),
            ['Sr. No.', 'Sector Code', 'Media Title', 'Location', 'Booked By', 'Booked Days', 'Blocked Days', 'Free Days', 'Utilisation'],
            $rows
        ), $name . '.xlsx');
    }
}
