<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Vendor Inventory Management (PRD v1.0, web portal only).
 *
 * - vendors become accounts that can sign in to /vendor-panel, and get a slug
 *   for their public showcase at /{slug} and a logo for branding.
 * - media_management gets an approval state. Every existing row is
 *   'approved', so nothing already on the website changes. Vendor-submitted
 *   media is saved 'pending' with is_active = 0, which is what keeps it off
 *   the website and out of booking — every public query already filters on
 *   is_active = 1.
 * - media_booked_date learns whether a range is a booking or a vendor block,
 *   and who set it. Existing rows are bookings that came from orders.
 * - new tables: activity log, panel notifications, operations (+ photos) and
 *   the vendor website slider.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'password')) {
                $table->string('password')->nullable()->after('email');
            }
            if (!Schema::hasColumn('vendors', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            if (!Schema::hasColumn('vendors', 'slug')) {
                $table->string('slug', 120)->nullable()->unique()->after('vendor_code');
            }
            if (!Schema::hasColumn('vendors', 'logo')) {
                $table->string('logo')->nullable()->after('address');
            }
            if (!Schema::hasColumn('vendors', 'credentials_sent_at')) {
                $table->timestamp('credentials_sent_at')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('vendors', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('credentials_sent_at');
            }
        });

        // Slugs for the vendors that already exist, unique across the table.
        $used = [];
        foreach (DB::table('vendors')->orderBy('id')->get(['id', 'vendor_name', 'slug']) as $vendor) {
            if ($vendor->slug) {
                $used[] = $vendor->slug;
                continue;
            }
            $base = Str::slug($vendor->vendor_name) ?: 'vendor-' . $vendor->id;
            $slug = $base;
            $n = 2;
            while (in_array($slug, $used, true)) {
                $slug = $base . '-' . $n++;
            }
            $used[] = $slug;
            DB::table('vendors')->where('id', $vendor->id)->update(['slug' => $slug]);
        }

        Schema::table('media_management', function (Blueprint $table) {
            if (!Schema::hasColumn('media_management', 'approval_status')) {
                $table->string('approval_status', 20)->default('approved')->after('is_available')->index();
                $table->text('rejection_reason')->nullable()->after('approval_status');
                $table->unsignedBigInteger('submitted_by_vendor_id')->nullable()->after('rejection_reason');
                $table->timestamp('submitted_at')->nullable()->after('submitted_by_vendor_id');
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('submitted_at');
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        Schema::table('media_booked_date', function (Blueprint $table) {
            if (!Schema::hasColumn('media_booked_date', 'status_type')) {
                // booked | blocked
                $table->string('status_type', 20)->default('booked')->after('to_date');
                // order | admin | vendor
                $table->string('source', 20)->default('order')->after('status_type');
                $table->unsignedBigInteger('vendor_id')->nullable()->after('source');
            }
        });

        if (!Schema::hasTable('vendor_activity_logs')) {
            Schema::create('vendor_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id')->nullable()->index();
                $table->unsignedBigInteger('media_id')->nullable()->index();
                $table->unsignedBigInteger('order_id')->nullable();
                // vendor | admin
                $table->string('actor_type', 20);
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_name')->nullable();
                $table->string('action', 80)->index();
                $table->string('media_code', 50)->nullable();
                $table->json('details')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        if (!Schema::hasTable('panel_notifications')) {
            Schema::create('panel_notifications', function (Blueprint $table) {
                $table->id();
                // admin | vendor
                $table->string('audience', 20);
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->string('type', 60);
                $table->string('title');
                $table->text('message')->nullable();
                $table->string('link')->nullable();
                $table->unsignedBigInteger('media_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
                $table->index(['audience', 'vendor_id', 'is_read']);
            });
        }

        if (!Schema::hasTable('media_operations')) {
            Schema::create('media_operations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id')->index();
                $table->unsignedBigInteger('media_id');
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('order_item_id')->unique();
                // installation | monitoring | end_date | completed
                $table->string('stage', 20)->default('installation')->index();
                $table->timestamp('installation_shared_at')->nullable();
                $table->timestamp('end_date_shared_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('removal_note')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media_operation_photos')) {
            Schema::create('media_operation_photos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('operation_id')->index();
                // installation | monitoring | end_date
                $table->string('stage', 20);
                // newspaper | long | close
                $table->string('shot_type', 20);
                $table->date('photo_date')->nullable();
                $table->string('file');
                $table->timestamp('shared_at')->nullable();
                $table->unsignedBigInteger('uploaded_by_vendor_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('vendor_sliders')) {
            Schema::create('vendor_sliders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id')->index();
                // image | video
                $table->string('media_type', 10);
                $table->string('file');
                $table->string('title')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_sliders');
        Schema::dropIfExists('media_operation_photos');
        Schema::dropIfExists('media_operations');
        Schema::dropIfExists('panel_notifications');
        Schema::dropIfExists('vendor_activity_logs');

        Schema::table('media_booked_date', function (Blueprint $table) {
            foreach (['status_type', 'source', 'vendor_id'] as $column) {
                if (Schema::hasColumn('media_booked_date', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('media_management', function (Blueprint $table) {
            foreach (['approval_status', 'rejection_reason', 'submitted_by_vendor_id', 'submitted_at', 'reviewed_by', 'reviewed_at'] as $column) {
                if (Schema::hasColumn('media_management', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'slug')) {
                $table->dropUnique(['slug']);
            }
            foreach (['password', 'remember_token', 'slug', 'logo', 'credentials_sent_at', 'last_login_at'] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
