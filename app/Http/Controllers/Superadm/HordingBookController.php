<?php

namespace App\Http\Controllers\Superadm;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Services\Superadm\HordingBookService;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\DB;


class HordingBookController extends Controller
{
    public function __construct(private HordingBookService $homeService) {}

    public function index()
    {
        $filters = [];
        $mediaList = $this->homeService->searchMedia($filters);

        return view('superadm.admin-booking.search', compact('mediaList', 'filters'));
    }

    public function search(Request $request)
    {
        if ($request->filled('clear')) {
            return redirect()->route('admin-booking.index');
        }

        $filters = $request->only([
            'category_id',
            'state_id',
            'district_id',
            'city_id',
            'area_id',
            'radius_id',
            'from_date',
            'to_date',
            'area_type',
            'available_days',
        ]);

        $mediaList = $this->homeService->searchMedia($filters);

        // 🔥 Lazy load AJAX
        if ($request->ajax()) {
            return view('superadm.admin-booking.admin-media-home-list', [
                'mediaList' => $mediaList
            ])->render();
        }


        return view('superadm.admin-booking.search', compact('mediaList', 'filters'));
    }
    public function getMediaDetailsAdmin($mediaId)
    {
        //  Decode ID ONCE
        $decodedId = base64_decode($mediaId, true);

        if ($decodedId === false || !is_numeric($decodedId)) {
            abort(404);
        }

        $media = $this->homeService->getMediaDetailsAdmin((int)$decodedId);

        if (!$media) {
            abort(404);
        }

        //  USE DECODED ID HERE
        $bookedRanges = DB::table('order_items')
            ->where('media_id', $decodedId)
            ->select('from_date', 'to_date')
            ->get();

        return view(
            'superadm.admin-booking.admin-media-details',
            compact('media', 'bookedRanges')
        );
    }


    public function bookMedia(Request $request, HordingBookService $service)
    {
        $request->validate([
            'signup_name'          => 'required|string|min:3|max:255',
            'signup_email'         => 'required|email|max:255',
            'signup_mobile_number' => 'required|digits_between:10,12',

            'media_id'     => 'required',
            'from_date'    => 'required|date',
            'to_date'      => 'required|date|after_or_equal:from_date',

            'total_amount' => 'required|numeric|min:0',
            'gst_amount'   => 'required|numeric|min:0',
            'grand_total'  => 'required|numeric|min:0',
        ]);

        try {
            $service->handleAdminBooking($request);

            return redirect()
                ->route('admin.booking.list-booking')
                ->with('success', 'Media booked successfully');
        } catch (\Throwable $e) {

            Log::error('Admin Booking Error', [
                'error' => $e->getMessage(),
                'data'  => $request->all()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function bookingList()
    {
        $payments = $this->homeService->bookingList();
        return view('superadm.admin-booking.list-booking', compact('payments'));
    }

    public function bookingDetailsList($orderId)
    {
        $orderId = base64_decode($orderId);

        $order = $this->homeService->bookingDetailsList($orderId);
        return view('superadm.admin-booking.list-booking-details', compact('order'));
    }
}
