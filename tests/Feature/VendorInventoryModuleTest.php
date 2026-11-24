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
        ])->assertRedirect();

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
        $this->asAdmin()->post(route('admin.vendor.approvals.reject', $media->id), ['reason' => 'Close shot is blurred'])->assertRedirect();
        $this->assertSame('rejected', $media->fresh()->approval_status);
        $this->assertTrue(PanelNotification::forVendor($this->vendor->id)->where('type', 'media_rejected')->exists());
        $this->asVendor()->get(route('vendor.media.edit', $media->id))->assertOk()->assertSee('Close shot is blurred')->assertSee('Resubmit for Approval');

        // Approve publishes it.
        $this->asAdmin()->post(route('admin.vendor.approvals.approve', $media->id))->assertRedirect();
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

        $this->asVendor()->get(route('vendor.operations'))->assertOk()->assertSee('Ops User');
        $op = MediaOperation::where('media_id', $media->id)->firstOrFail();
        $this->assertSame('installation', $op->stage);

        // Sharing before all three shots are in is refused.
        $this->asVendor()->post(route('vendor.operations.share', $op->id), ['stage' => 'installation'])->assertSessionHasErrors('share');

        foreach (['newspaper', 'long', 'close'] as $shot) {
            $this->asVendor()->post(route('vendor.operations.photo', $op->id), [
                'stage' => 'installation', 'shot_type' => $shot, 'photo' => UploadedFile::fake()->image($shot . '.jpg', 800, 600),
            ])->assertRedirect();
        }

        $this->asVendor()->post(route('vendor.operations.share', $op->id), ['stage' => 'installation'])->assertSessionHas('success');
        Mail::assertSent(OperationPhotosMail::class, fn ($m) => $m->hasTo('ops-user@example.test'));
        $this->assertSame('monitoring', $op->fresh()->stage);

        // Removal needs the end-date photos shared first.
        $this->asVendor()->post(route('vendor.operations.complete', $op->id), ['confirm' => 1])->assertSessionHasErrors('complete');

        $this->assertTrue(PanelNotification::forAdmin()->where('type', 'installation_photos')->exists());
    }

    public function test_vendor_website_requires_otp_and_shows_only_live_media(): void
    {
        $live = $this->makeMedia($this->vendor, ['media_title' => 'Live Showcase Site']);
        $this->makeMedia($this->vendor, ['media_title' => 'Pending Hidden Site', 'approval_status' => 'pending', 'is_active' => 0]);
        $this->makeMedia($this->otherVendor, ['media_title' => 'Other Vendor Site']);

        $site = route('vendor-site.show', $this->vendor->slug);
        $this->get($site)->assertOk()->assertSee('Verify to view inventory')->assertDontSee('Live Showcase Site');

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
            ->assertSee('Powered by');

        $this->get(route('vendor-site.media', [$this->vendor->slug, $live->id]))->assertOk()->assertSee('Live Showcase Site');
        $this->get('/no-such-vendor-slug-xyz')->assertNotFound();
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

        $this->asVendor()->get(route('vendor.notifications'))->assertOk()
            ->assertSee('Recent Entry')->assertDontSee('Ancient Entry');

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

        // Admin's own active switch cannot publish a pending vendor submission.
        $this->asAdmin()->post(route('media.status'), ['id' => base64_encode($media->id)])->assertStatus(422);
    }
}
