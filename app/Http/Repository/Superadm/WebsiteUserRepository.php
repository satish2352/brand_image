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
        return DB::table('website_users')
            ->where('id', $id)
            ->first();
    }

}
