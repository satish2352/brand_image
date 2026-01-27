<?php

namespace App\Http\Services\Superadm;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Mail\AdminBookingCredentialsMail;
use App\Http\Repository\Superadm\HordingBookRepository;
use Exception;


class HordingBookService
{
    protected HordingBookRepository $repo;

    public function __construct(HordingBookRepository $repo)
    {
        $this->repo = $repo;
    }

    public function searchMedia(array $filters)
    {
        $data_output = $this->repo->searchMedia($filters);
        return $data_output;
    }


    public function handleAdminBooking($request)
    {
        DB::transaction(function () use ($request) {

            // 1️⃣ USER
            $user = $this->createOrGetUser(
                $request->signup_name,
                $request->signup_email,
                $request->signup_mobile_number
            );

            // 2️⃣ ORDER
            $orderId = DB::table('orders')->insertGetId([
                'user_id'        => $user->id,
                'order_no'       => 'ORD-' . time(),
                'total_amount' => $request->total_amount ?? 0,
                'gst_amount'   => $request->gst_amount ?? 0,
                'grand_total'  => $request->grand_total ?? 0,
                'payment_status' => 'ADMIN_BOOKED',
                'created_at'     => now(),
            ]);

            // 3️⃣ ORDER ITEM
            DB::table('order_items')->insert([
                'order_id'  => $orderId,
                'media_id'  => base64_decode($request->media_id),
                'from_date' => $request->from_date,
                'to_date'   => $request->to_date,
                'price'     => $request->total_amount,
                'qty'       => 1,
                'created_at' => now(),
            ]);

            // 4️⃣ BLOCK / UPDATE MEDIA DATE
            $this->repo->blockMediaDates(
                base64_decode($request->media_id),
                $request->from_date,
                $request->to_date
            );
        });

        return true;
    }

    public function createOrGetUser($name, $email, $mobile)
    {
        $user = DB::table('website_users')->where('email', $email)->first();

        if ($user) {
            return (object) $user;
        }

        $password = Str::random(10);

        $userId = DB::table('website_users')->insertGetId([
            'name'           => $name,
            'email'          => $email,
            'mobile_number'  => $mobile,
            'password'       => bcrypt($password),
            'created_at'     => now(),
        ]);

        Mail::to($email)->send(
            new AdminBookingCredentialsMail($name, $email, $password)
        );

        return DB::table('website_users')->where('id', $userId)->first();
    }
    public function getMediaDetailsAdmin($mediaId)
    {
        try {
            $data_output = $this->repo->getMediaDetailsAdmin($mediaId);

            return $data_output;
        } catch (Exception $e) {

            Log::error('HomeService getMediaDetailsAdmin Error', [
                'media_id' => $mediaId,
                'message'  => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function bookingList()
    {
        return $this->repo->bookingList();
    }
    public function bookingDetailsList($orderId)
    {
        return $this->repo->bookingDetailsList($orderId);
    }
}
