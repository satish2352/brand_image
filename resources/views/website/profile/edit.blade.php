@extends('website.layout')

@section('content')
    <style>
        .profile-form-card {
            max-width: 640px;
            margin: 130px auto 60px auto;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
            overflow: hidden;
        }

        .profile-form-header {
            background: #f28123;
            padding: 24px 32px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .profile-form-header h5 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .profile-form-body {
            background: #fff;
            padding: 30px 32px;
        }

        .profile-form-body .form-label {
            font-weight: 600;
            font-size: 14px;
        }

        .profile-form-body .form-control:focus {
            border-color: #f28123;
            box-shadow: 0 0 0 0.2rem rgba(242, 129, 35, 0.20);
        }

        .email-note {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        .btn-align {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }
    </style>

    <div class="container">
        <div class="profile-form-card">
            <div class="profile-form-header">
                <i class="bi bi-pencil-square fs-4"></i>
                <h5>Edit Profile</h5>
            </div>
            <div class="profile-form-body">

                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('website.profile.update') }}" id="editProfileForm" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}" maxlength="100" placeholder="Enter your full name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                        <div class="email-note"><i class="bi bi-info-circle"></i> Email cannot be changed.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_number"
                            class="form-control @error('mobile_number') is-invalid @enderror"
                            value="{{ old('mobile_number', $user->mobile_number) }}" maxlength="10" inputmode="numeric"
                            placeholder="10-digit mobile number">
                        @error('mobile_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Organisation <span class="text-muted">(optional)</span></label>
                        <input type="text" name="organisation"
                            class="form-control @error('organisation') is-invalid @enderror"
                            value="{{ old('organisation', $user->organisation) }}" maxlength="150"
                            placeholder="Your company or organisation">
                        @error('organisation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">GST Number <span class="text-muted">(optional)</span></label>
                        <input type="text" name="gst" class="form-control @error('gst') is-invalid @enderror"
                            value="{{ old('gst', $user->gst) }}" maxlength="15" placeholder="e.g. 27ABCDE1234F1Z5">
                        @error('gst')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-3 btn-align">
                        <button type="submit" class="btn btn-dark px-4 ">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
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

            // Allow only letters and spaces in name
            $('input[name="name"]').on('input', function() {
                this.value = this.value.replace(/[^A-Za-z\s]/g, '');
            });

            // Allow only digits in mobile
            $('input[name="mobile_number"]').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
            });

            // Auto-uppercase GST
            $('input[name="gst"]').on('input', function() {
                this.value = this.value.toUpperCase().replace(/[^0-9A-Z]/g, '').substring(0, 15);
            });
        });
    </script>
@endsection
