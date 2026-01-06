<?php


namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /* ================= CART COUNT (ALL VIEWS) ================= */
        view()->composer('*', function ($view) {

            $cartCount = 0;

            if (Auth::guard('website')->check()) {
                $cartCount = CartItem::where('is_deleted', 0)
                    ->where('is_active', 1)
                    ->where('cart_type', 'NORMAL')
                    ->where('status', 'ACTIVE')
                    ->where('user_id', Auth::guard('website')->id())
                    ->count();
            }

            $view->with('cartCount', $cartCount);
        });

        /* ================= WEBSITE SEARCH FORM ================= */
        view()->composer('website.search-form', function ($view) {

            $categories = DB::table('category')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('id')
                ->get();

            $states = DB::table('tbl_location')
                ->where('location_type', 1)
                ->where('is_active', 1)
                ->get();

            $radiusList = DB::table('radius_master')
                ->where('is_active', 1)
                ->orderBy('radius')
                ->get();

            $view->with(compact('categories', 'states', 'radiusList'));
        });

        /* ================= ADMIN BOOKING SEARCH FORM ================= */
        view()->composer('superadm.admin-booking.search-form', function ($view) {

            $categories = DB::table('category')
                ->where('is_active', 1)
                ->where('is_deleted', 0)
                ->orderBy('id')
                ->get();

            $states = DB::table('tbl_location')
                ->where('location_type', 1)
                ->where('is_active', 1)
                ->get();

            $radiusList = DB::table('radius_master')
                ->where('is_active', 1)
                ->orderBy('radius')
                ->get();

            $view->with(compact('categories', 'states', 'radiusList'));
        });
    }
}

// namespace App\Providers;

// use Illuminate\Support\ServiceProvider;
// use Illuminate\Support\Facades\View;
// use Illuminate\Support\Facades\Auth;
// use App\Models\CartItem;
// use Illuminate\Support\Facades\DB;

// class AppServiceProvider extends ServiceProvider
// {

//     public function register(): void {}


//     public function boot(): void
//     {
//         View::composer('*', function ($view) {

//             $cartCount = 0;

//             if (Auth::guard('website')->check()) {
//                 $cartCount = CartItem::where('is_deleted', 0)
//                     ->where('is_active', 1)
//                     ->where('cart_type', 'NORMAL')
//                     ->where('status', 'ACTIVE')
//                     ->where('user_id', Auth::guard('website')->id())
//                     ->count();
//             }


//             $view->with('cartCount', $cartCount);
//         });


//         View::composer('website.search-form', function ($view) {

//             $categories = DB::table('category')
//                 ->where('is_active', 1)
//                 ->where('is_deleted', 0)
//                 ->orderBy('id', 'asc')
//                 ->get();

//             $states = DB::table('tbl_location')
//                 ->where('location_type', 1)
//                 ->where('name', 'Maharashtra')
//                 ->where('is_active', 1)
//                 ->get();

//             $radiusList = DB::table('radius_master')
//                 ->where('is_active', 1)
//                 ->orderBy('radius')
//                 ->get();

//             $view->with([
//                 'categories' => $categories,
//                 'states'     => $states,
//                 'radiusList' => $radiusList, 
//             ]);
//         });
//     }
// }
