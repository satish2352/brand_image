<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use App\Models\CartItem;
use App\Support\MasterCache;
use App\Support\RadiusRange;
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

            $categories = Cache::remember(MasterCache::CATEGORIES, MasterCache::TTL, fn() =>
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

            $radiusList = RadiusRange::options();

            $highways = Cache::remember(MasterCache::HIGHWAYS, MasterCache::TTL, fn() =>
                DB::table('highway')
                    ->where('is_active', 1)
                    ->where('is_deleted', 0)
                    ->orderBy('highway_name')
                    ->get()
            );

            $landmarks = Cache::remember(MasterCache::LANDMARKS, MasterCache::TTL, fn() =>
                DB::table('landmark')
                    ->where('is_active', 1)
                    ->where('is_deleted', 0)
                    ->orderBy('landmark_name')
                    ->get()
            );

            // Bounds for the Radius slider - same ceiling the backend clamps to.
            $radiusMin = RadiusRange::MIN;
            $radiusMax = RadiusRange::max();

            $view->with(compact(
                'categories', 'states', 'radiusList', 'highways', 'landmarks',
                'radiusMin', 'radiusMax'
            ));
        });

        /* ========================================
            ADMIN BOOKING SEARCH FORM VIEW
        ======================================== */
        View::composer('superadm.admin-booking.search-form', function ($view) {

            $firstCategoryName = Cache::remember(MasterCache::FIRST_CATEGORY, MasterCache::TTL, fn() =>
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

            $radiusList = RadiusRange::options();

            $view->with(compact('firstCategoryName', 'states', 'radiusList'));
        });
    }
}
