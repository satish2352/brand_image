<?php

namespace App\Http\Repository\Superadm;

use Illuminate\Support\Facades\DB;

class WebsiteUserRepository
{
    public function list()
    {
        return DB::table('website_users')
            ->where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function delete($id)
    {
        // 🔍 Check if user has orders
        $hasOrders = DB::table('orders')
            ->where('user_id', $id)
            ->exists();

        if ($hasOrders) {
            return false; // ❌ do not delete
        }

        // ✅ safe to delete
        return DB::table('website_users')
            ->where('id', $id)
            ->update(['is_deleted' => 1]);
    }

    public function toggleStatus($id)
    {
        $user = DB::table('website_users')->where('id', $id)->first();

        return DB::table('website_users')
            ->where('id', $id)
            ->update([
                'is_active' => $user->is_active ? 0 : 1
            ]);
    }

    public function getById($id)
    {
        // Named columns rather than select *: this row is returned straight to
        // the browser as JSON, and the details modal has no use for the
        // password hash, the live OTP or the remember token.
        return DB::table('website_users')
            ->select(
                'id',
                'name',
                'email',
                'mobile_number',
                'city',
                'user_type',
                'organisation',
                'gst',
                'is_active',
                'created_at'
            )
            ->where('id', $id)
            ->first();
    }
}
