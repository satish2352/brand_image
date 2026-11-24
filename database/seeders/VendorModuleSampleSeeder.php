<?php

namespace Database\Seeders;

use App\Models\MediaImage;
use App\Models\MediaManagement;
use App\Models\MediaOperation;
use App\Models\MediaOperationPhoto;
use App\Models\PanelNotification;
use App\Models\Vendor;
use App\Models\VendorActivityLog;
use App\Models\VendorEnquiry;
use App\Support\MediaCode;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * LOCAL TESTING ONLY — realistic sample data for the Vendor Inventory module.
 *
 *   php artisan db:seed --class=VendorModuleSampleSeeder          (re)create
 *   php artisan db:seed --class=VendorModuleSampleCleanupSeeder   remove
 *
 * Adds, for one vendor (default: the "Rohini Test" vendor, or VENDOR_SAMPLE_EMAIL):
 *  - 9 media at real Nashik / Pune locations (correct coordinates) covering every
 *    state: approved, pending approval, rejected; booked, blocked, ask, available
 *  - placeholder photos (generated), bookings by sample customers that fill every
 *    Operations stage, website enquiries, notifications and activity-log entries.
 *
 * Every sample row is recognisable (media_code SAMPLE-xx, order_no ORD-SAMPLE-*,
 * emails sample.*@example.com), and running the seeder again first removes the
 * previous sample rows, so nothing piles up. No real person is ever emailed.
 */
class VendorModuleSampleSeeder extends Seeder
{
    private const IMG_DIR = 'upload/images/media';

