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
use App\Http\Services\Website\SearchSessionService;
use App\Listeners\StartSearchSessionOnLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrap();

        /* ========================================
            SEARCH ACCESS WINDOW
            Opened on the Login event rather than in the auth controllers:
            password login, OTP registration and Google all raise it, so one
            listener covers every way in.
        ======================================== */
        Event::listen(Login::class, StartSearchSessionOnLogin::class);

        /* ========================================
            SEARCH SESSION STATE (WEBSITE VIEWS)
            The countdown component needs the same numbers the middleware
            decides on, so it is resolved once here rather than in each
            controller that renders a page.
        ======================================== */
        View::composer('website.*', function ($view) {
            $view->with(
                'searchAccess',
                app(SearchSessionService::class)->state(request())
            );
        });


        /* ========================================
            CART COUNT (AVAILABLE ON ALL VIEWS)
        ======================================== */
        View::composer('website.*', function ($view) {
            $cartCount = 0;

            if (Auth::guard('website')->check()) {
                // Counted live, not cached for 120s as it once was.
                //
                // The cache had to be forgotten by every write, and two of them
                // never did: placing an order (CheckoutController) and turning
                // the cart into a campaign (CampaignRepository) both empty the
                // NORMAL cart with a mass update, which fires no model events
                // and cleared nothing — so the badge kept claiming an item the
                // cart page could not show. cart_items is indexed on user_id
                // and holds one row per item a person is considering, so the
                // count the cache was avoiding is cheaper than being wrong.
                $cartCount = CartItem::where('user_id', Auth::guard('website')->id())
                    ->where('is_deleted', 0)
                    ->where('is_active', 1)
                    ->where('cart_type', 'NORMAL')
                    ->where('status', 'ACTIVE')
                    ->count();
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
