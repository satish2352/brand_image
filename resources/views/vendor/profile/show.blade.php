@extends('vendor.layout')
@section('title', 'My Profile')
@section('crumb', 'Vendor Panel / My Profile')
@section('form_errors_inline', '1')
@section('heading', 'My Profile')

@section('actions')
    <a href="{{ route('vendor.password') }}" class="vp-btn vp-btn-g"><i class="fa-solid fa-key"></i>Change Password</a>
@endsection

@section('content')
    <div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
        {{-- Managed by Brand Adda: shown, not editable here. --}}
        <section class="vp-card vp-card-pad" style="flex:1 1 300px">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px">
                <div class="vp-avatar" style="width:56px;height:56px;font-size:20px">{{ $vendor->initials }}</div>
                <div style="min-width:0">
                    <div style="font-size:18px;font-weight:800;word-break:break-word">{{ $vendor->display_name }}</div>
                    <div class="vp-muted" style="font-size:13px">Vendor Admin</div>
                </div>
            </div>
            @php
                $info = [
                    'Vendor Name'   => $vendor->vendor_name,
                    'Company Name'  => $vendor->company_name,
                    'Vendor Code'   => $vendor->vendor_code,
                    'Login Email'   => $vendor->email,
                    'Mobile Number' => $vendor->mobile,
                    'Location'      => $location ?: '—',
                    'Last Login'   => $vendor->last_login_at ? $vendor->last_login_at->format('d M Y, h:i A') : '—',
                ];
            @endphp
            <dl style="margin:0;display:flex;flex-direction:column;gap:12px">
                @foreach ($info as $k => $v)
                    <div><dt class="vp-muted" style="font-size:12px;font-weight:600">{{ $k }}</dt><dd style="margin:3px 0 0;font-size:15px;font-weight:700;word-break:break-word">{{ $v ?: '—' }}</dd></div>
                @endforeach
                @if ($vendor->slug)
                    <div><dt class="vp-muted" style="font-size:12px;font-weight:600">Your Website</dt>
                        <dd style="margin:3px 0 0;font-size:15px;font-weight:700;word-break:break-all"><a href="{{ url($vendor->slug) }}" target="_blank" rel="noopener">{{ url($vendor->slug) }}</a></dd></div>
                @endif
            </dl>
            <p class="vp-muted" style="margin:16px 0 0;font-size:12px">To change your vendor name, company name, login email, mobile number or location, contact Brand Adda.</p>
        </section>

        <section class="vp-card vp-card-pad" style="flex:2 1 420px">
            <h2 class="vp-h2" style="margin-bottom:16px">Edit Profile</h2>
            {{-- Only the address is editable; company name, email and mobile are managed by Brand Adda. --}}
            <form method="POST" action="{{ route('vendor.profile.update') }}" style="display:flex;flex-direction:column;gap:16px" novalidate>
                @csrf
                <div>
                    <label class="vp-label" for="address">Address <span style="color:#B91C1C">*</span></label>
                    <textarea id="address" name="address" class="vp-input" maxlength="1000" required>{{ old('address', $vendor->address) }}</textarea>
                    @error('address')<div style="color:#B91C1C;font-size:13px;margin-top:4px">{{ $message }}</div>@enderror
                </div>
                <div><button type="submit" class="vp-btn vp-btn-o"><i class="fa-solid fa-floppy-disk"></i>Save Changes</button></div>
            </form>
        </section>
    </div>
@endsection

