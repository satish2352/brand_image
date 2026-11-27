@extends('vendor.layout')
@section('title', 'Change Password')
@section('crumb', 'Vendor Panel / Change Password')
@section('form_errors_inline', '1')
@section('heading', 'Change Password')

@section('actions')
    <a href="{{ route('vendor.profile') }}" class="vp-btn vp-btn-g"><i class="fa-regular fa-user"></i>My Profile</a>
@endsection

@section('content')
    <section class="vp-card vp-card-pad" style="max-width:560px">
        <h2 class="vp-h2" style="margin-bottom:4px">Change your password</h2>
        <p class="vp-muted" style="margin:0 0 18px;font-size:13px">Use at least 8 characters with letters and numbers.</p>
        <form method="POST" action="{{ route('vendor.password.update') }}" style="display:flex;flex-direction:column;gap:16px" novalidate>
            @csrf
            @foreach (['current_password' => 'Current Password', 'password' => 'New Password', 'password_confirmation' => 'Confirm New Password'] as $name => $label)
                <div>
                    <label class="vp-label" for="{{ $name }}">{{ $label }} <span style="color:#B91C1C">*</span></label>
                    <div style="position:relative">
                        <input id="{{ $name }}" name="{{ $name }}" type="password" class="vp-input" required
                            autocomplete="{{ $name === 'current_password' ? 'current-password' : 'new-password' }}" style="padding-right:44px">
                        <button type="button" class="js-eye" data-for="{{ $name }}" aria-label="Show {{ strtolower($label) }}"
                            style="position:absolute;right:6px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:0;background:none;color:#64748B;cursor:pointer">
                            <i class="fa-regular fa-eye"></i></button>
                    </div>
                    @error($name)<div style="color:#B91C1C;font-size:13px;margin-top:4px">{{ $message }}</div>@enderror
                </div>
            @endforeach
            <div><button type="submit" class="vp-btn vp-btn-o"><i class="fa-solid fa-key"></i>Update Password</button></div>
        </form>
    </section>
@endsection

@section('scripts')
    <script>
        // Show / hide each password.
        document.querySelectorAll('.js-eye').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById(btn.dataset.for), show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('i').className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
            });
        });
    </script>
@endsection
