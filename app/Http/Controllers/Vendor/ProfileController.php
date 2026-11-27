<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Support\VendorAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * The signed-in vendor's own profile and password (header menu of the vendor panel).
 *
 * The vendor may change only its address. Vendor name, company name, code, login
 * email, mobile, location and website address are managed by Brand Adda
 * (Super Admin → Master → Vendor) and are shown read-only.
 */
class ProfileController extends Controller
{
    private function vendor()
    {
        return auth('vendor')->user();
    }

    public function show()
    {
        $vendor = $this->vendor();

        $location = DB::table('vendors as v')
            ->leftJoin('states as s', 's.id', '=', 'v.state_id')
            ->leftJoin('districts as d', 'd.id', '=', 'v.district_id')
            ->leftJoin('cities as c', 'c.id', '=', 'v.city_id')
            ->where('v.id', $vendor->id)
            ->first(['s.state_name', 'd.district_name', 'c.city_name']);

        return view('vendor.profile.show', [
            'vendor'   => $vendor,
            'location' => collect([$location->city_name ?? null, $location->district_name ?? null, $location->state_name ?? null])->filter()->unique()->implode(', '),
        ]);
    }

    public function update(Request $request)
    {
        $vendor = $this->vendor();

        // Only the address is taken; any other posted field (company name, mobile, email…) is ignored.
        $data = $request->validate([
            'address' => 'required|string|min:5|max:1000',
        ]);

        $before = $vendor->only(['address']);
        $vendor->forceFill(['address' => $data['address']])->save();

        $changes = VendorAudit::diff($before, $vendor->only(['address']), ['address' => 'Address']);
        if ($changes) {
            VendorAudit::vendorDid($vendor, 'Profile Updated', $changes);
        }

        return redirect()->route('vendor.profile')->with('success', $changes ? 'Profile updated.' : 'No changes to save.');
    }

    public function editPassword()
    {
        return view('vendor.profile.password');
    }

    public function updatePassword(Request $request)
    {
        $vendor = $this->vendor();

        $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'The new password and its confirmation do not match.',
            'password.different' => 'The new password must be different from the current one.',
        ]);

        if (!Hash::check($request->current_password, $vendor->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $vendor->forceFill(['password' => Hash::make($request->password)])->save();
        $request->session()->regenerate();

        VendorAudit::vendorDid($vendor, 'Password Changed');

        return redirect()->route('vendor.password')->with('success', 'Your password has been changed.');
    }
}
