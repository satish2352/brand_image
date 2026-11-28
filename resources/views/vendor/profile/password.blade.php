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
                    @if ($name === 'password')
                        {{-- Live checklist for the new password (the server checks the same rules). --}}
                        <ul class="pw-rules" id="pwRules" aria-live="polite">
                            <li data-rule="len">At least 8 characters</li>
                            <li data-rule="letter">Contains a letter</li>
                            <li data-rule="number">Contains a number</li>
                            <li data-rule="diff">Different from the current password</li>
                        </ul>
                    @elseif ($name === 'password_confirmation')
                        <div class="pw-match" id="pwMatch" aria-live="polite" hidden></div>
                    @endif
                </div>
            @endforeach
            <div><button type="submit" class="vp-btn vp-btn-o" id="pwSubmit"><i class="fa-solid fa-key"></i>Update Password</button></div>
        </form>
    </section>
@endsection

@section('scripts')
    <style>
        .pw-rules { list-style: none; margin: 8px 0 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 4px 12px; font-size: 13px; color: #64748B; }
        .pw-rules li::before { content: '○'; display: inline-block; width: 18px; font-weight: 700; }
        .pw-rules li.ok { color: #166534; }
        .pw-rules li.ok::before { content: '✓'; }
        .pw-match { margin-top: 6px; font-size: 13px; font-weight: 600; }
        .pw-match.ok { color: #166534; }
        .pw-match.bad { color: #B91C1C; }
        .vp-input.is-ok { border-color: #16A34A; }
        .vp-input.is-bad { border-color: #DC2626; }
    </style>
    <script>
        // Live checks: new-password rules and whether the confirmation matches.
        (function () {
            const cur = document.getElementById('current_password'), pw = document.getElementById('password'), conf = document.getElementById('password_confirmation');
            const rules = document.querySelectorAll('#pwRules li'), match = document.getElementById('pwMatch'), submit = document.getElementById('pwSubmit');
            function check() {
                const v = pw.value;
                const ok = { len: v.length >= 8, letter: /[A-Za-z]/.test(v), number: /\d/.test(v), diff: v !== '' && v !== cur.value };
                rules.forEach(function (li) { li.classList.toggle('ok', ok[li.dataset.rule]); });

                // Update stays off until the current password is filled, every rule passes and both match.
                const allOk = cur.value !== '' && Object.values(ok).every(Boolean) && conf.value !== '' && conf.value === v;
                submit.disabled = !allOk;
                submit.title = allOk ? '' : 'Fill all fields: the new password must meet every rule and match the confirmation.';

                if (!conf.value) {
                    match.hidden = true; conf.classList.remove('is-ok', 'is-bad');
                    return;
                }
                const same = conf.value === v;
                match.hidden = false;
                match.className = 'pw-match ' + (same ? 'ok' : 'bad');
                match.innerHTML = same ? '<i class="fa-solid fa-circle-check"></i> Passwords match' : '<i class="fa-solid fa-circle-xmark"></i> Passwords do not match';
                conf.classList.toggle('is-ok', same); conf.classList.toggle('is-bad', !same);
            }
            [cur, pw, conf].forEach(function (el) { el.addEventListener('input', check); });
            check();
        })();

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