    private Vendor $vendor;
    private array $media = [];   // key => MediaManagement
    private array $users = [];   // key => website_users.id

    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command?->warn('Skipped: sample data is only for the local environment.');
            return;
        }

        $vendor = Vendor::where('is_deleted', 0)
            ->where(fn ($q) => $q->where('email', env('VENDOR_SAMPLE_EMAIL', '__none__'))->orWhere('vendor_name', 'Rohini Test'))
            ->first();

        if (!$vendor) {
            $this->command?->warn('No vendor found (set VENDOR_SAMPLE_EMAIL or create the "Rohini Test" vendor).');
            return;
        }
        $this->vendor = $vendor;

        self::cleanup();

        DB::transaction(function () {
            $this->createMedia();
            $this->createCustomers();
            $this->createBookingsAndOperations();
            $this->createAvailability();
            $this->createEnquiries();
            $this->createNotificationsAndLog();
        });

        $this->command?->info('Sample data added for ' . $this->vendor->vendor_name . ': '
            . count($this->media) . ' media, 4 bookings, 4 enquiries, notifications and activity log.');
    }

    /* ------------------------------------------------------------------ */

    private function createMedia(): void
    {
        $nashik = ['state_id' => 1, 'district_id' => 22, 'city_id' => 187];
        $pune   = ['state_id' => 1, 'district_id' => 26, 'city_id' => 229];

        // key, title, address, area_id, location, lat, lng, w, h, illumination, facing, price, approval, category
        $rows = [
            ['college_road', 'College Road Junction', 'Near BYK College, College Road, Nashik 422005', 2, $nashik, 20.0063, 73.7652, 40, 20, 2, 'Towards Canada Corner', 85000, 'approved', 1],
            ['gangapur', 'Gangapur Road – Jehan Circle', 'Jehan Circle, Gangapur Road, Nashik 422013', 2, $nashik, 20.0119, 73.7562, 30, 15, 3, 'Towards Anandvalli', 64000, 'approved', 1],
            ['mumbai_naka', 'Mumbai Naka Flyover', 'Mumbai Naka, Old Agra Road, Nashik 422002', 2, $nashik, 19.9893, 73.7834, 60, 20, 2, 'Towards CBS', 120000, 'approved', 1],
            ['dwarka', 'Dwarka Circle Gantry', 'Dwarka Circle, Mumbai–Agra Highway, Nashik 422011', 2, $nashik, 19.9938, 73.8019, 50, 10, 2, 'Towards Nashik Road', 95000, 'approved', 1],
            ['govind_nagar', 'Govind Nagar Signal', 'Govind Nagar Signal, Mumbai–Agra Road, Nashik 422009', 1, $nashik, 19.9887, 73.7591, 30, 20, 1, 'Towards Ambad', 48000, 'approved', 1],
            ['cidco', 'CIDCO Trimurti Chowk Wall', 'Trimurti Chowk, CIDCO, Nashik 422008', 1, $nashik, 19.9741, 73.7569, 25, 12, 1, 'Main road facing', 22000, 'approved', 2],
            ['nashik_road', 'Nashik Road Station Approach', 'Station Road, Nashik Road, Nashik 422101', 2, $nashik, 19.9478, 73.8423, 40, 20, 2, 'Towards Railway Station', 72000, 'pending', 1],
            ['fc_road', 'FC Road – Goodluck Chowk', 'Goodluck Chowk, FC Road, Shivajinagar, Pune 411004', 3, $pune, 18.5236, 73.8415, 40, 20, 2, 'Towards Deccan', 110000, 'rejected', 1],
            ['baner', 'Baner Road Unipole', 'Near Baner Hill, Baner Road, Pune 411045', 3, $pune, 18.5590, 73.7868, 30, 30, 3, 'Towards Aundh', 98000, 'approved', 1],
        ];

        foreach ($rows as $i => [$key, $title, $address, $areaId, $loc, $lat, $lng, $w, $h, $illum, $facing, $price, $approval, $cat]) {
            $created = now()->subDays(40 - $i * 3)->setTime(10 + $i % 6, 15);
            $media = MediaManagement::forceCreate($loc + [
                'area_id'         => $areaId,
                'category_id'     => $cat,
                'areatype_id'     => 2,
                'media_code'      => sprintf('SAMPLE-%02d', $i + 1),
                'hoarding_code'   => $cat === 1 ? MediaCode::next('HD') : null,
                'media_title'     => $title,
                'address'         => $address,
                'width'           => $w,
                'height'          => $h,
                'area_auto'       => $w * $h,
                'illumination_id' => $illum,
                'facing'          => $facing,
                'latitude'        => $lat,
                'longitude'       => $lng,
                'price'           => $price,
                'vendor_id'       => $this->vendor->id,
                'is_active'       => $approval === 'approved' ? 1 : 0,
                'is_available'    => 1,
                'is_deleted'      => 0,
                'approval_status' => $approval,
                'rejection_reason' => $approval === 'rejected'
                    ? 'Close shot photo is blurred and the latitude / longitude point to the wrong side of the road. Please correct and resubmit.' : null,
                'submitted_by_vendor_id' => in_array($approval, ['pending', 'rejected'], true) ? $this->vendor->id : null,
                'submitted_at'    => in_array($approval, ['pending', 'rejected'], true) ? now()->subDays(2) : null,
                'reviewed_by'     => $approval === 'rejected' ? DB::table('users')->value('id') : null,
                'reviewed_at'     => $approval === 'rejected' ? now()->subDay() : null,
                'created_at'      => $created,
                'updated_at'      => $created,
            ]);

            // Two generated placeholder photos per media.
            foreach ([1, 2] as $n) {
                $file = 'sample_' . $media->media_code . '_' . $n . '.jpg';
                Storage::disk('public')->put(self::IMG_DIR . '/' . $file, $this->placeholder($title, $media->hoarding_code ?: $media->media_code, $n === 1 ? 'Long shot' : 'Close shot', $i + $n));
                MediaImage::create(['media_id' => $media->id, 'images' => $file, 'is_active' => 1, 'is_deleted' => 0]);
            }

            $this->media[$key] = $media;
        }

        // "Ask for Availability" on the CIDCO wall.
        $this->media['cidco']->forceFill(['is_available' => 0])->save();
    }

    private function createCustomers(): void
    {
        $people = [
            'priya'  => ['Priya Shah', '9822011001', 'Shah Foods Pvt Ltd', 'Pune'],
            'rahul'  => ['Rahul Deshmukh', '9890022002', 'Deshmukh Motors', 'Nashik'],
            'anjali' => ['Anjali Kulkarni', '9765033003', 'Kulkarni Jewellers', 'Nashik'],
            'imran'  => ['Imran Shaikh', '9970044004', 'Shaikh Real Estate', 'Nashik'],
        ];

        foreach ($people as $key => [$name, $mobile, $org, $city]) {
            $this->users[$key] = DB::table('website_users')->insertGetId([
                'name'              => $name,
                'email'             => 'sample.' . $key . '@example.com',
                'mobile_number'     => $mobile,
                'organisation'      => $org,
                'city'              => $city,
                'password'          => Hash::make('Sample@123'),
                'is_email_verified' => 1,
                'access_exempt'     => 0,
                'is_active'         => 1,
                'is_deleted'        => 0,
                'created_at'        => now()->subDays(70),
                'updated_at'        => now()->subDays(70),
            ]);
        }
    }

    private function createBookingsAndOperations(): void
    {
        $today = now()->startOfDay();

        // media, customer, from (days from today), to, payment, stage
        $bookings = [
            ['college_road', 'priya', -2, 28, 'ADMIN_BOOKED', 'installation'],
            ['gangapur', 'rahul', -12, 18, 'PAID', 'monitoring'],
            ['mumbai_naka', 'anjali', -31, -1, 'ADMIN_BOOKED', 'end_date'],
            ['dwarka', 'imran', -65, -35, 'PAID', 'completed'],
        ];

        foreach ($bookings as $n => [$mediaKey, $userKey, $fromDays, $toDays, $payment, $stage]) {
            $media = $this->media[$mediaKey];
            $from = $today->copy()->addDays($fromDays);
            $to   = $today->copy()->addDays($toDays);
            $days = $from->diffInDays($to) + 1;
            $perDay = round($media->price / 30, 2);
            $total = round($perDay * $days, 2);

            $orderId = DB::table('orders')->insertGetId([
                'user_id'        => $this->users[$userKey],
                'order_no'       => 'ORD-SAMPLE-' . (1001 + $n),
                'total_amount'   => $total,
                'gst_amount'     => round($total * 0.18, 2),
                'grand_total'    => round($total * 1.18, 2),
                'payment_status' => $payment,
                'payment_id'     => $payment === 'PAID' ? 'pay_SAMPLE' . (1001 + $n) : null,
                'is_active'      => 1,
                'is_deleted'     => 0,
                'created_at'     => $from->copy()->subDays(5),
                'updated_at'     => $from->copy()->subDays(5),
            ]);

            $itemId = DB::table('order_items')->insertGetId([
                'order_id'      => $orderId,
                'media_id'      => $media->id,
                'price'         => $total,
                'per_day_price' => $perDay,
                'total_days'    => $days,
                'total_price'   => $total,
                'qty'           => 1,
                'from_date'     => $from->toDateString(),
                'to_date'       => $to->toDateString(),
                'is_active'     => 1,
                'is_deleted'    => 0,
                'created_at'    => $from->copy()->subDays(5),
                'updated_at'    => $from->copy()->subDays(5),
            ]);

            DB::table('media_booked_date')->insert([
                'media_id'    => $media->id,
                'from_date'   => $from->toDateString(),
                'to_date'     => $to->toDateString(),
                'status_type' => 'booked',
                'source'      => $payment === 'PAID' ? 'order' : 'admin',
                'is_active'   => 1,
                'is_deleted'  => 0,
                'created_at'  => $from->copy()->subDays(5),
                'updated_at'  => $from->copy()->subDays(5),
            ]);

            $op = MediaOperation::create([
                'vendor_id'              => $this->vendor->id,
                'media_id'               => $media->id,
                'order_id'               => $orderId,
                'order_item_id'          => $itemId,
                // end_date is reached automatically from "monitoring" once the end date passes
                'stage'                  => $stage === 'end_date' ? 'monitoring' : $stage,
                'installation_shared_at' => $stage !== 'installation' ? $from->copy()->setTime(18, 30) : null,
                'end_date_shared_at'     => $stage === 'completed' ? $to->copy()->setTime(17, 0) : null,
                'completed_at'           => $stage === 'completed' ? $to->copy()->addDay()->setTime(11, 0) : null,
                'removal_note'           => $stage === 'completed' ? 'Flex removed and site cleaned.' : null,
            ]);

            // Photos for the stages already done.
            if ($stage !== 'installation') {
                $this->operationPhotos($op, 'installation', null, $from->copy()->setTime(18, 30), true);
            }
            if ($stage === 'monitoring') {
                // Requested for today; two of three uploaded, not shared yet.
                $this->operationPhotos($op, 'monitoring', $today->toDateString(), null, false, ['newspaper', 'long']);
            }
            if (in_array($stage, ['end_date', 'completed'], true)) {
                $this->operationPhotos($op, 'monitoring', $from->copy()->addDays(10)->toDateString(), $from->copy()->addDays(10)->setTime(12, 0), true);
            }
            if ($stage === 'completed') {
                $this->operationPhotos($op, 'end_date', null, $to->copy()->setTime(17, 0), true);
            }
        }
    }

    private function createAvailability(): void
    {
        // Vendor hold on Govind Nagar (today → +14 days).
        DB::table('media_booked_date')->insert([
            'media_id'    => $this->media['govind_nagar']->id,
            'from_date'   => now()->subDay()->toDateString(),
            'to_date'     => now()->addDays(14)->toDateString(),
            'status_type' => 'blocked',
            'source'      => 'vendor',
            'vendor_id'   => $this->vendor->id,
            'is_active'   => 1,
            'is_deleted'  => 0,
            'created_at'  => now()->subDay(),
            'updated_at'  => now()->subDay(),
        ]);
    }

    private function createEnquiries(): void
    {
        $rows = [
            ['priya', 'college_road', 'new', 'Hi, we are launching a new outlet in Nashik. Is College Road available for all of January? Please share the best rate for 2 months.', 1],
            ['rahul', null, 'new', 'Looking for 3 to 4 hoardings on the Mumbai–Agra highway near Nashik for a car dealership launch in February. Please call me.', 3],
            ['anjali', 'baner', 'contacted', 'Need the Baner Road unipole for our jewellery exhibition from 10 to 25 March. Do you also provide flex printing?', 26],
            ['imran', 'gangapur', 'closed', 'Interested in Gangapur Road after the current booking ends. What is the monthly rate including printing and mounting?', 50],
        ];

        foreach ($rows as [$userKey, $mediaKey, $status, $message, $hoursAgo]) {
            $u = DB::table('website_users')->find($this->users[$userKey]);
            $when = now()->subHours($hoursAgo);
            $enquiry = VendorEnquiry::forceCreate([
                'vendor_id'  => $this->vendor->id,
                'media_id'   => $mediaKey ? $this->media[$mediaKey]->id : null,
                'name'       => $u->name,
                'email'      => $u->email,
                'mobile'     => $u->mobile_number,
                'company'    => $u->organisation,
                'city'       => $u->city,
                'message'    => $message,
                'status'     => $status,
                'read_at'    => $status === 'new' ? null : $when->copy()->addHour(),
                'ip'         => '127.0.0.1',
                'created_at' => $when,
                'updated_at' => $when,
            ]);

            $this->notify('vendor', 'enquiry_received', 'New enquiry from ' . $u->name, \Illuminate\Support\Str::limit($message, 110),
                route('vendor.enquiries', ['open' => $enquiry->id]), $enquiry->media_id ?: $this->media['college_road']->id, $when, $status !== 'new');
        }
    }

    private function createNotificationsAndLog(): void
    {
        $m = $this->media;
        $code = fn ($k) => $m[$k]->display_code;

        // Vendor panel notifications
        $this->notify('vendor', 'media_approved', 'Media approved – ' . $code('baner'), 'Baner Road Unipole is approved and now live on Brand Adda.',
            route('vendor.media.show', $m['baner']->id), $m['baner']->id, now()->subDays(6), true);
        $this->notify('vendor', 'media_rejected', 'Media rejected – ' . $code('fc_road'), 'Reason: ' . $m['fc_road']->rejection_reason,
            route('vendor.media.edit', $m['fc_road']->id), $m['fc_road']->id, now()->subDay(), false);
        $this->notify('vendor', 'booking_updated', 'New booking – ' . $code('college_road'),
            'Booked for Priya Shah from ' . now()->subDays(2)->format('d M Y') . ' to ' . now()->addDays(28)->format('d M Y') . '. Installation photos are due from the start date.',
            route('vendor.operations', ['stage' => 'installation']), $m['college_road']->id, now()->subDays(7), false);
        $this->notify('vendor', 'inventory_updated', 'Inventory updated – ' . $code('mumbai_naka'), 'Brand Adda changed: Price.',
            route('vendor.media.show', $m['mumbai_naka']->id), $m['mumbai_naka']->id, now()->subDays(9), true);

        // Super Admin notifications (bell)
        $this->notify('admin', 'media_submitted', 'New media submitted for approval',
            $this->vendor->vendor_name . ' submitted ' . $code('nashik_road') . ' · Nashik Road Station Approach.',
            route('admin.vendor.approvals.show', $m['nashik_road']->id), $m['nashik_road']->id, now()->subDays(2), false);
        $this->notify('admin', 'availability_updated', 'Availability updated by ' . $this->vendor->vendor_name,
            $code('govind_nagar') . ': Available → Blocked (' . now()->subDay()->format('d M Y') . ' – ' . now()->addDays(14)->format('d M Y') . ')',
            route('admin.vendor.activity', ['vendor_id' => $this->vendor->id]), $m['govind_nagar']->id, now()->subDay(), false);
        $this->notify('admin', 'monitoring_photos', 'Monitoring photos uploaded by ' . $this->vendor->vendor_name,
            'ORD-SAMPLE-1002 · ' . $code('gangapur') . ' · Long Shot',
            route('admin.vendor.activity', ['vendor_id' => $this->vendor->id]), $m['gangapur']->id, now()->subHours(3), false);

        // Activity log
        $vendorLog = [
            ['dwarka', 'Media Removal Completed', ['Booking number' => 'ORD-SAMPLE-1004', 'Completion date' => now()->subDays(34)->format('d M Y, h:i A')], now()->subDays(34)],
            ['mumbai_naka', 'Installation Photos Uploaded', ['Booking number' => 'ORD-SAMPLE-1003', 'Shot' => 'Long Shot'], now()->subDays(31)],
            ['gangapur', 'Installation Photos Uploaded', ['Booking number' => 'ORD-SAMPLE-1002', 'Shot' => 'Close Shot'], now()->subDays(12)],
            ['baner', 'Media Rate Updated', ['Previous rate' => '92000', 'New rate' => '98000'], now()->subDays(8)],
            ['cidco', 'Availability Updated', ['Previous status' => 'Available', 'New status' => 'Ask for Availability'], now()->subDays(4)],
            ['nashik_road', 'Media Added', ['Vendor' => $this->vendor->vendor_name], now()->subDays(2)],
            ['nashik_road', 'Media Submitted for Approval', ['Submission status' => 'Pending Approval'], now()->subDays(2)],
            ['govind_nagar', 'Availability Updated', ['Previous status' => 'Available', 'New status' => 'Blocked', 'Dates' => now()->subDay()->format('d M Y') . ' – ' . now()->addDays(14)->format('d M Y')], now()->subDay()],
            ['gangapur', 'Monitoring Photos Uploaded', ['Booking number' => 'ORD-SAMPLE-1002', 'Shot' => 'Long Shot', 'Requested date' => now()->format('d M Y')], now()->subHours(3)],
        ];
        foreach ($vendorLog as [$k, $action, $details, $when]) {
            $this->log('vendor', $k, $action, $details, $when);
        }

        $this->log('admin', 'baner', 'Media Approved', ['Vendor' => $this->vendor->vendor_name, 'Approving Super Admin' => 'Admin'], now()->subDays(6));
        $this->log('admin', 'fc_road', 'Media Rejected', ['Vendor' => $this->vendor->vendor_name, 'Rejection reason' => $m['fc_road']->rejection_reason], now()->subDay());
        $this->log('admin', 'mumbai_naka', 'Vendor Rate Updated', ['Previous rate' => '110000', 'New rate' => '120000'], now()->subDays(9));
    }

    /* ------------------------------------------------------------------ */

    private function notify(string $audience, string $type, string $title, string $message, string $link, ?int $mediaId, Carbon $when, bool $read): void
    {
        PanelNotification::forceCreate([
            'audience'   => $audience,
            'vendor_id'  => $this->vendor->id,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'media_id'   => $mediaId,
            'is_read'    => $read,
            'created_at' => $when,
            'updated_at' => $when,
        ]);
    }

    private function log(string $actor, string $mediaKey, string $action, array $details, Carbon $when): void
    {
        $media = $this->media[$mediaKey];
        VendorActivityLog::forceCreate([
            'vendor_id'  => $this->vendor->id,
            'media_id'   => $media->id,
            'media_code' => $media->display_code,
            'actor_type' => $actor,
            'actor_id'   => $actor === 'vendor' ? $this->vendor->id : DB::table('users')->value('id'),
            'actor_name' => $actor === 'vendor' ? $this->vendor->vendor_name : 'Admin',
            'action'     => $action,
            'details'    => $details,
            'created_at' => $when,
        ]);
    }

    private function operationPhotos(MediaOperation $op, string $stage, ?string $date, ?Carbon $sharedAt, bool $shared, array $shots = ['newspaper', 'long', 'close']): void
    {
        $labels = MediaOperationPhoto::SHOTS;
        $media = $op->media;

        foreach ($shots as $i => $shot) {
            $path = 'upload/operations/' . $op->id . '/sample_' . $stage . '_' . $shot . '.jpg';
            Storage::disk('public')->put($path, $this->placeholder($media->media_title, $media->display_code,
                ucfirst(str_replace('_', '-', $stage)) . ' · ' . $labels[$shot], $op->id + $i + 3));

            MediaOperationPhoto::create([
                'operation_id'          => $op->id,
                'stage'                 => $stage,
                'shot_type'             => $shot,
                'photo_date'            => $date,
                'file'                  => $path,
                'shared_at'             => $shared ? $sharedAt : null,
                'uploaded_by_vendor_id' => $this->vendor->id,
            ]);
        }
    }

    /** A simple generated JPEG: coloured scene + caption, so screens have real images. */
    private function placeholder(string $title, string $code, string $caption, int $seed): string
    {
        $w = 1200;
        $h = 800;
        $img = imagecreatetruecolor($w, $h);
        $palettes = [[15, 23, 42], [30, 58, 138], [15, 118, 110], [124, 45, 18], [55, 65, 81], [107, 33, 168]];
        [$r, $g, $b] = $palettes[$seed % count($palettes)];

        // Sky gradient
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $col = imagecolorallocate($img, (int) (135 + 80 * $t), (int) (180 + 50 * $t), (int) (230 - 20 * $t));
            imageline($img, 0, $y, $w, $y, $col);
        }
        // Road
        imagefilledrectangle($img, 0, 620, $w, $h, imagecolorallocate($img, 70, 75, 85));
        for ($x = 40; $x < $w; $x += 140) {
            imagefilledrectangle($img, $x, 705, $x + 70, 715, imagecolorallocate($img, 240, 240, 240));
        }
        // Hoarding structure + board
        imagefilledrectangle($img, 560, 470, 640, 620, imagecolorallocate($img, 90, 90, 95));
        imagefilledrectangle($img, 200, 150, 1000, 470, imagecolorallocate($img, 230, 230, 230));
        imagefilledrectangle($img, 215, 165, 985, 455, imagecolorallocate($img, $r, $g, $b));
        imagefilledrectangle($img, 215, 395, 985, 455, imagecolorallocate($img, 249, 115, 22));

        $white = imagecolorallocate($img, 255, 255, 255);
        $dark = imagecolorallocate($img, 15, 23, 42);
        $font = 'C:\\Windows\\Fonts\\arialbd.ttf';
        if (is_file($font)) {
            imagettftext($img, 38, 0, 245, 245, $white, $font, mb_strimwidth($title, 0, 32, '…'));
            imagettftext($img, 24, 0, 245, 300, $white, $font, $code . ' · Sample photo');
            imagettftext($img, 24, 0, 245, 438, $dark, $font, $caption);
            imagettftext($img, 18, 0, 30, 780, $white, $font, 'Brand Adda · generated for testing');
        } else {
            imagestring($img, 5, 245, 220, $title, $white);
            imagestring($img, 5, 245, 260, $code . ' - Sample photo', $white);
            imagestring($img, 5, 245, 420, $caption, $dark);
        }

        ob_start();
        imagejpeg($img, null, 82);
        imagedestroy($img);

        return ob_get_clean();
    }

    /** Remove everything a previous run created. */
    public static function cleanup(): void
    {
        $mediaIds = DB::table('media_management')->where('media_code', 'like', 'SAMPLE-%')->pluck('id');
        $orderIds = DB::table('orders')->where('order_no', 'like', 'ORD-SAMPLE-%')->pluck('id');
        $enquiryIds = DB::table('vendor_enquiries')->where('email', 'like', 'sample.%@example.com')->pluck('id');

        $opIds = DB::table('media_operations')->whereIn('media_id', $mediaIds)->orWhereIn('order_id', $orderIds)->pluck('id');
        foreach ($opIds as $opId) {
            Storage::disk('public')->deleteDirectory('upload/operations/' . $opId);
        }
        DB::table('media_operation_photos')->whereIn('operation_id', $opIds)->delete();
        DB::table('media_operations')->whereIn('id', $opIds)->delete();

        foreach (DB::table('media_images')->whereIn('media_id', $mediaIds)->pluck('images') as $file) {
            Storage::disk('public')->delete(self::IMG_DIR . '/' . $file);
        }
        DB::table('media_images')->whereIn('media_id', $mediaIds)->delete();
        DB::table('media_booked_date')->whereIn('media_id', $mediaIds)->delete();
        DB::table('media_landmark')->whereIn('media_id', $mediaIds)->delete();
        DB::table('order_items')->whereIn('order_id', $orderIds)->orWhereIn('media_id', $mediaIds)->delete();
        DB::table('orders')->whereIn('id', $orderIds)->delete();
        DB::table('panel_notifications')->whereIn('media_id', $mediaIds)->delete();
        DB::table('vendor_activity_logs')->whereIn('media_id', $mediaIds)->delete();
        DB::table('vendor_enquiries')->whereIn('id', $enquiryIds)->delete();
        DB::table('media_management')->whereIn('id', $mediaIds)->delete();
        DB::table('website_users')->where('email', 'like', 'sample.%@example.com')->delete();
    }
}
