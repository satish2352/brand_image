@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    {{-- HEADER --}}
                    <div class="d-flex justify-content-between mb-3">
                        <h4>City List</h4>
                        <a href="{{ route('city.create') }}" class="btn btn-add">
                            Add City
                        </a>
                    </div>

                    {{-- FLASH MESSAGES --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- TABLE --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatables">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr.No.</th>
                                    <th>State</th>
                                    <th>District</th>
                                    <th>City Name</th>
                                    <th>Latitude</th>
                                    <th>Longitude</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($cities as $key => $city)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $city->state_name }}</td>
                                        <td>{{ $city->district_name }}</td>
                                        <td>{{ $city->city_name }}</td>
                                        <td>{{ $city->latitude }}</td>
                                        <td>{{ $city->longitude }}</td>
                                        <td>
                                            <form action="{{ route('city.updatestatus') }}" method="POST"
                                                class="d-inline-block delete-form">
                                                @csrf
                                                <label class="switch">
                                                    <input type="checkbox" class="toggle-status"
                                                        data-id="{{ base64_encode($city->id) }}"
                                                        {{ $city->is_active == '1' ? 'checked' : '' }}>
                                                    <span class="slider"></span>
                                                </label>

                                                <input type="hidden" name="id"
                                                    value="{{ base64_encode($city->id) }}">
                                            </form>
                                        </td>
                                        <td class="d-flex">
                                            <a href="{{ route('city.edit', base64_encode($city->id)) }}"
                                                class="btn btn-sm btn-primary icon-medium mr-2">
                                                <i class="mdi mdi-square-edit-outline"></i>
                                            </a>
                                            <form action="{{ route('city.delete') }}" method="POST"
                                                class="d-inline-block">
                                                @csrf
                                                <input type="hidden" name="id"
                                                    value="{{ base64_encode($city->id) }}">
                                                <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                    title="Delete">
                                                    <i class="mdi mdi-trash-can-outline icon-medium"></i>
                                                </button>
                                            </form>
                                        </td>


                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            No cities found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @section('scripts')
        <script>
            /* ================= STATUS TOGGLE ================= */
            $('.toggle-status').change(function() {

                let id = $(this).data('id');

                $.post("{{ route('city.updatestatus') }}", {
                    _token: "{{ csrf_token() }}",
                    id: id
                }, function(response) {
                    toastr.success(response.message);
                }).fail(function() {
                    toastr.error('Failed to update status');
                });

            });

            /* ================= DELETE ================= */
            /* ================= DELETE WITH SWEET ALERT ================= */


            // DELETE CONFIRMATION
            $(document).on('click', '.delete-btn', function() {
                let form = $(this).closest('form');

                Swal.fire({
                    title: 'Are You Sure?',
                    text: 'Do you really want to delete this record?',
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#28a745",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                }).then(res => {
                    if (res.isConfirmed) form.submit();
                });
            });
        </script>
    @endsection
@endsection
