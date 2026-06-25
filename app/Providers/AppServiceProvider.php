<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use App\Models\CartItem;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrap();

        /* ========================================
            CART COUNT (AVAILABLE ON ALL VIEWS)
        ======================================== */
        View::composer('website.*', function ($view) {
            $cartCount = 0;

            if (Auth::guard('website')->check()) {
                $userId    = Auth::guard('website')->id();
                $cacheKey  = "cart_count_user_{$userId}";

                $cartCount = Cache::remember($cacheKey, 120, function () use ($userId) {
                    return CartItem::where('user_id', $userId)
                        ->where('is_deleted', 0)
                        ->where('is_active', 1)
                        ->where('cart_type', 'NORMAL')
                        ->where('status', 'ACTIVE')
                        ->count();
                });
            }

            $view->with('cartCount', $cartCount);
        });

        /* ========================================
            WEBSITE SEARCH FORM VIEW
        ======================================== */
        View::composer('website.search-form', function ($view) {

            $categories = Cache::remember('search_form_categories', 3600, fn() =>
                DB::table('category')
                    ->where('is_active', 1)
                    ->where('is_deleted', 0)
                    ->orderBy('id')
                    ->get()
            );

            $states = Cache::remember('search_form_states', 3600, fn() =>
                DB::table('states')
                    ->where('is_active', 1)
                    ->where('is_deleted', 0)
                    ->orderBy('state_name')
                    ->get()
            );

            $radiusList = Cache::remember('search_form_radius', 86400, fn() =>
                DB::table('radius_master')
                    ->where('is_active', 1)
                    ->where('is_deleted', 0)
                    ->orderBy('radius')
                    ->get()
            );

            $highways = Cache::remember('search_form_highways', 3600, fn() =>
                DB::table('highway')
                    ->where('is_active', 1)
                    ->where('is_deleted', 0)
                    ->orderBy('highway_name')
                    ->get()
            );

            $landmarks = Cache::remember('search_form_landmarks', 3600, fn() =>
                DB::table('landmark')
                    ->where('is_active', 1)
                    ->where('is_deleted', 0)
                    ->orderBy('landmark_name')
                    ->get()
            );

            $view->with(compact('categories', 'states', 'radiusList', 'highways', 'landmarks'));
        });

        /* ========================================
            ADMIN BOOKING SEARCH FORM VIEW
        ======================================== */
        View::composer('superadm.admin-booking.search-form', function ($view) {

            $firstCategoryName = Cache::remember('admin_first_category', 3600, fn() =>
                DB::table('category')
                    ->where('is_active', 1)
                    ->where('is_deleted', 0)
                    ->orderBy('id')
                    ->value('category_name')
            );

            $states = Cache::remember('search_form_states', 3600, fn() =>
                DB::table('states')
                    ->where('is_active', 1)
                    ->where('is_deleted', 0)
                    ->orderBy('state_name')
                    ->get()
            );

            $radiusList = Cache::remember('search_form_radius', 86400, fn() =>
                DB::table('radius_master')
                    ->where('is_active', 1)
                    ->where('is_deleted', 0)
                    ->orderBy('radius')
                    ->get()
            );

            $view->with(compact('firstCategoryName', 'states', 'radiusList'));
        });
    }
}
