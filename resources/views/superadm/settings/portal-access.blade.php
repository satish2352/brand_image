@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">

                    <h4 class="mb-1">Search Access Settings</h4>
                    <p class="text-muted mb-4" style="font-size:13px;">
                        How long a visitor and a signed-in user may search the inventory.
                        Changes apply to sessions started from now on — anyone already
                        searching keeps the time they were given.
                    </p>

                    @if (session('success'))
                        {{-- No close button: the admin theme does not style
                             Bootstrap 5's .btn-close, so it rendered as a stray
                             mark after the text. Every other alert in the panel
                             is written this way. --}}
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.portal-access.update') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Free Visitor Access</label>
                                <div class="input-group">
                                    <input type="number" name="guest_preview_minutes" class="form-control"
                                        min="1" max="1440" value="{{ old('guest_preview_minutes', $guestPreviewMinutes) }}">
                                    <span class="input-group-text">Minutes</span>
                                </div>
                                <small class="text-muted">
                                    Before a visitor is asked to log in or register. Default 2.
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Logged-in Search Access</label>
                                <div class="input-group">
                                    <input type="number" name="search_session_minutes" class="form-control"
                                        min="1" max="1440" value="{{ old('search_session_minutes', $searchSessionMinutes) }}">
                                    <span class="input-group-text">Minutes</span>
                                </div>
                                <small class="text-muted">
                                    Granted at login or registration. Default 5.
                                </small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch"
                                id="gateEnabled" name="portal_access_gate_enabled" value="1"
                                {{ old('portal_access_gate_enabled', $gateEnabled) ? 'checked' : '' }}>
                            <label class="form-check-label" for="gateEnabled">
                                <strong>Enable timed search access</strong><br>
                                <small class="text-muted">
                                    Off means no timer at all — the site behaves as it did before this
                                    feature. Use this to switch the whole gate off without a deploy.
                                </small>
                            </label>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch"
                                id="exemptPaying" name="exempt_paying_customers" value="1"
                                {{ old('exempt_paying_customers', $exemptPayingCustomers) ? 'checked' : '' }}>
                            <label class="form-check-label" for="exemptPaying">
                                <strong>Exempt paying customers</strong><br>
                                <small class="text-muted">
                                    A user who has completed a booking is not put on a timer.
                                </small>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">How it works</h5>
                    <ul class="ps-3 mb-0" style="font-size:13px; line-height:1.7;">
                        <li>A new visitor gets the free preview, timed from their first page view.</li>
                        <li>Refreshing, reopening the browser or opening more tabs does not restart it.</li>
                        <li>Logging in or registering starts one search session for that user.</li>
                        <li>Every search request is checked on the server, so the timer cannot be bypassed from the browser.</li>
                        <li>When a session ends the user stays logged in — only searching stops.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
