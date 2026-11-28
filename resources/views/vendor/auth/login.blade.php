<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendor Login · Brand Adda</title>
    <link rel="icon" type="image/webp" href="{{ asset('assets/img/logo/brand_adda_browser.webp') }}">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="{{ asset('asset/css/vendor-panel.css') }}?v={{ @filemtime(public_path('asset/css/vendor-panel.css')) }}" rel="stylesheet">
    <style>
        .vl-wrap { display: flex; flex-wrap: wrap; min-height: 100vh; }
        .vl-brand { flex: 1 1 520px; background: #0F172A; color: #fff; padding: 56px 64px; display: flex; flex-direction: column; justify-content: space-between; gap: 48px; }
        .vl-brand h1 { margin: 18px 0 16px; font-size: 42px; line-height: 1.15; font-weight: 800; }
        .vl-brand p { margin: 0; font-size: 16px; line-height: 1.6; color: #CBD5E1; }
        .vl-brand ul { margin: 28px 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 14px; font-size: 15px; color: #E2E8F0; }
        .vl-brand li { display: flex; gap: 12px; align-items: center; }
        .vl-tick { width: 26px; height: 26px; border-radius: 999px; background: #F97316; color: #0F172A; display: grid; place-items: center; font-size: 12px; flex: none; }
        .vl-form-side { flex: 1 1 520px; display: grid; place-items: center; padding: 48px 24px; background: #fff; }
        .vl-form { width: 100%; max-width: 420px; display: flex; flex-direction: column; gap: 20px; }
        .vl-pill { display: inline-block; padding: 6px 12px; border-radius: 999px; background: #1E293B; color: #FDBA74; font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .vl-eye { position: absolute; right: 4px; top: 2px; width: 44px; height: 44px; border: 0; background: transparent; color: #475569; cursor: pointer; }
        @media (max-width: 640px) { .vl-brand { padding: 36px 24px; } .vl-brand h1 { font-size: 30px; } }
    </style>
</head>

<body class="vp-body">
    <div class="vl-wrap">
        <section class="vl-brand">
            <img src="{{ asset('assets/img/logo/brand_adda_light.png') }}" alt="Brand Adda" style="width:220px;height:auto">
            <div style="max-width:520px">
                <span class="vl-pill">Vendor Panel</span>
                <h1>Manage your outdoor media inventory in one place.</h1>
                <p>Update availability, add new sites, upload installation and monitoring photos, and see every change reflected on Brand Adda instantly.</p>
                <ul>
                    <li><span class="vl-tick"><i class="fa-solid fa-check"></i></span>Only your own inventory, kept separate from other vendors</li>
                    <li><span class="vl-tick"><i class="fa-solid fa-check"></i></span>Real-time sync with the central Brand Adda inventory</li>
                    <li><span class="vl-tick"><i class="fa-solid fa-check"></i></span>Your own showcase website with your logo</li>
                </ul>
            </div>
            <div style="font-size:13px;color:#94A3B8">© {{ date('Y') }} Brand Adda · Maharashtra’s Outdoor Media Platform</div>
        </section>

        <section class="vl-form-side">
            <form class="vl-form" method="POST" action="{{ route('vendor.login.submit') }}" novalidate>
                @csrf
                <div>
                    <h2 style="margin:0;font-size:30px;font-weight:800">Vendor Login</h2>
                    <p style="margin:8px 0 0;font-size:15px;color:#475569">Sign in with the email and password sent to you by Brand Adda.</p>
                </div>

                @if (session('error'))
                    <div class="vp-alert vp-alert-error" role="alert">{{ session('error') }}</div>
                @endif
                @if (session('success'))
                    <div class="vp-alert vp-alert-success" role="status">{{ session('success') }}</div>
                @endif

                <div>
                    <label class="vp-label" for="email">Email ID <span style="color:#B91C1C" aria-hidden="true">*</span></label>
                    <input id="email" name="email" type="email" class="vp-input" value="{{ old('email') }}" autocomplete="username" required autofocus>
                    @error('email')<div style="color:#B91C1C;font-size:13px;margin-top:6px">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="vp-label" for="password">Password <span style="color:#B91C1C" aria-hidden="true">*</span></label>
                    <div style="position:relative">
                        <input id="password" name="password" type="password" class="vp-input" style="padding-right:56px" autocomplete="current-password" required>
                        <button type="button" class="vl-eye" aria-label="Show password" onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.setAttribute('aria-label',p.type==='password'?'Show password':'Hide password')">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    @error('password')<div style="color:#B91C1C;font-size:13px;margin-top:6px">{{ $message }}</div>@enderror
                </div>
                <label style="display:flex;align-items:center;gap:8px;min-height:44px;color:#334155;font-size:14px">
                    <input type="checkbox" name="remember" value="1" style="width:18px;height:18px;accent-color:#F97316">Remember me
                </label>
                <button type="submit" class="vp-btn vp-btn-o" style="min-height:50px;font-size:16px;font-weight:800">Login</button>
                <div style="padding:14px 16px;border-radius:10px;background:#F8FAFC;border:1px solid #E5E7EB;font-size:13px;line-height:1.5;color:#475569">
                    Don’t have login details? Your credentials are emailed by the Brand Adda Super Admin. Contact your Brand Adda account manager to get access.
                </div>
            </form>
        </section>
    </div>
</body>

</html>
