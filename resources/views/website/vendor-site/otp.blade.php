@extends('website.vendor-site.layout')
@section('title', 'Verify')

@section('styles')
    <style>
        .otp { width: 52px; height: 60px; border: 1px solid #CBD5E1; border-radius: 12px; text-align: center; font: 800 24px 'Montserrat', sans-serif; color: #0F172A; }
        .otp:focus { outline: none; border: 2px solid #F97316; }
        @media (max-width: 420px) { .otp { width: 42px; height: 52px; font-size: 20px; } }
    </style>
@endsection

@section('content')
    <main style="min-height:calc(100vh - 81px);display:grid;place-items:center;padding:48px 20px;background:linear-gradient(180deg,#FFF7ED 0%,#F8FAFC 60%)">
        <div style="width:100%;max-width:480px;background:#fff;border:1px solid #E5E7EB;border-radius:20px;padding:36px;box-shadow:0 20px 40px rgba(15,23,42,.08);display:flex;flex-direction:column;gap:22px">
            <div style="width:64px;height:64px;border-radius:16px;background:#FFF7ED;color:#C2410C;display:grid;place-items:center;font-size:26px"><i class="fa-regular fa-envelope"></i></div>
            <div>
                <h1 style="margin:0;font-size:26px;font-weight:800">Verify to view inventory</h1>
                <p style="margin:8px 0 0;font-size:15px;line-height:1.6;color:#475569">
                    @if ($otpSent)
                        We sent a 6-digit code to <strong style="color:#0F172A">{{ $maskedEmail }}</strong>. Enter it below to open {{ $vendor->vendor_name }}’s media and map.
                    @else
                        A one-time code will be sent to <strong style="color:#0F172A">{{ $maskedEmail }}</strong>, the email registered for {{ $vendor->vendor_name }}.
                    @endif
                </p>
            </div>

            @if (session('error'))<div class="vs-alert vs-alert-error" role="alert">{{ session('error') }}</div>@endif
            @if (session('success'))<div class="vs-alert vs-alert-success" role="status">{{ session('success') }}</div>@endif

            @if ($otpSent)
                <form method="POST" action="{{ route('vendor-site.otp.verify', $vendor->slug) }}" style="display:flex;flex-direction:column;gap:22px" id="otpForm">
                    @csrf
                    <fieldset style="border:0;margin:0;padding:0">
                        <legend style="font-size:13px;font-weight:600;color:#334155;margin-bottom:10px">One-time password</legend>
                        <div style="display:flex;gap:10px;justify-content:space-between">
                            @for ($i = 1; $i <= 6; $i++)
                                <input class="otp" name="otp[]" inputmode="numeric" maxlength="1" autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i }}" required @if ($i === 1) autofocus @endif>
                            @endfor
                        </div>
                    </fieldset>
                    <button type="submit" class="vs-btn vs-btn-o" style="min-height:50px;font-size:16px;font-weight:800">Verify &amp; Continue</button>
                </form>
                <form method="POST" action="{{ route('vendor-site.otp.send', $vendor->slug) }}" style="display:flex;justify-content:space-between;align-items:center;font-size:14px;color:#475569;margin:0">
                    @csrf
                    <span>The code is valid for {{ $minutes }} minutes.</span>
                    <button type="submit" style="background:none;border:0;color:#C2410C;font:700 14px Montserrat,sans-serif;cursor:pointer;min-height:44px">Resend OTP</button>
                </form>
            @else
                <form method="POST" action="{{ route('vendor-site.otp.send', $vendor->slug) }}" style="margin:0">
                    @csrf
                    <button type="submit" class="vs-btn vs-btn-o" style="width:100%;min-height:50px;font-size:16px;font-weight:800">Send OTP</button>
                </form>
            @endif
        </div>
    </main>
@endsection

@section('scripts')
    <script>
        // Typing moves to the next box; pasting a whole code fills them all.
        const boxes = Array.from(document.querySelectorAll('.otp'));
        boxes.forEach(function (box, i) {
            box.addEventListener('input', function () {
                box.value = box.value.replace(/\D/g, '').slice(0, 1);
                if (box.value && boxes[i + 1]) boxes[i + 1].focus();
            });
            box.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !box.value && boxes[i - 1]) boxes[i - 1].focus();
            });
            box.addEventListener('paste', function (e) {
                const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6).split('');
                if (digits.length < 2) return;
                e.preventDefault();
                digits.forEach(function (d, j) { if (boxes[j]) boxes[j].value = d; });
                (boxes[digits.length] || boxes[5]).focus();
            });
        });
    </script>
@endsection
