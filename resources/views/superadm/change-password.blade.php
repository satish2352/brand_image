@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="page-header">
                <h3 class="page-title">Change Password</h3>
            </div>

            {{-- Success Message --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <form class="forms-sample" action="{{ route('admin.update-password') }}" method="POST" id="regForm">
                        @csrf

                        {{-- New Password --}}
                        <div class="form-group position-relative">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" name="new_password" id="new_password"
                                placeholder="Enter New Password">

                            <i class="fa fa-eye password-toggle" onclick="togglePassword('new_password')"
                                style="position:absolute; right:10px; top:38px; cursor:pointer;"></i>

                            <span class="text-danger js-error d-none" id="newPasswordError"></span>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="form-group position-relative">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password"
                                placeholder="Confirm Password">

                            <i class="fa fa-eye password-toggle" onclick="togglePassword('confirm_password')"
                                style="position:absolute; right:10px; top:38px; cursor:pointer;"></i>

                            <span class="text-danger js-error d-none" id="confirmPasswordError"></span>
                        </div>

                        {{-- Submit --}}
                        <div class="text-center">
                            <button type="submit" class="btn btn-sm btn-success btn-add" disabled>
                                Save & Submit
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Password Changed',
                text: '{{ session('success') }}',
                confirmButtonColor: '#198754',
                confirmButtonText: 'OK'
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'OK'
            });
        </script>
    @endif


    <script>
        $(document).ready(function() {

            // ✔ Letters + Numbers + @ , min 6 characters
            const passwordRegex = /^[A-Za-z0-9@]{6,}$/;

            function validatePasswords() {
                let newPassword = $('#new_password').val().trim();
                let confirmPassword = $('#confirm_password').val().trim();
                let valid = true;

                // Clear old errors
                $('.js-error').addClass('d-none').text('');

                // New Password check
                if (newPassword === '') {
                    $('#newPasswordError')
                        .removeClass('d-none')
                        .text('Enter new password');
                    valid = false;
                } else if (!passwordRegex.test(newPassword)) {
                    $('#newPasswordError')
                        .removeClass('d-none')
                        .text('Password must contain letters, numbers and @ (minimum 6 characters)');
                    valid = false;
                }

                // Confirm Password check
                if (confirmPassword === '') {
                    $('#confirmPasswordError')
                        .removeClass('d-none')
                        .text('Confirm your password');
                    valid = false;
                } else if (newPassword !== confirmPassword) {
                    $('#confirmPasswordError')
                        .removeClass('d-none')
                        .text('Passwords do not match');
                    valid = false;
                }

                // Enable / Disable submit button
                $('.btn-add').prop('disabled', !valid);
                return valid;
            }

            // Live validation while typing
            $('#new_password, #confirm_password').on('keyup blur', validatePasswords);

            // Final check on submit
            $('#regForm').on('submit', function() {
                return validatePasswords();
            });

        });
    </script>

    {{-- Show / Hide Password --}}
    <script>
        function togglePassword(fieldId) {
            let field = document.getElementById(fieldId);
            let icon = field.nextElementSibling;

            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
@endsection
