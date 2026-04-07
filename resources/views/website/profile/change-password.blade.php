@extends('website.layout')

@section('content')
    <style>
        .cp-card {
            max-width: 540px;
            margin: 130px auto 60px auto;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
            overflow: hidden;
        }

        .cp-card-header {
            background: #f28123;
            padding: 24px 32px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .cp-card-header h5 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .cp-card-body {
            background: #fff;
            padding: 30px 32px;
        }

        .cp-card-body .form-label {
            font-weight: 600;
            font-size: 14px;
        }

        .cp-card-body .form-control:focus {
            border-color: #f28123;
            box-shadow: 0 0 0 0.2rem rgba(242, 129, 35, 0.20);
        }

        .cp-password-wrapper {
            position: relative;
        }

        .cp-password-wrapper .cp-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 16px;
            z-index: 5;
            user-select: none;
        }

        .cp-password-wrapper .cp-toggle:hover {
            color: #f28123;
        }

        .cp-password-wrapper .form-control {
            padding-right: 40px;
        }

        .strength-bar {
            height: 5px;
            border-radius: 3px;
            margin-top: 6px;
            transition: all 0.3s;
            width: 0%;
        }

        .strength-label {
            font-size: 12px;
            margin-top: 3px;
        }
    </style>

    <div class="container">
        <div class="cp-card">
            <div class="cp-card-header">
                <i class="bi bi-shield-lock fs-4"></i>
                <h5>Change Password</h5>
            </div>
            <div class="cp-card-body">

                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
                    <i class="bi bi-info-circle me-1"></i>
                    After a successful password change, you will be logged out automatically.
                </div>

                <form method="POST" action="{{ route('website.profile.change-password.post') }}" id="changePasswordForm"
                    novalidate>
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="cp-password-wrapper">
                            <input type="password" name="new_password" id="newPassword"
                                class="form-control @error('new_password') is-invalid @enderror"
                                placeholder="Minimum 6 characters" autocomplete="new-password">
                            <i class="bi bi-eye-slash cp-toggle" data-target="newPassword"></i>
                        </div>
                        <div class="strength-bar" id="strengthBar"></div>
                        <div class="strength-label" id="strengthLabel"></div>
                        @error('new_password')
                            <div class="text-danger mt-1" style="font-size:13px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="cp-password-wrapper">
                            <input type="password" name="new_password_confirmation" id="confirmPassword"
                                class="form-control" placeholder="Re-enter new password" autocomplete="new-password">
                            <i class="bi bi-eye-slash cp-toggle" data-target="confirmPassword"></i>
                        </div>
                        <div class="text-danger mt-1" id="matchError" style="font-size:13px; display:none;">
                            Passwords do not match.
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-dark px-4" id="submitBtn">
                            <i class="bi bi-check-lg me-1"></i> Change Password
                        </button>
                        <a href="{{ route('website.profile.view') }}"
                            class="btn btn-outline-secondary d-flex align-self-center px-4 btn-align" style="padding:12px;">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {

            /* ---- Toggle — uses data-target, no conflict with global handler ---- */
            $('.cp-toggle').on('click', function() {
                const targetId = $(this).data('target');
                const input = $('#' + targetId);

                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    $(this).removeClass('bi-eye-slash').addClass('bi-eye');
                } else {
                    input.attr('type', 'password');
                    $(this).removeClass('bi-eye').addClass('bi-eye-slash');
                }
            });

            /* ---- Password strength indicator ---- */
            $('#newPassword').on('input', function() {
                const val = this.value;
                const bar = $('#strengthBar');
                const label = $('#strengthLabel');

                if (!val) {
                    bar.css({
                        width: '0%',
                        background: ''
                    });
                    label.text('');
                    return;
                }

                let strength = 0;
                if (val.length >= 6) strength++;
                if (val.length >= 10) strength++;
                if (/[A-Z]/.test(val)) strength++;
                if (/[0-9]/.test(val)) strength++;
                if (/[^A-Za-z0-9]/.test(val)) strength++;

                const levels = [{
                        pct: '20%',
                        color: '#dc3545',
                        text: 'Very Weak'
                    },
                    {
                        pct: '40%',
                        color: '#fd7e14',
                        text: 'Weak'
                    },
                    {
                        pct: '60%',
                        color: '#ffc107',
                        text: 'Fair'
                    },
                    {
                        pct: '80%',
                        color: '#20c997',
                        text: 'Strong'
                    },
                    {
                        pct: '100%',
                        color: '#198754',
                        text: 'Very Strong'
                    },
                ];

                const lvl = levels[Math.min(strength - 1, 4)];
                bar.css({
                    width: lvl.pct,
                    background: lvl.color
                });
                label.text(lvl.text).css('color', lvl.color);
            });

            /* ---- Confirm password match ---- */
            function checkMatch() {
                const np = $('#newPassword').val();
                const cp = $('#confirmPassword').val();
                if (cp.length && np !== cp) {
                    $('#matchError').show();
                    return false;
                }
                $('#matchError').hide();
                return true;
            }

            $('#confirmPassword, #newPassword').on('input', checkMatch);

            /* ---- Submit validation ---- */
            $('#changePasswordForm').on('submit', function(e) {
                let valid = true;

                if (!$('#newPassword').val() || $('#newPassword').val().length < 6) {
                    valid = false;
                }
                if (!checkMatch() || !$('#confirmPassword').val()) {
                    valid = false;
                }

                if (!valid) e.preventDefault();
            });

        });
    </script>
@endsection
