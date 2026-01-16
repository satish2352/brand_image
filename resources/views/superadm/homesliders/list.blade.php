@extends('superadm.layout.master')

@section('content')
    <div class="card">
        <div class="card-body">

            <div class="d-flex justify-content-between mb-3">
                <h4>Home Sliders</h4>
                <a href="{{ route('homeslider.create') }}" class="btn btn-add">Add Slider</a>
            </div>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Desktop</th>
                        <th>Mobile</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sliders as $key => $row)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td><img src="{{ config('fileConstants.IMAGE_VIEW') . $row->desktop_image }}" height="50"></td>
                            <td><img src="{{ config('fileConstants.IMAGE_VIEW') . $row->mobile_image }}" height="50"></td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" class="toggle-status" data-id="{{ base64_encode($row->id) }}"
                                        {{ $row->is_active ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                            </td>
                            <td class="text-nowrap">

                                {{-- VIEW --}}
                                {{-- <a href="{{ config('fileConstants.IMAGE_VIEW') . $row->desktop_image }}"
                        target="_blank"
                        class="btn btn-sm btn-info">
                            <i class="mdi mdi-eye"></i>
                        </a> --}}

                                {{-- EDIT --}}
                                <a href="{{ route('homeslider.edit', base64_encode($row->id)) }}"
                                    class="btn btn-sm btn-primary">
                                    <i class="mdi mdi-pencil"></i>
                                </a>

                                {{-- DELETE --}}
                                <button type="button" class="btn btn-sm btn-danger delete-slider"
                                    data-id="{{ base64_encode($row->id) }}">
                                    <i class="mdi mdi-delete"></i>
                                </button>

                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).on('change', '.toggle-status', function() {

            let checkbox = $(this);
            let id = checkbox.data('id');
            let isChecked = checkbox.is(':checked');

            Swal.fire({
                title: 'Are you sure?',
                text: isChecked ?
                    'Do you want to activate this slider?' : 'Do you want to deactivate this slider?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel'
            }).then((result) => {

                if (!result.isConfirmed) {
                    // revert checkbox state
                    checkbox.prop('checked', !isChecked);
                    return;
                }

                $.ajax({
                    url: "{{ route('homeslider.status') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id
                    },
                    success: function() {
                        Swal.fire('Updated!', 'Status updated successfully.', 'success');
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                        checkbox.prop('checked', !isChecked);
                    }
                });

            });
        });
    </script>

    <script>
        $('.delete-slider').click(function() {

            let id = $(this).data('id');

            Swal.fire({
                title: 'Delete Slider?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it'
            }).then((result) => {

                if (!result.isConfirmed) return;

                $('<form>', {
                        method: 'POST',
                        action: "{{ route('homeslider.delete') }}"
                    })
                    .append('@csrf')
                    .append(`<input type="hidden" name="id" value="${id}">`)
                    .appendTo('body')
                    .submit();
            });
        });
    </script>
@endsection
