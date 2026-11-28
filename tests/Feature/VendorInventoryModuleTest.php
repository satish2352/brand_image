<?php

namespace Tests\Feature;

use App\Mail\OperationPhotosMail;
use App\Mail\VendorCredentialsMail;
use App\Mail\VendorSiteOtpMail;
use App\Models\MediaManagement;
use App\Models\MediaOperation;
use App\Models\PanelNotification;
use App\Models\Vendor;
use App\Models\VendorActivityLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Vendor Inventory Management module, end to end.
 *
 * Runs against the development MySQL database (like the other feature tests
 * here: the schema relies on MySQL), inside one transaction that is rolled
 * back, with mail and the public disk faked — nothing it does persists.
 */
class VendorInventoryModuleTest extends TestCase
{
    private Vendor $vendor;
    private Vendor $otherVendor;
    private object $template;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3307',
            'database.connections.mysql.database' => 'new_brand_image',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
        DB::purge('mysql');

        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Development MySQL database is not reachable.');
        }

        $this->template = DB::table('media_management as m')
            ->join('category as c', 'c.id', '=', 'm.category_id')
            ->where('m.is_deleted', 0)
            ->whereRaw("LOWER(c.category_name) LIKE '%hoarding%'")
            ->whereNotNull('m.illumination_id')
            ->first(['m.*']);

        if (!$this->template) {
            $this->markTestSkipped('Needs at least one hoarding in the development database.');
        }

        DB::beginTransaction();
        Mail::fake();
        Storage::fake('public');

        // The first request of every test never matches a route in this app's
        // test setup (see MediaImportExportSmokeTest), so burn one.
        $this->get('/test');

        $this->vendor = $this->makeVendor('Test Vendor Alpha');
        $this->otherVendor = $this->makeVendor('Test Vendor Beta');
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    private function makeVendor(string $name): Vendor
    {
        $vendor = Vendor::create([
            'state_id'    => $this->template->state_id,
            'district_id' => $this->template->district_id,
            'city_id'     => $this->template->city_id,
            'vendor_name' => $name,
            'vendor_code' => 'TEST_' . Str::upper(Str::random(8)),
            'mobile'      => '9876543210',
            'email'       => Str::lower(Str::random(10)) . '@example.test',
            'address'     => 'Test address',
            'is_active'   => 1,
            'is_deleted'  => 0,
        ]);
        $vendor->forceFill(['password' => Hash::make('Secret#123')])->save();

        return $vendor;
    }

    private function makeMedia(Vendor $vendor, array $overrides = []): MediaManagement
    {
        $row = (array) $this->template;
        unset($row['id'], $row['roadstar_site_id']);

        return MediaManagement::forceCreate(array_merge($row, [
            'vendor_id'       => $vendor->id,
            'media_title'     => 'Test Site ' . Str::random(5),
            'hoarding_code'   => 'HDT' . random_int(100000, 999999),
            'is_active'       => 1,
            'is_available'    => 1,
            'is_deleted'      => 0,
            'approval_status' => 'approved',
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $overrides));
    }

    private function asVendor(?Vendor $vendor = null): static
    {
        return $this->actingAs($vendor ?? $this->vendor, 'vendor');
    }

    private function asAdmin(): static
    {
        return $this->withSession(['user_id' => DB::table('users')->value('id') ?? 1, 'name' => 'Test Admin']);
    }

    public function test_login_and_panel_access(): void
    {
        $this->get(route('vendor.dashboard'))->assertRedirect(route('vendor.login'));

        $this->post(route('vendor.login.submit'), ['email' => $this->vendor->email, 'password' => 'wrong'])
            ->assertSessionHas('error');

        $this->post(route('vendor.login.submit'), ['email' => $this->vendor->email, 'password' => 'Secret#123'])
            ->assertRedirect(route('vendor.dashboard'));

        $this->get(route('vendor.dashboard'))->assertOk()->assertSee('Test Vendor Alpha')->assertSee('Upcoming Availability');
    }

    public function test_vendor_sees_only_its_own_inventory(): void
    {
        $own = $this->makeMedia($this->vendor, ['media_title' => 'Alpha Own Site']);
        $foreign = $this->makeMedia($this->otherVendor, ['media_title' => 'Beta Foreign Site']);

        $this->asVendor()->get(route('vendor.inventory'))->assertOk()
            ->assertSee('Alpha Own Site')->assertDontSee('Beta Foreign Site');

        $this->asVendor()->get(route('vendor.media.show', $own->id))->assertOk();
        $this->asVendor()->get(route('vendor.media.show', $foreign->id))->assertNotFound();
        $this->asVendor()->get(route('vendor.media.edit', $foreign->id))->assertNotFound();
        $this->asVendor()->post(route('vendor.media.toggle', $foreign->id))->assertNotFound();
        $this->asVendor()->post(route('vendor.availability.update', $foreign->id), ['status' => 'ask'])->assertNotFound();

        $this->asVendor()->get(route('vendor.inventory.export'))->assertOk();
    }

    public function test_media_added_by_vendor_waits_for_approval(): void
    {
        $t = $this->template;

        $this->asVendor()->get(route('vendor.media.create'))->assertOk()->assertSee('Submit for Approval');

        $this->asVendor()->post(route('vendor.media.store'), [
            'category_id'     => $t->category_id,
            'vendor_id'       => $this->otherVendor->id, // must be ignored
            'media_title'     => 'Brand New Vendor Hoarding',
            'state_id'        => $t->state_id,
            'district_id'     => $t->district_id,
            'city_id'         => $t->city_id,
            'area_id'         => $t->area_id,
            'width'           => 40,
            'height'          => 20,
            'latitude'        => 18.5,
            'longitude'       => 73.8,
            'price'           => 50000,
            'facing'          => 'North',
            'illumination_id' => $t->illumination_id,
            'address'         => 'Somewhere on FC Road',
        ])->assertRedirect(route('vendor.inventory'))->assertSessionHas('success'); // back to the media list

        $media = MediaManagement::where('media_title', 'Brand New Vendor Hoarding')->firstOrFail();
        $this->assertSame($this->vendor->id, (int) $media->vendor_id);
        $this->assertSame('pending', $media->approval_status);
        $this->assertSame(0, (int) $media->is_active);

        $this->assertTrue(PanelNotification::forAdmin()->where('type', 'media_submitted')->where('media_id', $media->id)->exists());
        $this->assertTrue(VendorActivityLog::where('media_id', $media->id)->where('action', 'Media Submitted for Approval')->exists());

        // Not activatable until approved.
        $this->asVendor()->post(route('vendor.media.toggle', $media->id))->assertSessionHas('error');
        $this->assertSame(0, (int) $media->fresh()->is_active);

        // Reject needs a reason, then the vendor sees it.
        $this->asAdmin()->post(route('admin.vendor.approvals.reject', $media->id), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->asAdmin()->post(route('admin.vendor.approvals.reject', $media->id), ['reason' => 'Close shot is blurred'])
            ->assertRedirect(route('admin.vendor.approvals', ['status' => 'rejected']));
        $this->assertSame('rejected', $media->fresh()->approval_status);
        $this->assertTrue(PanelNotification::forVendor($this->vendor->id)->where('type', 'media_rejected')->exists());
        $this->asVendor()->get(route('vendor.media.edit', $media->id))->assertOk()->assertSee('Close shot is blurred')->assertSee('Resubmit for Approval');

        // A rejected record cannot be approved from the Rejected list; it stays out of Approved.
        $this->asAdmin()->post(route('admin.vendor.approvals.approve', $media->id))->assertSessionHas('error');
        $this->assertSame('rejected', $media->fresh()->approval_status);

        // Once the vendor resubmits (back to Pending), approve publishes it and lands on the Approved tab.
        $media->fresh()->forceFill(['approval_status' => 'pending'])->save();
        $this->asAdmin()->post(route('admin.vendor.approvals.approve', $media->id))
            ->assertRedirect(route('admin.vendor.approvals', ['status' => 'approved']));
        $fresh = $media->fresh();
        $this->assertSame('approved', $fresh->approval_status);
        $this->assertSame(1, (int) $fresh->is_active);
        $this->assertTrue(PanelNotification::forVendor($this->vendor->id)->where('type', 'media_approved')->exists());
    }

    public function test_availability_statuses(): void
    {
        $media = $this->makeMedia($this->vendor);
        $url = route('vendor.availability.update', $media->id);

        $this->asVendor()->post($url, ['status' => 'blocked'])->assertSessionHasErrors(['from_date', 'to_date']);

        $this->asVendor()->post($url, [
            'status' => 'blocked', 'from_date' => now()->toDateString(), 'to_date' => now()->addDays(5)->toDateString(),
        ])->assertSessionHas('success');
        $this->assertSame('blocked', \App\Support\MediaAvailability::forMedia([$media->id])[$media->id]['status']);

        // Overlap is refused.
        $this->asVendor()->post($url, [
            'status' => 'booked', 'from_date' => now()->addDays(2)->toDateString(), 'to_date' => now()->addDays(8)->toDateString(),
        ])->assertSessionHasErrors('from_date');

        $this->asVendor()->post($url, ['status' => 'available'])->assertSessionHas('success');
        $this->assertSame('available', \App\Support\MediaAvailability::forMedia([$media->id])[$media->id]['status']);

        $this->asVendor()->post($url, ['status' => 'ask'])->assertSessionHas('success');
        $this->assertSame(0, (int) $media->fresh()->is_available);
        $this->assertTrue(VendorActivityLog::where('media_id', $media->id)->where('action', 'Availability Updated')->count() >= 3);

        $this->asVendor()->get(route('vendor.availability'))->assertOk();

        // Server-side pagination: 10 per page, and the status filter is applied in SQL.
        for ($i = 1; $i <= 12; $i++) {
            $this->makeMedia($this->vendor, ['media_title' => 'Avail Page ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }
        $list = route('vendor.availability');
        $this->asVendor()->get($list)->assertOk()->assertSee('Page 1 of');
        $this->asVendor()->get($list . '?page=99')->assertRedirect();
        $askOnly = $this->asVendor()->get($list . '?status=ask')->assertOk()->getContent();
        $this->assertStringNotContainsString('Avail Page', $askOnly);
        $this->assertStringContainsString('Avail Page 01', $this->asVendor()->get($list . '?status=available&per_page=100')->getContent());
        $this->asVendor()->get(route('vendor.availability.form', $media->id))->assertOk()->assertSee('Update Availability');
    }

    public function test_operations_workflow(): void
    {
        $media = $this->makeMedia($this->vendor);
        $userId = DB::table('website_users')->insertGetId([
            'name' => 'Ops User', 'email' => 'ops-user@example.test', 'mobile_number' => '9000000000',
            'password' => Hash::make('x'), 'is_email_verified' => 1, 'is_active' => 1, 'is_deleted' => 0, 'access_exempt' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $userId, 'order_no' => 'ORD-TEST-' . random_int(1000, 9999), 'total_amount' => 0, 'gst_amount' => 0,
            'grand_total' => 0, 'payment_status' => 'ADMIN_BOOKED', 'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId, 'media_id' => $media->id, 'price' => 0, 'qty' => 1,
            'from_date' => now()->toDateString(), 'to_date' => now()->addDays(30)->toDateString(),
            'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(),
        ]);

        // Super Admin runs Operations for every vendor.
        // (Filtered to this vendor: the list holds every vendor's bookings.)
        // The stage opens as a table of bookings; clicking one opens its page with the photo slots.
        $this->asAdmin()->get(route('admin.vendor.operations', ['vendor_id' => $this->vendor->id]))->assertOk()
            ->assertSee('Ops User')->assertSee('Sr. No.')->assertSee('Open')->assertDontSee('Upload Photo');
        $op = MediaOperation::where('media_id', $media->id)->firstOrFail();
        $this->asAdmin()->get(route('admin.vendor.operations', ['vendor_id' => $this->vendor->id, 'stage' => 'installation', 'op' => $op->id]))->assertOk()
            ->assertSee('Upload Photo')->assertSee('Back to Installation list');
        // Each table has its own search: by customer mobile, and no match shows a message (not the empty-stage hint).
        $inst = fn (array $q) => route('admin.vendor.operations', ['vendor_id' => $this->vendor->id, 'stage' => 'installation'] + $q);
        $this->asAdmin()->get($inst([]))->assertSee('aria-label="Search bookings"', false);
        $this->asAdmin()->get($inst(['q' => '9000000000']))->assertOk()->assertSee('Ops User');
        $this->asAdmin()->get($inst(['q' => 'no-such-booking-xyz']))->assertOk()->assertDontSee('Ops User')->assertSee('No bookings match');
        $this->assertSame('installation', $op->stage);
        $this->assertSame($this->vendor->id, (int) $op->vendor_id);

        // Sharing before all three shots are in is refused.
        $this->asAdmin()->post(route('admin.vendor.operations.share', $op->id), ['stage' => 'installation'])->assertSessionHasErrors('share');

        foreach (['newspaper', 'long', 'close'] as $shot) {
            $this->asAdmin()->post(route('admin.vendor.operations.photo', $op->id), [
                'stage' => 'installation', 'shot_type' => $shot, 'photo' => UploadedFile::fake()->image($shot . '.jpg', 800, 600),
            ])->assertRedirect();
        }

        $this->asAdmin()->post(route('admin.vendor.operations.share', $op->id), ['stage' => 'installation'])->assertSessionHas('success');
        Mail::assertSent(OperationPhotosMail::class, fn ($m) => $m->hasTo('ops-user@example.test'));
        $this->assertSame('monitoring', $op->fresh()->stage);

        // Monitoring photos for a date the user asks for, shared on WhatsApp: no email, photos marked shared.
        $day = now()->addDays(3)->toDateString();
        $board = route('admin.vendor.operations', ['vendor_id' => $this->vendor->id, 'stage' => 'monitoring', 'op' => $op->id, 'date' => $day]);
        $this->asAdmin()->get($board)->assertOk()->assertSee('Upload monitoring photos for')->assertSee('Add Photos');
        foreach (['newspaper', 'long', 'close'] as $shot) {
            $this->asAdmin()->post(route('admin.vendor.operations.photo', $op->id), [
                'vendor_id' => $this->vendor->id, 'return_stage' => 'monitoring',
                'stage' => 'monitoring', 'photo_date' => $day, 'shot_type' => $shot, 'photo' => UploadedFile::fake()->image($shot . '.jpg', 800, 600),
            ])->assertRedirect($board . '#ops-photos')->assertSessionHas('ops_focus'); // back to the Photos section
        }
        $this->asAdmin()->withSession(['ops_focus' => true, 'success' => 'Close Shot uploaded.'])->get($board)
            ->assertSee('id="ops-photos"', false)->assertSee('Close Shot uploaded.');
        $this->asAdmin()->get($board)->assertOk()->assertSee('Share with User')
            ->assertSee('name="to_mobile" class="vp-input" value="9000000000"', false)->assertSee('value="ops-user@example.test"', false);
        $mailsBefore = count(Mail::sent(OperationPhotosMail::class));
        $this->asAdmin()->post(route('admin.vendor.operations.share', $op->id), ['stage' => 'monitoring', 'photo_date' => $day, 'channel' => 'whatsapp', 'to_mobile' => '9000000000'])
            ->assertSessionHas('success');
        $this->assertCount($mailsBefore, Mail::sent(OperationPhotosMail::class));
        $this->assertSame(3, \App\Models\MediaOperationPhoto::where('operation_id', $op->id)->where('stage', 'monitoring')->whereNotNull('shared_at')->count());
        $this->assertTrue(VendorActivityLog::where('vendor_id', $this->vendor->id)->where('action', 'Photos Shared with User')->latest('id')->first()?->details !== null);

        // Shared photos can be shared again, with anyone: the button stays, the recipient can be changed.
        $this->asAdmin()->get($board)->assertSee('Share Again');
        $this->asAdmin()->post(route('admin.vendor.operations.share', $op->id), ['stage' => 'monitoring', 'photo_date' => $day, 'channel' => 'email', 'to_email' => 'agency@example.test'])
            ->assertSessionHas('success');
        Mail::assertSent(OperationPhotosMail::class, fn ($m) => $m->hasTo('agency@example.test'));
        $this->asAdmin()->post(route('admin.vendor.operations.share', $op->id), ['stage' => 'monitoring', 'photo_date' => $day, 'channel' => 'email', 'to_email' => 'not-an-email'])
            ->assertSessionHasErrors('to_email');

        // End-date photos can be added while the campaign is still running; the page comes back to that tab.
        $this->asAdmin()->get($board)->assertSee('You can upload the end-date photos now');
        $this->asAdmin()->post(route('admin.vendor.operations.photo', $op->id), [
            'vendor_id' => $this->vendor->id, 'return_stage' => 'monitoring',
            'stage' => 'end_date', 'shot_type' => 'newspaper', 'photo' => UploadedFile::fake()->image('end.jpg', 800, 600),
        ])->assertRedirect(route('admin.vendor.operations', ['vendor_id' => $this->vendor->id, 'stage' => 'monitoring', 'op' => $op->id, 'tab' => 'end_date']) . '#ops-photos');
        $this->assertTrue(\App\Models\MediaOperationPhoto::where('operation_id', $op->id)->where('stage', 'end_date')->exists());
        $this->assertSame('monitoring', $op->fresh()->stage); // still monitoring until the end-date photos are shared

        // Mark as Completed is only on End Date Pics: not offered (and refused) during Monitoring.
        $this->asAdmin()->get($board)->assertDontSee('Mark as Completed');
        $this->asAdmin()->post(route('admin.vendor.operations.complete', $op->id), ['confirm' => 1])->assertSessionHasErrors('complete');
        $this->assertSame('monitoring', $op->fresh()->stage);

        // The vendor is told and sees the admin's photos, read-only (no upload / share controls or routes).
        $this->assertTrue(PanelNotification::where('audience', 'vendor')->where('vendor_id', $this->vendor->id)->where('type', 'installation_photos')->exists());
        $this->asVendor()->get(route('vendor.operations', ['stage' => 'monitoring', 'op' => $op->id]))->assertOk()
            ->assertSee('Ops User')->assertSee('Installation photos')->assertSee('uploads the installation')
            ->assertDontSee('Upload Photo')->assertDontSee('Mark as Completed')->assertSee('Share Again');
        $this->asVendor()->post('/vendor-panel/operations/' . $op->id . '/photo', [])->assertNotFound();

        // ...but the vendor can send the uploaded photos to the user again (any email / WhatsApp number);
        // re-sharing old installation photos never moves the booking back.
        $this->asVendor()->post(route('vendor.operations.share', $op->id), ['stage' => 'installation', 'channel' => 'email', 'to_email' => 'client@example.test'])
            ->assertSessionHas('success');
        Mail::assertSent(OperationPhotosMail::class, fn ($m) => $m->hasTo('client@example.test'));
        $this->assertSame('monitoring', $op->fresh()->stage);
        $this->assertTrue(VendorActivityLog::where('vendor_id', $this->vendor->id)->where('action', 'Photos Shared with User')->exists());
        $this->asVendor()->post(route('vendor.operations.share', $op->id), ['stage' => 'installation', 'channel' => 'whatsapp'])->assertSessionHasErrors('to_mobile');
        $other = $this->makeMedia($this->otherVendor);
        $this->asVendor()->post(route('vendor.operations.share', 999999), ['stage' => 'installation', 'channel' => 'email', 'to_email' => 'x@example.test'])->assertNotFound();

        // A vendor (without the admin session) cannot reach the admin routes.
        $this->flushSession();
        $this->asVendor()->get(route('admin.vendor.operations'))->assertRedirect();
        $this->asVendor()->post(route('admin.vendor.operations.share', $op->id), ['stage' => 'installation'])->assertRedirect();

        // Sharing the 3 end-date photos moves it to End Date Pics, where Mark as Completed appears (needs the tick).
        $this->flushSession();
        foreach (['long', 'close'] as $shot) {
            $this->asAdmin()->post(route('admin.vendor.operations.photo', $op->id), [
                'stage' => 'end_date', 'shot_type' => $shot, 'photo' => UploadedFile::fake()->image($shot . '.jpg', 800, 600),
            ])->assertRedirect();
        }
        $this->asAdmin()->post(route('admin.vendor.operations.share', $op->id), ['stage' => 'end_date'])->assertSessionHas('success');
        $this->assertSame('end_date', $op->fresh()->stage);
        $this->asAdmin()->get(route('admin.vendor.operations', ['vendor_id' => $this->vendor->id, 'stage' => 'end_date', 'op' => $op->id]))->assertOk()
            ->assertSee('Mark as Completed');
        $this->asAdmin()->post(route('admin.vendor.operations.complete', $op->id), [])->assertSessionHasErrors('confirm');

        // Marked completed: it then shows under Completed, closed for changes.
        $this->asAdmin()->post(route('admin.vendor.operations.complete', $op->id), ['vendor_id' => $this->vendor->id, 'confirm' => 1, 'removal_note' => 'Flex removed'])
            ->assertRedirect(route('admin.vendor.operations', ['vendor_id' => $this->vendor->id, 'stage' => 'completed', 'op' => $op->id]));
        $this->assertSame('completed', $op->fresh()->stage);
        $this->assertNotNull($op->fresh()->completed_at);
        $this->asAdmin()->get(route('admin.vendor.operations', ['vendor_id' => $this->vendor->id, 'stage' => 'completed', 'op' => $op->id]))->assertOk()
            ->assertSee('Completed · media removed')->assertDontSee('Mark as Completed')->assertDontSee('Upload Photo');
        $this->asAdmin()->post(route('admin.vendor.operations.complete', $op->id), ['confirm' => 1])->assertSessionHasErrors('complete');
    }

    public function test_booked_or_blocked_media_cannot_be_deleted(): void
    {
        $range = fn ($media, string $type, int $from, int $to) => DB::table('media_booked_date')->insert([
            'media_id' => $media->id, 'from_date' => now()->addDays($from)->toDateString(), 'to_date' => now()->addDays($to)->toDateString(),
            'status_type' => $type, 'source' => 'vendor', 'vendor_id' => $this->vendor->id, 'is_active' => 1, 'is_deleted' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $booked  = $this->makeMedia($this->vendor);  $range($booked, 'booked', 0, 10);    // booked now
        $blocked = $this->makeMedia($this->vendor);  $range($blocked, 'blocked', 5, 9);   // blocked later on
        $past    = $this->makeMedia($this->vendor);  $range($past, 'booked', -20, -2);    // booking already over

        // Vendor panel: refused with the reason, the delete button is disabled.
        $this->asVendor()->post(route('vendor.media.destroy', $booked->id))->assertSessionHas('error', fn ($m) => str_contains($m, 'booked till'));
        $this->asVendor()->post(route('vendor.media.destroy', $blocked->id))->assertSessionHas('error', fn ($m) => str_contains($m, 'blocked till'));
        $this->assertSame(0, (int) $booked->fresh()->is_deleted);
        $this->assertSame(0, (int) $blocked->fresh()->is_deleted);
        $this->asVendor()->get(route('vendor.media.show', $booked->id))->assertOk()->assertSee('Cannot delete: this media is booked till');

        // Super Admin: refused too, with a message for the pop-up.
        $this->asAdmin()->post(route('media.delete'), ['id' => base64_encode((string) $blocked->id)])
            ->assertStatus(422)->assertJson(['status' => false])->assertJsonFragment(['message' => $blocked->display_code . ' is blocked till ' . now()->addDays(9)->format('d M Y') . ', so it cannot be deleted. Remove the booking / block first, or delete it after that date.']);
        $this->assertSame(0, (int) $blocked->fresh()->is_deleted);

        // A booking that has ended does not stop the delete.
        $this->flushSession();
        $this->asVendor()->post(route('vendor.media.destroy', $past->id))->assertRedirect(route('vendor.inventory'));
        $this->assertSame(1, (int) $past->fresh()->is_deleted);
    }

    public function test_pending_media_list_is_paginated_on_the_server(): void
    {
        for ($i = 1; $i <= 22; $i++) {
            $this->makeMedia($this->vendor, ['hoarding_code' => sprintf('PGT%03d', $i)]);
        }
        $url = fn (array $q = []) => route('admin.vendor.operations', ['stage' => 'pending', 'vendor_id' => $this->vendor->id] + $q);

        $first = $this->asAdmin()->get($url())->assertOk()->getContent();
        $this->assertStringContainsString('PGT001', $first);
        $this->assertStringNotContainsString('PGT022', $first);           // only 20 rows on page 1
        $this->assertStringContainsString('page=2', $first);              // page links are rendered
        $this->assertStringContainsString('Sr. No.', $first);
        $this->asAdmin()->get($url(['page' => 2]))->assertOk()->assertSee('PGT022')->assertDontSee('>PGT001<', false)
            ->assertSee('<td class="nowrap vp-muted">21</td>', false); // numbering continues on page 2
        $this->asAdmin()->get($url(['q' => 'PGT015']))->assertOk()->assertSee('PGT015')->assertDontSee('PGT016'); // search on the server
        $this->assertStringContainsString('aria-label="Search pending media"', $first);
        $this->asAdmin()->get($url(['q' => 'no-such-code-xyz']))->assertOk()->assertSee('No pending media matches');
    }

    public function test_vendor_profile_and_password_from_the_header_menu(): void
    {
        // Header menu links to both pages.
        $this->asVendor()->get(route('vendor.dashboard'))->assertOk()
            ->assertSee(route('vendor.profile'), false)->assertSee(route('vendor.password'), false)->assertSee('Change Password');

        // Profile: only the address is editable; company name, email and mobile are shown read-only.
        $before = $this->vendor->fresh();
        $this->asVendor()->get(route('vendor.profile'))->assertOk()->assertSee($this->vendor->email)->assertSee($this->vendor->vendor_code)
            ->assertSee('name="address"', false)->assertDontSee('name="company_name"', false)->assertDontSee('name="mobile"', false);
        $this->asVendor()->post(route('vendor.profile.update'), ['address' => 'abc'])->assertSessionHasErrors('address');
        $this->asVendor()->post(route('vendor.profile.update'), [
            'address' => 'Some street, Nashik', 'company_name' => 'Hacked Co', 'mobile' => '9000000001', 'email' => 'hacker@example.test',
        ])->assertRedirect(route('vendor.profile'))->assertSessionHas('success');
        $fresh = $this->vendor->fresh();
        $this->assertSame('Some street, Nashik', $fresh->address);
        $this->assertSame((string) $before->company_name, (string) $fresh->company_name);
        $this->assertSame((string) $before->mobile, (string) $fresh->mobile);
        $this->assertSame($before->email, $fresh->email);

        // Password: wrong current, weak or mismatched new passwords are refused.
        $this->asVendor()->get(route('vendor.password'))->assertOk();
        $this->asVendor()->post(route('vendor.password.update'), ['current_password' => 'wrong', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertSessionHasErrors('current_password');
        $this->asVendor()->post(route('vendor.password.update'), ['current_password' => 'Secret#123', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->asVendor()->post(route('vendor.password.update'), ['current_password' => 'Secret#123', 'password' => 'NewPass123', 'password_confirmation' => 'Other123'])
            ->assertSessionHasErrors('password');

        // After the change the vendor is signed out and sent to the login page.
        $this->asVendor()->post(route('vendor.password.update'), ['current_password' => 'Secret#123', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertRedirect(route('vendor.login'))->assertSessionHas('success');
        $this->assertTrue(Hash::check('NewPass123', $this->vendor->fresh()->password));
        $this->assertGuest('vendor');
        $this->get(route('vendor.dashboard'))->assertRedirect(route('vendor.login'));

        // The new password signs in; the old one no longer does.
        $this->post(route('vendor.login.submit'), ['email' => $this->vendor->email, 'password' => 'Secret#123'])->assertSessionHas('error');
        $this->post(route('vendor.login.submit'), ['email' => $this->vendor->email, 'password' => 'NewPass123'])->assertRedirect(route('vendor.dashboard'));
    }

    public function test_vendor_website_shortlist_is_for_brand_adda_admins_only(): void
    {
        $this->makeMedia($this->vendor, ['media_title' => 'Shortlist Site']);
        $site = route('vendor-site.show', $this->vendor->slug);

        // The vendor (or a verified visitor) sees no Select ticks or shortlist bar.
        $this->asVendor()->get($site)->assertOk()->assertSee('Shortlist Site')
            ->assertDontSee('class="share-pick-input"', false)->assertDontSee('id="shareBar"', false);

        // A Brand Adda admin signed in on the site gets the same shortlist as /search.
        $this->flushSession();
        auth('vendor')->logout();
        $adminId = DB::table('users')->value('id') ?? 1;
        $this->withSession(['site_admin_id' => $adminId, 'user_id' => $adminId])->get($site)->assertOk()
            ->assertSee('class="share-pick-input"', false)->assertSee('Select all loaded')
            ->assertSee('id="shareBar"', false)->assertSee(route('shared.link.store'), false);
    }

    public function test_operations_include_vendor_booked_dates_without_an_order(): void
    {
        $media = $this->makeMedia($this->vendor, ['media_title' => 'Vendor Booked Site']);
        // Booked by the vendor through Update Availability: a booked-date row, no order, no user.
        $bookedId = DB::table('media_booked_date')->insertGetId([
            'media_id' => $media->id, 'from_date' => now()->toDateString(), 'to_date' => now()->addDays(20)->toDateString(),
            'status_type' => 'booked', 'source' => 'vendor', 'vendor_id' => $this->vendor->id,
            'is_active' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->asAdmin()->get(route('admin.vendor.operations', ['stage' => 'installation', 'vendor_id' => $this->vendor->id]))->assertOk()
            ->assertSee('Vendor Booked Site')->assertSee('Booking #' . $bookedId);
        $op = MediaOperation::where('booked_date_id', $bookedId)->firstOrFail();
        $this->assertSame('installation', $op->stage);
        $this->assertNull($op->order_id);

        foreach (['newspaper', 'long', 'close'] as $shot) {
            $this->asAdmin()->post(route('admin.vendor.operations.photo', $op->id), [
                'stage' => 'installation', 'shot_type' => $shot, 'photo' => UploadedFile::fake()->image($shot . '.jpg', 800, 600),
            ])->assertRedirect();
        }

        // No user to email: the photos are recorded and the booking moves on, nothing is sent.
        $this->asAdmin()->post(route('admin.vendor.operations.share', $op->id), ['stage' => 'installation'])->assertSessionHas('success');
        Mail::assertNotSent(OperationPhotosMail::class);
        $this->assertSame('monitoring', $op->fresh()->stage);

        // Running the sync again does not add a second operation for the same booking.
        $this->asAdmin()->get(route('admin.vendor.operations'))->assertOk();
        $this->assertSame(1, MediaOperation::where('media_id', $media->id)->count());

        // The vendor sees it too (read-only).
        $this->flushSession();
        $this->asVendor()->get(route('vendor.operations', ['stage' => 'monitoring']))->assertOk()->assertSee('Booking #' . $bookedId);
    }

    public function test_vendor_website_requires_otp_and_shows_only_live_media(): void
    {
        $live = $this->makeMedia($this->vendor, ['media_title' => 'Live Showcase Site']);
        $this->makeMedia($this->vendor, ['media_title' => 'Pending Hidden Site', 'approval_status' => 'pending', 'is_active' => 0]);
        $this->makeMedia($this->otherVendor, ['media_title' => 'Other Vendor Site']);

        $site = route('vendor-site.show', $this->vendor->slug);
        $this->get($site)->assertOk()->assertSee('Verify to view inventory')->assertDontSee('Live Showcase Site');
        $this->get(route('vendor-site.contact', $this->vendor->slug))->assertRedirect($site);
        $this->get(route('vendor-site.map', $this->vendor->slug))->assertRedirect($site);

        $this->post(route('vendor-site.otp.send', $this->vendor->slug))->assertSessionHas('success');

        $otp = null;
        Mail::assertSent(VendorSiteOtpMail::class, function ($mail) use (&$otp) {
            $otp = $mail->otp;
            return $mail->hasTo($this->vendor->email);
        });

        $this->post(route('vendor-site.otp.verify', $this->vendor->slug), ['otp' => ['0', '0', '0', '0', '0', '0']])->assertSessionHas('error');
        $this->post(route('vendor-site.otp.verify', $this->vendor->slug), ['otp' => str_split($otp)])->assertRedirect($site);

        $this->get($site)->assertOk()
            ->assertSee('Live Showcase Site')
            ->assertDontSee('Pending Hidden Site')
            ->assertDontSee('Other Vendor Site')
            ->assertSee('Powered by')
            ->assertDontSee('id="contact"', false)
            ->assertSee(route('vendor-site.contact', $this->vendor->slug));

        // Separate Map page, linked from the menu: only this vendor's live sites.
        $mapUrl = route('vendor-site.map', $this->vendor->slug);
        $this->get($site)->assertSee('href="' . $mapUrl . '"', false);
        $this->get($mapUrl)->assertOk()->assertSee('id="vmMap"', false)->assertSee('Live Showcase Site')
            // The /explore filter groups (checkbox lists) and the status dropdown.
            ->assertSee('name="category_id[]"', false)->assertSee('name="city_id[]"', false)->assertSee('Landmarks')
            ->assertSee('All availability')->assertDontSee('class="vm-item"', false)
            ->assertDontSee('Pending Hidden Site')->assertDontSee('Other Vendor Site');

        // The map's search (same as /explore) returns only this vendor's live media.
        $ids = $this->getJson(route('vendor-site.map.search', $this->vendor->slug) . '?q=Site')->assertOk()->json('ids');
        $titles = \Illuminate\Support\Facades\DB::table('media_management')->whereIn('id', $ids)->pluck('media_title')->all();
        $this->assertContains('Live Showcase Site', $titles);
        $this->assertNotContains('Pending Hidden Site', $titles);
        $this->assertNotContains('Other Vendor Site', $titles);

        // Separate Contact page with the vendor's details.
        $this->get(route('vendor-site.contact', $this->vendor->slug))->assertOk()
            ->assertSee('Contact Test Vendor Alpha')
            ->assertSee($this->vendor->email)
            ->assertSee('tel:+919876543210', false)
            ->assertSee('https://wa.me/919876543210', false);

        // The detail page carries the Location Insights panel, which loads for a website visitor
        // without the admin refresh bar. (No provider call: fetch-on-view is off here.)
        config(['services.insights.fetch_on_view' => false]);
        $insightsUrl = route('media.insights.show', base64_encode((string) $live->id));
        $this->get(route('vendor-site.media', [$this->vendor->slug, $live->id]))->assertOk()->assertSee('Live Showcase Site')
            ->assertSee('id="media-insights"', false)->assertSee($insightsUrl, false)
            ->assertSee('Enquire about this site')->assertSee('Available now')->assertSee('id="availability"', false);
        // Dates picked in the calendar start the enquiry message; nonsense dates are ignored.
        $contactUrl = fn (array $q) => route('vendor-site.contact', [$this->vendor->slug, 'media' => $live->id] + $q);
        $this->get($contactUrl(['from' => '2027-01-05', 'to' => '2027-01-20']))->assertOk()
            ->assertSee('I would like to book Live Showcase Site from 05 Jan 2027 to 20 Jan 2027.');
        foreach ([['from' => '2027-01-20', 'to' => '2027-01-05'], ['from' => 'x', 'to' => '2027-99-99'], ['from' => ['a'], 'to' => '2027-01-05']] as $bad) {
            $this->get($contactUrl($bad))->assertOk()->assertDontSee('I would like to book');
        }
        // A blocked site explains when it is free again, skipping a range that follows on directly,
        // and other live sites of the same vendor (only) are suggested.
        $busy = $this->makeMedia($this->vendor, ['media_title' => 'Busy Showcase Site']);
        foreach ([[-2, 5, 'blocked'], [6, 9, 'booked']] as [$a, $b, $type]) {
            \Illuminate\Support\Facades\DB::table('media_booked_date')->insert([
                'media_id' => $busy->id, 'from_date' => now()->addDays($a)->toDateString(), 'to_date' => now()->addDays($b)->toDateString(),
                'status_type' => $type, 'source' => 'vendor', 'vendor_id' => $this->vendor->id, 'is_active' => 1, 'is_deleted' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->get(route('vendor-site.media', [$this->vendor->slug, $busy->id]))->assertOk()
            ->assertSee('Not available till ' . now()->addDays(5)->format('d M Y'))
            ->assertSee('Available from ' . now()->addDays(10)->format('d M Y'))
            ->assertSee('id="booking_range"', false)->assertSee('"from":"' . now()->subDays(2)->toDateString() . '"', false)
            ->assertSee('Live Showcase Site')->assertDontSee('Other Vendor Site')->assertDontSee('Pending Hidden Site');
        $panel = $this->getJson($insightsUrl)->assertOk()->json('html');
        $this->assertStringContainsString('Location Insights', $panel);
        $this->assertStringNotContainsString('insightsRefreshBtn', $panel);
        $this->assertStringNotContainsString('Geoapify', $panel);

        // Reset appears only once a filter is applied.
        $this->get($site)->assertDontSee('Clear all filters');
        $this->get($site . '?q=Live')->assertOk()->assertSee('Clear all filters')->assertSee('Live Showcase Site');

        // Media details with photos (main photo + thumbnails) must render too.
        \App\Models\MediaImage::create(['media_id' => $live->id, 'images' => 'first.jpg', 'is_active' => 1, 'is_deleted' => 0]);
        \App\Models\MediaImage::create(['media_id' => $live->id, 'images' => 'second.jpg', 'is_active' => 1, 'is_deleted' => 0]);
        $this->get(route('vendor-site.media', [$this->vendor->slug, $live->id]))->assertOk()
            ->assertSee('first.jpg', false)->assertSee('second.jpg', false);
        $this->get($site)->assertOk()->assertSee('first.jpg', false);
        $this->asVendor()->get(route('vendor.media.show', $live->id))->assertOk()->assertSee('second.jpg', false);
        $this->get('/no-such-vendor-slug-xyz')->assertNotFound();

        // Cards lazy-load 8 at a time: the page renders the first 8, an XHR for page 2 returns
        // only the remaining cards (no layout), and a page past the end returns nothing.
        for ($i = 1; $i <= 8; $i++) {
            $this->makeMedia($this->vendor, ['media_title' => 'Lazy Card ' . $i]);
        }
        $cards = fn ($html) => substr_count($html, 'class="vs-item"');
        $this->assertSame(8, $cards($this->get($site)->assertOk()->getContent()));
        $xhr = ['X-Requested-With' => 'XMLHttpRequest'];
        $page2 = $this->get($site . '?page=2', $xhr)->assertOk()->getContent();
        $this->assertSame(2, $cards($page2));
        $this->assertStringNotContainsString('<html', $page2);
        $this->assertSame('', trim($this->get($site . '?page=3', $xhr)->assertOk()->getContent()));
    }

    public function test_vendor_can_edit_a_slider_slide(): void
    {
        $this->asVendor()->post(route('vendor.website.slides.store'), [
            'media_type' => 'image', 'title' => 'Old caption', 'file' => UploadedFile::fake()->image('a.jpg', 1920, 700),
        ])->assertSessionHas('success');
        $slide = \App\Models\VendorSlider::where('vendor_id', $this->vendor->id)->firstOrFail();
        $oldFile = $slide->file;

        // Caption only: file kept.
        $this->asVendor()->post(route('vendor.website.slides.edit', $slide->id), ['media_type' => 'image', 'title' => 'New caption'])
            ->assertSessionHas('success');
        $this->assertSame('New caption', $slide->fresh()->title);
        $this->assertSame($oldFile, $slide->fresh()->file);

        // Switching to video without a file is refused.
        $this->asVendor()->post(route('vendor.website.slides.edit', $slide->id), ['media_type' => 'video', 'title' => 'New caption'])
            ->assertSessionHasErrors('file');

        // Replacing the image swaps the file and removes the old one.
        $this->asVendor()->post(route('vendor.website.slides.edit', $slide->id), [
            'media_type' => 'image', 'title' => 'New caption', 'file' => UploadedFile::fake()->image('b.png', 1920, 700),
        ])->assertSessionHas('success');
        $this->assertNotSame($oldFile, $slide->fresh()->file);
        Storage::disk('public')->assertMissing($oldFile);
        Storage::disk('public')->assertExists($slide->fresh()->file);

        // Another vendor cannot edit it.
        $this->asVendor($this->otherVendor)->post(route('vendor.website.slides.edit', $slide->id), ['media_type' => 'image'])->assertNotFound();

        $this->asVendor()->get(route('vendor.website'))->assertOk()->assertSee('Replace file (optional)');
    }

    public function test_contact_form_enquiry_reaches_the_vendor(): void
    {
        $media = $this->makeMedia($this->vendor, ['media_title' => 'Enquiry Site']);
        $contact = route('vendor-site.contact', $this->vendor->slug);

        // Needs the website OTP like the rest of the site.
        $this->post($contact, ['name' => 'X'])->assertRedirect(route('vendor-site.show', $this->vendor->slug));

        $this->asVendor()->get($contact . '?media=' . $media->id)->assertOk()->assertSee('Send an enquiry')->assertSee('Enquiry Site');

        // Validation.
        $this->asVendor()->post($contact, ['name' => '', 'email' => 'bad', 'mobile' => '123', 'message' => 'hi'])
            ->assertSessionHasErrors(['name', 'email', 'mobile', 'message']);
        $valid = ['name' => 'Asha Rao', 'email' => 'asha@example.test', 'mobile' => '9876543210', 'message' => 'Please share your rates.'];
        foreach ([
            'mobile'  => ['1234567890', '98765', '9999999999', '98765432101'],
            'name'    => ['Ra', 'R4hul Sharma', '12345'],
            'email'   => ['asha@', 'asha@example', 'as ha@example.com'],
            'city'    => ['Pune1', 'P'],
            'message' => ['short', '1234567890123'],
        ] as $field => $badValues) {
            foreach ($badValues as $bad) {
                $this->asVendor()->post($contact, array_merge($valid, [$field => $bad]))->assertSessionHasErrors($field);
            }
        }

        // Bots filling the hidden field are dropped silently.
        $this->asVendor()->post($contact, ['website' => 'spam', 'name' => 'Bot', 'email' => 'b@example.test', 'mobile' => '9876543210', 'message' => 'Buy cheap stuff now'])
            ->assertSessionHas('enquiry_sent');
        $this->assertSame(0, \App\Models\VendorEnquiry::where('vendor_id', $this->vendor->id)->count());

        $this->asVendor()->post($contact, [
            'name' => '  Priya   Shah ', 'email' => 'Priya@Example.test', 'mobile' => '+91 98765-43210', 'company' => 'Shah Foods',
            'city' => 'Pune', 'media_id' => $media->id, 'message' => 'Need this hoarding for January, please share rates.',
        ])->assertRedirect($contact)->assertSessionHas('enquiry_sent');

        // The thank-you box links to a fresh page (?new=1) so "Send another enquiry" opens an empty form.
        $thanks = $this->get($contact)->assertOk()->assertSee('Thank you! Your enquiry has been sent.')->getContent();
        $this->assertStringContainsString('new=1#enquiry', $thanks);
        $this->get($contact . '?new=1')->assertOk()->assertSee('id="enquiryForm"', false)->assertDontSee('Thank you! Your enquiry has been sent.')
            ->assertDontSee('outdoor media sites'); // no count line under the logo

        $enquiry = \App\Models\VendorEnquiry::where('vendor_id', $this->vendor->id)->firstOrFail();
        $this->assertSame('new', $enquiry->status);
        // Stored tidied: 10-digit mobile, single spaces, lower-case email.
        $this->assertSame('9876543210', $enquiry->mobile);
        $this->assertSame('Priya Shah', $enquiry->name);
        $this->assertSame('priya@example.test', $enquiry->email);
        $this->assertSame($media->id, (int) $enquiry->media_id);
        $this->assertTrue(PanelNotification::forVendor($this->vendor->id)->where('type', 'enquiry_received')->exists());

        // Brand Adda is told too and sees it in Super Admin → Vendor Enquiries (with the vendor filter and popup).
        $this->assertTrue(PanelNotification::forAdmin()->where('type', 'enquiry_received')->where('vendor_id', $this->vendor->id)->exists());
        $this->asAdmin()->get(route('admin.vendor.enquiries', ['vendor_id' => $this->vendor->id]))->assertOk()
            ->assertSee($enquiry->name)->assertSee($this->vendor->vendor_name);
        $this->asAdmin()->get(route('admin.vendor.enquiries', ['open' => $enquiry->id]))->assertOk()
            ->assertSee('id="eqModal"', false)->assertSee($enquiry->email);

        // Another vendor's media id is not attached.
        $other = $this->makeMedia($this->otherVendor);
        $this->asVendor()->post($contact, ['name' => 'Ravi', 'email' => 'r@example.test', 'mobile' => '9123456789', 'media_id' => $other->id, 'message' => 'General question about sites'])
            ->assertSessionHas('enquiry_sent');
        $this->assertNull(\App\Models\VendorEnquiry::where('name', 'Ravi')->value('media_id'));

        // Visible in the vendor panel, marked read when opened, status can change.
        $this->asVendor()->get(route('vendor.enquiries', ['open' => $enquiry->id]))->assertOk()
            ->assertSee('Priya Shah')->assertSee('Need this hoarding for January')->assertSee('Shah Foods');
        $this->assertNotNull($enquiry->fresh()->read_at);
        $this->asVendor()->post(route('vendor.enquiries.status', $enquiry->id), ['status' => 'contacted'])->assertSessionHas('success');
        $this->assertSame('contacted', $enquiry->fresh()->status);

        // Not visible to, or editable by, another vendor.
        $this->asVendor($this->otherVendor)->get(route('vendor.enquiries'))->assertOk()->assertDontSee('Priya Shah');
        $this->asVendor($this->otherVendor)->post(route('vendor.enquiries.status', $enquiry->id), ['status' => 'closed'])->assertNotFound();
    }

    public function test_admin_activity_log_is_paginated_on_the_server(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            VendorActivityLog::create([
                'vendor_id' => $this->vendor->id, 'actor_type' => 'vendor', 'actor_name' => 'x',
                'action' => 'Paging Check ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'created_at' => now()->addHour()->subMinutes($i),
            ]);
        }
        $url = route('admin.vendor.activity', ['vendor_id' => $this->vendor->id]);

        // Only the table body counts: the Action filter lists every action name.
        $rows = function (string $html): string {
            preg_match('#<tbody>(.*?)</tbody>#s', $html, $m);
            return $m[1] ?? '';
        };

        // 10 per page by default: newest 10 on page 1, the rest on page 2.
        $page1 = $this->asAdmin()->get($url)->assertOk()->assertSee('Page 1 of 2');
        $body = $rows($page1->getContent());
        $this->assertSame(10, substr_count($body, 'Paging Check'));
        $this->assertStringContainsString('Paging Check 01', $body);
        $this->assertStringNotContainsString('Paging Check 11', $body);

        $body = $rows($this->asAdmin()->get($url . '&page=2')->assertOk()->getContent());
        $this->assertStringContainsString('Paging Check 13', $body);
        $this->assertStringNotContainsString('Paging Check 01', $body);

        // Per-page choice, and a page past the end goes to the last page.
        $body = $rows($this->asAdmin()->get($url . '&per_page=20')->assertOk()->getContent());
        $this->assertSame(13, substr_count($body, 'Paging Check'));
        $this->asAdmin()->get($url . '&page=9')->assertRedirect();
    }

    public function test_vendor_company_name_shows_in_master_and_vendor_panel(): void
    {
        // The admin form has the field, and a too-long value is rejected.
        $this->asAdmin()->get(route('vendor.edit', base64_encode($this->vendor->id)))->assertOk()->assertSee('name="company_name"', false);
        $this->asAdmin()->post(route('vendor.update', base64_encode($this->vendor->id)), ['company_name' => str_repeat('x', 256)])
            ->assertSessionHasErrors('company_name');

        $this->vendor->update(['company_name' => 'Alpha Outdoor Media Pvt Ltd']);
        $this->asAdmin()->get(route('vendor.list'))->assertOk()->assertSee('Alpha Outdoor Media Pvt Ltd');
        // The vendor panel shows the company name in place of the vendor name.
        $this->asVendor()->get(route('vendor.dashboard'))->assertOk()
            ->assertSee('Alpha Outdoor Media Pvt Ltd')->assertDontSee('>' . $this->vendor->vendor_name . '<', false);
        // So does the vendor website (the signed-in vendor can open it without a code).
        $this->asVendor()->get(route('vendor-site.show', $this->vendor->slug))->assertOk()
            ->assertSee('<title>Alpha Outdoor Media Pvt Ltd · Powered by Brand Adda</title>', false)
            ->assertDontSee($this->vendor->vendor_name);
        $this->asVendor()->get(route('vendor-site.contact', $this->vendor->slug))->assertOk()->assertSee('Contact Alpha Outdoor Media Pvt Ltd');
    }

    public function test_vendor_lists_are_paginated_on_the_server(): void
    {
        // Notifications: 15 per page, oldest on page 2, page past the end goes to the last page.
        for ($i = 1; $i <= 17; $i++) {
            \App\Models\PanelNotification::create([
                'audience' => 'vendor', 'vendor_id' => $this->vendor->id, 'type' => 'info',
                'title' => 'Paging Note ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'message' => 'x', 'is_read' => true,
            ]);
            \Illuminate\Support\Facades\DB::table('panel_notifications')->where('title', 'Paging Note ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT))
                ->update(['created_at' => now()->addHour()->subMinutes($i)]);
        }
        $url = route('vendor.notifications');
        $this->asVendor()->get($url)->assertOk()->assertSee('Paging Note 01')->assertDontSee('Paging Note 17')->assertSee('Page 1 of');
        $this->asVendor()->get($url . '?page=2')->assertOk()->assertSee('Paging Note 17')->assertDontSee('Paging Note 01');
        $this->asVendor()->get($url . '?page=99')->assertRedirect();

        // Activity Log shows a serial number; the Utilisation report and Operations still render.
        $this->asVendor()->get(route('vendor.activity'))->assertOk()->assertSee('Sr. No.');
        $this->asVendor()->get(route('vendor.reports.utilisation'))->assertOk()->assertSee('Sr. No.');
        $this->asVendor()->get(route('vendor.operations'))->assertOk();
    }

    public function test_activity_log_keeps_six_months(): void
    {
        $old = VendorActivityLog::create([
            'vendor_id' => $this->vendor->id, 'actor_type' => 'vendor', 'actor_name' => 'x',
            'action' => 'Ancient Entry', 'created_at' => now()->subMonths(7),
        ]);
        $recent = VendorActivityLog::create([
            'vendor_id' => $this->vendor->id, 'actor_type' => 'vendor', 'actor_name' => 'x',
            'action' => 'Recent Entry', 'created_at' => now()->subMonths(5),
        ]);

        $this->asVendor()->get(route('vendor.activity'))->assertOk()
            ->assertSee('Recent Entry')->assertDontSee('Ancient Entry');
        // Notifications page shows notifications only, not the log.
        $this->asVendor()->get(route('vendor.notifications'))->assertOk()
            ->assertDontSee('Recent Entry')->assertSee(route('vendor.activity'));

        (new VendorActivityLog())->pruneAll();

        $this->assertNull(VendorActivityLog::find($old->id));
        $this->assertNotNull(VendorActivityLog::find($recent->id));
    }

    public function test_admin_screens_and_credentials(): void
    {
        $media = $this->makeMedia($this->vendor, ['approval_status' => 'pending', 'is_active' => 0, 'submitted_by_vendor_id' => $this->vendor->id, 'submitted_at' => now()]);

        $this->asAdmin()->get(route('admin.vendor.approvals'))->assertOk()->assertSee($media->hoarding_code);
        $this->asAdmin()->get(route('admin.vendor.approvals.show', $media->id))->assertOk()->assertSee('Approve Media');
        $this->asAdmin()->get(route('admin.vendor.inventory', ['vendor_id' => $this->vendor->id]))->assertOk();
        $this->asAdmin()->get(route('admin.vendor.activity'))->assertOk();
        $this->asAdmin()->get(route('admin.vendor.notifications'))->assertOk();

        // Assign / remove inventory.
        $loose = $this->makeMedia($this->otherVendor);
        $this->asAdmin()->post(route('admin.vendor.inventory.assign'), ['vendor_id' => $this->vendor->id, 'media_ids' => [$loose->id]])->assertSessionHas('success');
        $this->assertSame($this->vendor->id, (int) $loose->fresh()->vendor_id);
        $this->assertTrue(PanelNotification::forVendor($this->otherVendor->id)->where('type', 'inventory_removed')->exists());

        // Opening a notification: marked read, then the page it is about (on today's address, not the
        // stored one); media no longer in the inventory is explained; another vendor's is not found.
        $mine = $this->makeMedia($this->vendor, ['media_title' => 'Notified Site']);
        $note = fn (array $v) => PanelNotification::create($v + ['audience' => 'vendor', 'vendor_id' => $this->vendor->id, 'type' => 'media_approved', 'title' => 'Open me', 'is_read' => 0]);
        $a = $note(['link' => 'https://old-address.test/app/vendor-panel/media/' . $mine->id, 'media_id' => $mine->id]);
        $this->asVendor()->get(route('vendor.notifications'))->assertSee(route('vendor.notifications.open', $a->id), false);
        $this->asVendor()->get(route('vendor.notifications.open', $a->id))->assertRedirect(route('vendor.media.show', $mine->id));
        $this->assertTrue((bool) $a->fresh()->is_read);
        $b = $note(['type' => 'enquiry_received', 'link' => route('vendor.enquiries', ['open' => 7])]);
        $this->asVendor()->get(route('vendor.notifications.open', $b->id))->assertRedirect(route('vendor.enquiries', ['open' => 7]));
        $mine->forceFill(['vendor_id' => $this->otherVendor->id])->save();
        foreach ([$a, $note(['type' => 'inventory_removed', 'media_id' => $mine->id])] as $n) {
            $this->asVendor()->get(route('vendor.notifications.open', $n->id))->assertRedirect(route('vendor.inventory'))->assertSessionHas('info');
        }
        $c = $note(['vendor_id' => $this->otherVendor->id]);
        $this->asVendor()->get(route('vendor.notifications.open', $c->id))->assertNotFound();
        $this->asAdmin()->post(route('admin.vendor.inventory.remove'), ['vendor_id' => $this->vendor->id, 'media_ids' => [$loose->id]])->assertSessionHas('success');
        $this->assertNull($loose->fresh()->vendor_id);

        // Login details: a new password is emailed and works.
        $this->asAdmin()->post(route('admin.vendor.credentials', $this->vendor->id))->assertSessionHas('success');
        $sent = null;
        Mail::assertSent(VendorCredentialsMail::class, function ($m) use (&$sent) {
            $sent = $m->plainPassword;
            return $m->hasTo($this->vendor->email);
        });
        $this->assertTrue(Hash::check($sent, $this->vendor->fresh()->password));

        // The branded emails render (HTML + plain text) with the right details.
        $html = (new VendorCredentialsMail($this->vendor->fresh(), $sent))->render();
        $this->assertStringContainsString($sent, $html);
        $this->assertStringContainsString('Login to Vendor Panel', $html);
        $this->assertStringContainsString('482915', (new VendorSiteOtpMail($this->vendor, '482915', 10))->render());

        // Admin's own active switch cannot publish a pending vendor submission.
        $this->asAdmin()->post(route('media.status'), ['id' => base64_encode($media->id)])->assertStatus(422);
    }
}
