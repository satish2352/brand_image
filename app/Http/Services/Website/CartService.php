<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\CartRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CartService
{
    public function __construct(private CartRepository $repo) {}

    public function getCartItems()
    {
        $items = $this->repo->getCartItems();

        foreach ($items as $item) {

            $start = Carbon::parse($item->from_date);
            $end   = Carbon::parse($item->to_date);

            $totalPrice = 0;
            $current = $start->copy();

            while ($current->lte($end)) {

                $monthStart = $current->copy()->startOfMonth();
                $monthEnd   = $current->copy()->endOfMonth();

                // Booking range inside this month
                $rangeStart = $current->greaterThan($monthStart) ? $current : $monthStart;
                $rangeEnd   = $end->lessThan($monthEnd) ? $end : $monthEnd;

                $daysInThisMonth = $current->daysInMonth;
                $bookedDays = $rangeStart->diffInDays($rangeEnd) + 1;

                $perDayPrice = $item->price / $daysInThisMonth;
                $totalPrice += $perDayPrice * $bookedDays;

                // Move to next month
                $current = $current->addMonth()->startOfMonth();
            }

            $item->total_days = Carbon::parse($item->from_date)
                ->diffInDays(Carbon::parse($item->to_date)) + 1;

            $item->per_day_price = round($totalPrice / $item->total_days, 2);
            $item->total_price   = round($totalPrice, 2);
        }

        return $items;
    }

    public function addToCart($mediaId)
    {
        $media = DB::table('media_management')
            ->where('id', $mediaId)
            ->select('id', 'price')
            ->first();

        if (!$media) {
            throw new \Exception('Media not found');
        }

        $this->repo->addItem($media->id, $media->price);
    }

    public function addToCartWithDate($mediaId, $from, $to)
    {
        $fromDate = Carbon::parse($from);
        $toDate   = Carbon::parse($to);

        // 🔒 BLOCK already BOOKED dates (order_items only)
        $alreadyBooked = DB::table('order_items')
            ->where('media_id', $mediaId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('from_date', [$from, $to])
                    ->orWhereBetween('to_date', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('from_date', '<=', $from)
                            ->where('to_date', '>=', $to);
                    });
            })
            ->exists();

        if ($alreadyBooked) {
            throw new \Exception('Selected dates are already booked');
        }

        // 🔹 Calculate pricing
        $monthlyPrice = DB::table('media_management')
            ->where('id', $mediaId)
            ->value('price');

        $totalDays = $fromDate->diffInDays($toDate) + 1;
        $totalPrice = 0;
        $current = $fromDate->copy();

        while ($current->lte($toDate)) {

            $daysInMonth = $current->daysInMonth;
            $monthEnd = $current->copy()->endOfMonth();

            $rangeEnd = $toDate->lessThan($monthEnd) ? $toDate : $monthEnd;
            $bookedDays = $current->diffInDays($rangeEnd) + 1;

            $perDay = $monthlyPrice / $daysInMonth;
            $totalPrice += $perDay * $bookedDays;

            $current = $current->addMonth()->startOfMonth();
        }

        $this->repo->addItemWithDate(
            $mediaId,
            $monthlyPrice,
            $from,
            $to,
            round($totalPrice / $totalDays, 2),
            round($totalPrice, 2),
            $totalDays
        );
    }
    public function getBookedDatesByMedia($mediaId)
    {
        return $this->repo->getBookedDatesByMedia($mediaId);
    }

    // =================
    public function updateCartDates($cartItemId, $from, $to)
    {
        $item = $this->repo->getCartItemById($cartItemId);

        if (!$item) {
            throw new \Exception('Cart item not found');
        }

        // 🔒 Availability check
        if ($this->repo->isDateAlreadyBooked($item->media_id, $from, $to)) {
            throw new \Exception('Selected dates are already booked');
        }

        // 🔢 Price calculation
        $fromDate = Carbon::parse($from);
        $toDate   = Carbon::parse($to);

        $totalDays = $fromDate->diffInDays($toDate) + 1;
        $totalPrice = 0;
        $current = $fromDate->copy();

        while ($current->lte($toDate)) {

            $daysInMonth = $current->daysInMonth;
            $monthEnd = $current->copy()->endOfMonth();

            $rangeEnd = $toDate->lessThan($monthEnd) ? $toDate : $monthEnd;
            $bookedDays = $current->diffInDays($rangeEnd) + 1;

            $totalPrice += ($item->price / $daysInMonth) * $bookedDays;
            $current = $current->addMonth()->startOfMonth();
        }

        $this->repo->updateCartDates(
            $cartItemId,
            $from,
            $to,
            round($totalPrice / $totalDays, 2),
            round($totalPrice, 2),
            $totalDays
        );
    }
    public function updateQty($itemId, $qty)
    {
        $this->repo->updateQty($itemId, $qty);
    }

    public function removeItem($itemId)
    {
        $this->repo->removeItem($itemId);
    }

    public function clearCart()
    {
        $this->repo->clearCart();
    }
}
