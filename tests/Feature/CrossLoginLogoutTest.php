<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Three logins share one browser session: the admin panel, admin mode on the
 * public site, and a customer account. Signing out of any one of them must
 * leave the other two standing.
 */
class CrossLoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    /** Every admin key, as a login leaves them. */
    private const ADMIN = [
        'user_id' => 7, 'email' => 'a@example.test', 'name' => 'Admin',
        'site_admin_id' => 7, 'site_admin_email' => 'a@example.test', 'site_admin_name' => 'Admin',
    ];

    private function customer(): WebsiteUser
    {
        return WebsiteUser::create([
            'name' => 'Cust', 'email' => 'c@example.test',
            'password' => Hash::make('secret123'),
            'is_email_verified' => 1, 'is_active' => 1, 'is_deleted' => 0,
        ]);
    }

    private function guardKey(): string
    {
        return Auth::guard('website')->getName();
    }

    /** Signing in at either door opens both admin surfaces, as it did before. */
    public function test_admin_login_opens_both_surfaces(): void
    {
        User::create([
            'name' => 'Admin', 'email' => 'a@example.test',
            'password' => Hash::make('secret123'), 'is_active' => 1, 'is_deleted' => 0,
        ]);

        $this->post('/website/admin-login', [
            'admin_email' => 'a@example.test', 'admin_password' => 'secret123',
        ])->assertJson(['status' => true]);

        $this->assertTrue(session()->has('user_id'), 'the panel was not opened');
        $this->assertTrue(session()->has('site_admin_id'), 'site admin mode was not opened');
    }

    /** The customer leaves; both admin surfaces survive. */
    public function test_website_logout_keeps_admin_session(): void
    {
        $res = $this->withSession(self::ADMIN + [$this->guardKey() => $this->customer()->id])
            ->get('/website/logout');

        $res->assertRedirect('/');
        $this->assertSame(7, session('user_id'), 'the panel was signed out by the customer logout');
        $this->assertSame(7, session('site_admin_id'), 'admin mode was signed out by the customer logout');
        $this->assertNull(session($this->guardKey()), 'the customer was not signed out');
    }

    /** The panel leaves; admin mode on the site and the customer survive. */
    public function test_admin_panel_logout_leaves_the_site_alone(): void
    {
        $id = $this->customer()->id;

        $res = $this->withSession(self::ADMIN + [$this->guardKey() => $id])->get('/admin/logout');

        $res->assertRedirect(route('login'));
        $this->assertNull(session('user_id'), 'the panel was not signed out');
        $this->assertSame(7, session('site_admin_id'), 'the panel logout also dropped site admin mode');
        $this->assertSame($id, session($this->guardKey()), 'the panel logout signed the customer out');
    }

    /** Admin mode on the site leaves; the panel and the customer survive. */
    public function test_exit_admin_mode_leaves_the_panel_alone(): void
    {
        $id = $this->customer()->id;

        $this->withSession(self::ADMIN + [$this->guardKey() => $id])->get('/website/admin-logout');

        $this->assertNull(session('site_admin_id'), 'admin mode was not signed out');
        $this->assertSame(7, session('user_id'), 'exiting admin mode also signed out of the panel');
        $this->assertSame($id, session($this->guardKey()), 'exiting admin mode signed the customer out');
    }

    /** A disabled customer account is forced out without taking admin with it. */
    public function test_disabled_customer_logout_keeps_admin_session(): void
    {
        $user = $this->customer();
        $user->update(['is_active' => 0]);

        $this->withSession(self::ADMIN + [$this->guardKey() => $user->id])
            ->get(route('dashboard.home'))
            ->assertRedirect('/');

        $this->assertNull(session($this->guardKey()), 'the disabled customer was not signed out');

        $this->assertSame(7, session('user_id'), 'the panel was dropped with the disabled customer');
        $this->assertSame(7, session('site_admin_id'), 'admin mode was dropped with the disabled customer');
    }
}
