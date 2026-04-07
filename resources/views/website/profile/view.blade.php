@extends('website.layout')

@section('content')
    <style>
        .profile-card {
            max-width: 680px;
            margin: 130px auto 60px auto;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
            overflow: hidden;
        }

        .profile-card-header {
            background: #f28123;
            padding: 32px 32px 24px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            flex-shrink: 0;
        }

        .profile-card-header h4 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }

        .profile-card-header span {
            font-size: 14px;
            opacity: 0.9;
        }

        .profile-card-body {
            padding: 30px 32px;
            background: #fff;
        }

        .profile-field {
            display: flex;
            align-items: flex-start;
            padding: 14px 0;
            border-bottom: 1px solid #f1f1f1;
            gap: 16px;
        }

        .profile-field:last-child {
            border-bottom: none;
        }

        .profile-field-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #fff5ec;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f28123;
            font-size: 16px;
            flex-shrink: 0;
        }

        .profile-field-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-field-value {
            font-size: 15px;
            color: #222;
            font-weight: 500;
        }

        .profile-field-value.muted {
            color: #aaa;
            font-style: italic;
            font-weight: 400;
        }

        .profile-actions {
            margin-top: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
    </style>

    <div class="container">
        <div class="profile-card">
            <div class="profile-card-header">
                <div class="profile-avatar-circle">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <h4>{{ $user->name }}</h4>
                    <span>{{ $user->email }}</span>
                </div>
            </div>
            <div class="profile-card-body">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
                        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="profile-field">
                    <div class="profile-field-icon"><i class="bi bi-person"></i></div>
                    <div>
                        <div class="profile-field-label">Full Name</div>
                        <div class="profile-field-value">{{ $user->name }}</div>
                    </div>
                </div>

                <div class="profile-field">
                    <div class="profile-field-icon"><i class="bi bi-envelope"></i></div>
                    <div>
                        <div class="profile-field-label">Email Address</div>
                        <div class="profile-field-value">{{ $user->email }}</div>
                    </div>
                </div>

                <div class="profile-field">
                    <div class="profile-field-icon"><i class="bi bi-phone"></i></div>
                    <div>
                        <div class="profile-field-label">Mobile Number</div>
                        <div class="profile-field-value">{{ $user->mobile_number }}</div>
                    </div>
                </div>

                <div class="profile-field">
                    <div class="profile-field-icon"><i class="bi bi-building"></i></div>
                    <div>
                        <div class="profile-field-label">Organisation</div>
                        <div class="profile-field-value {{ $user->organisation ? '' : 'muted' }}">
                            {{ $user->organisation ?? 'Not provided' }}
                        </div>
                    </div>
                </div>

                <div class="profile-field">
                    <div class="profile-field-icon"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="profile-field-label">GST Number</div>
                        <div class="profile-field-value {{ $user->gst ? '' : 'muted' }}">
                            {{ $user->gst ?? 'Not provided' }}
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="{{ route('website.profile.edit') }}" class="btn btn-dark">
                        <i class="bi bi-pencil-square me-1"></i> Edit Profile
                    </a>
                    <a href="{{ route('website.profile.change-password') }}"
                        class="btn btn-outline-secondary d-flex align-self-center" style="padding:12px;">
                        <i class="bi bi-shield-lock me-1"></i> Change Password
                    </a>
                </div>

            </div>
        </div>
    </div>
@endsection
