@extends('superadm.layout.master')

@section('content')
    <style>
        /* The admin theme (asset/css/style.css) pushes every checkbox off-screen
           and draws a fake one on the label after it. These bulk-select boxes
           have no label, so bring the native checkbox back. */
        input.media-check[type="checkbox"],
        input.media-check[type="checkbox"]:checked,
        input.media-check[type="checkbox"]:not(:checked) {
            position: static;
            left: auto;
            opacity: 1;
            width: 17px;
            height: 17px;
            cursor: pointer;
            accent-color: #008a93;
            vertical-align: middle;
        }
    </style>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    {{-- FLASH --}}
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    {{-- ROWS THE BULK IMPORT COULD NOT PUBLISH --}}
                    @if (!empty(session('import_skipped')))
                        <div class="alert alert-warning">
                            <b>{{ count(session('import_skipped')) }} row(s) were skipped during the import:</b>
                            <ul class="mb-0 mt-2">
                                @foreach (session('import_skipped') as $skipped)
                                    <li>Row {{ $skipped['row'] }} — {{ $skipped['issues'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- IMAGES THE BULK IMPORT COULD NOT DOWNLOAD (the records themselves were saved) --}}
                    @if (!empty(session('import_image_warnings')))
                        <div class="alert alert-warning">
                            <b>
                                {{ count(session('import_image_warnings')) }} record(s) were saved but some images
                                could not be downloaded:
                            </b>
                            <ul class="mb-0 mt-2">
                                @foreach (session('import_image_warnings') as $warning)
                                    <li>
                                        Row {{ $warning['row'] }}
                                        @if (!empty($warning['media_title']))
                                            ({{ $warning['media_title'] }})
                                        @endif
                                        — {{ $warning['issues'] }}
                                    </li>
                                @endforeach
                            </ul>
                            <small class="d-block mt-2">
                                You can add these images from the Edit Media screen.
                            </small>
                        </div>
                    @endif

                    {{-- TABLE --}}
                    <div class="table">
                        <form method="GET" class="mb-3">
                            @if (request('per_page'))
                                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                            @endif
                            <div class="row">

                                <div class="col-md-3">
                                    <label><b>Vendor</b></label>
                                    <select name="vendor_id" class="form-control">
                                        <option value="">Select Vendor</option>
                                        @foreach ($vendors as $v)
                                            <option value="{{ $v->id }}"
                                                {{ request('vendor_id') == $v->id ? 'selected' : '' }}>
                                                {{ $v->vendor_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label><b>Category</b></label>
                                    <select name="category_id" class="form-control">
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}"
                                                {{ request('category_id') == $c->id ? 'selected' : '' }}>
                                                {{ $c->category_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label><b>District</b></label>
                                    <select name="district_id" id="district_id" class="form-control">
                                        <option value="">Select District</option>
                                        @foreach ($districts as $d)
                                            <option value="{{ $d->id }}"
                                                {{ request('district_id') == $d->id ? 'selected' : '' }}>
                                                {{ $d->district_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label><b>Town</b></label>
                                    <select name="city_id" id="city_id" class="form-control">
                                        <option value="">Select Town</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <label><b>Year</b></label>
                                    <select name="year" class="form-control">
                                        <option value="">Select Year</option>
                                        @foreach ($years as $y)
                                            <option value="{{ $y }}"
                                                {{ request('year') == $y ? 'selected' : '' }}>
                                                {{ $y }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label><b>Month</b></label>
                                    <select name="month" class="form-control">
                                        <option value="">Select Month</option>
                                        @foreach ($months as $num => $name)
                                            <option value="{{ $num }}"
                                                {{ request('month') == $num ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label><b>From</b></label>
                                    <input type="date" name="from_date" class="form-control"
                                        value="{{ request('from_date') }}">
                                </div>

                                <div class="col-md-3">
                                    <label><b>To</b></label>
                                    <input type="date" name="to_date" class="form-control"
                                        value="{{ request('to_date') }}">
                                </div>

                                <div class="col-md-3 mt-3">
                                    <label><b>Sector Code</b></label>
                                    <input type="text" name="hoarding_code" class="form-control"
                                        placeholder="e.g. HD000007 or BS000012" value="{{ request('hoarding_code') }}">
                                </div>

                                <div class="col-md-6 d-flex align-items-end mt-3">
                                    <button class="btn btn-success m-2">Filter</button>
                                    <a href="{{ route('media.list') }}" class="btn btn-secondary m-2">Reset</a>
                                </div>
                            </div>
                        </form>


                        {{-- HEADER --}}
                        <div class="d-flex justify-content-between mb-3">
                            <h4>Media List</h4>
                            <div>
                                {{-- Opens on the Import tab; the list's current filters still ride along so
                                     switching to Export keeps whatever the user was browsing. --}}
                                <a href="{{ route('media.import-export', array_merge(request()->query(), ['tab' => 'import'])) }}"
                                    class="btn btn-primary">
                                    <i class="fa fa-exchange-alt"></i> Import / Export
                                </a>
                                <a href="{{ route('media.create') }}" class="btn btn-add">
                                    Add Media
                                </a>
                            </div>
                        </div>
                        {{-- BULK AVAILABILITY: tick rows, then flag them. A Not Available
                             hoarding stays on the website but cannot be booked. --}}
                        <div class="d-flex align-items-center mb-2">
                            <button type="button" class="btn btn-danger btn-sm m-1 bulk-availability"
                                data-available="0" disabled>
                                <i class="fa fa-ban"></i> Not Available
                            </button>
                            <button type="button" class="btn btn-success btn-sm m-1 bulk-availability"
                                data-available="1" disabled>
                                <i class="fa fa-check"></i> Available
                            </button>
                            <span class="text-muted" style="margin-left:12px;font-size:15px;font-weight:500;" id="selectedCount"></span>
                            {{-- Shown once the whole page is ticked and there are more pages. --}}
                            @if ($mediaList->total() > $mediaList->count())
                                <span class="d-none" style="margin-left:12px;font-size:15px;font-weight:600;" id="selectAllPrompt">
                                    <a href="javascript:void(0)" id="selectAllMatching" style="text-decoration:underline;">
                                        Select all {{ $mediaList->total() }} media
                                    </a>
                                </span>
                                <span class="d-none" style="margin-left:12px;font-size:15px;" id="clearAllPrompt">
                                    <b>All {{ $mediaList->total() }} media selected.</b>
                                    <a href="javascript:void(0)" id="clearSelection">Clear selection</a>
                                </span>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">

                                {{-- <table class="table table-bordered table-striped datatables"> --}}
                                <thead class="table-light">
                                    <tr>
                                        <th><input type="checkbox" class="media-check" id="selectAllMedia" title="Select all"></th>
                                        <th>Sr.No</th>
                                        {{-- Not "Hoarding Code": the column carries every
                                             scheme, HD for hoardings and BS for bus
                                             shelters. Named apart from the Media Code
                                             column further right, which is a different
                                             field (media_management.media_code). --}}
                                        <th>Sector Code</th>
                                        <th>Media Title</th>
                                        <th>Category</th>
                                        <th>State</th>
                                        <th>District</th>
                                        <th>City</th>
                                        <th>Area</th>
                                        <th>Price</th>
                                        <th>Vendor Name</th>
                                        <th>Media Code</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($mediaList as $key => $media)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="media-check media-select"
                                                    value="{{ base64_encode($media->id) }}">
                                            </td>
                                            {{-- <td>{{ $key + 1 }}</td> --}}
                                            <td>{{ $mediaList->firstItem() + $key }}</td>
                                            <td><span class="badge bg-success text-white">{{ $media->hoarding_code ?? '-' }}</span></td>
                                            <td>
                                                {{ $media->media_title ?? '-' }}
                                                @if (isset($media->is_available) && !$media->is_available)
                                                    <br><span class="badge bg-danger text-white">Not Available</span>
                                                @endif
                                            </td>
                                            <td>{{ $media->category_name ?? '-' }}</td>
                                            <td>{{ $media->state_name ?? '-' }}</td>
                                            <td>{{ $media->district_name ?? '-' }}</td>
                                            <td>{{ $media->city_name ?? '-' }}</td>
                                            <td>{{ $media->area_name ?? '-' }}</td>
                                            <td>
                                                ₹ {{ $media->price !== null ? number_format($media->price, 2) : '-' }}
                                            </td>
                                            <td>{{ $media->vendor_name ?? '-' }}</td>
                                            <td>{{ $media->media_code ?? '-' }}</td>
                                            <td>
                                                <label class="switch">
                                                    <input type="checkbox" class="toggle-status"
                                                        data-id="{{ base64_encode($media->id) }}"
                                                        {{ $media->is_active ? 'checked' : '' }}>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                            <td class="d-flex">

                                                {{-- View Details --}}
                                                <a href="{{ route('media.viewdetails', base64_encode($media->id)) }}"
                                                    class="btn btn-success btn-sm m-1" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </a>

                                                {{-- View Images --}}
                                                <a href="{{ route('media.view', base64_encode($media->id)) }}"
                                                    class="btn btn-secondary btn-sm m-1" title="Add Images">
                                                    <i class="fa fa-image"></i>
                                                </a>

                                                {{-- Edit --}}
                                                <a href="{{ route('media.edit', base64_encode($media->id)) }}"
                                                    class="btn btn-primary btn-sm m-1" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>

                                                {{-- Delete --}}
                                                <button type="button" class="btn btn-danger btn-sm delete-btn m-1"
                                                    data-id="{{ base64_encode($media->id) }}" title="Delete">
                                                    <i class="fa fa-trash"></i>
                                                </button>

                                            </td>


                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="14" class="text-center">
                                                No media found
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-between  mt-3">

                                {{-- LEFT : COUNT --}}
                                <div class="text-muted d-flex align-items-start">
                                    Showing {{ $mediaList->firstItem() }} to {{ $mediaList->lastItem() }}
                                    of {{ $mediaList->total() }} rows
                                </div>

                                {{-- RIGHT : ROWS PER PAGE + PAGINATION --}}
                                <div class="d-flex align-items-start">
                                    <div class="d-flex align-items-center mr-3" style="margin-right:12px;">
                                        <label for="perPage" class="text-muted mb-0" style="margin-right:6px;">Show</label>
                                        <select id="perPage" class="form-control form-control-sm" style="width:auto;">
                                            @foreach ([10, 20, 50, 100] as $size)
                                                <option value="{{ $size }}"
                                                    {{ $mediaList->perPage() == $size ? 'selected' : '' }}>
                                                    {{ $size }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{ $mediaList->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        @section('scripts')
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                $(document).ready(function() {

                    // ================= LOAD CITIES =================
                    $(document).ready(function() {

                        function loadCities(districtId, selectedCity = '') {

                            if (districtId === '') {
                                $('#city_id').html('<option value="">Select Town</option>');
                                return;
                            }

                            $('#city_id').html('<option value="">Loading...</option>');

                            $.ajax({
                                url: "{{ url('get-cities') }}/" + districtId,
                                type: "GET",
                                success: function(response) {

                                    let options = '<option value="">Select Town</option>';

                                    $.each(response, function(key, city) {

                                        let selected = (city.id == selectedCity) ?
                                            'selected' :
                                            '';

                                        options += `
                        <option value="${city.id}" ${selected}>
                            ${city.city_name}
                        </option>
                    `;
                                    });

                                    $('#city_id').html(options);
                                }
                            });
                        }

                        // 🔥 ON DISTRICT CHANGE
                        $('#district_id').on('change', function() {
                            loadCities($(this).val());
                        });

                        // 🔥 IMPORTANT — PAGE RELOAD CASE
                        let districtId = "{{ request('district_id') }}";
                        let cityId = "{{ request('city_id') }}";

                        if (districtId !== '') {
                            loadCities(districtId, cityId);
                        }

                    });

                });


                // ================= STATUS TOGGLE =================
                $(document).on('change', '.toggle-status', function() {

                    let id = $(this).data('id');

                    $.post("{{ route('media.status') }}", {
                        _token: "{{ csrf_token() }}",
                        id: id
                    }, function(response) {
                        toastr.success(response.message);
                    }).fail(function() {
                        toastr.error('Failed to update status');
                    });

                });


                // ================= ROWS PER PAGE =================
                // Keeps the current filters, restarts at page 1.
                $(document).on('change', '#perPage', function() {
                    let url = new URL(window.location.href);
                    url.searchParams.set('per_page', this.value);
                    url.searchParams.delete('page');
                    window.location.href = url.toString();
                });


                // ================= BULK AVAILABILITY =================
                // true once "Select all N media" is clicked: the action then covers every
                // record matching the filters, not only the rows on this page.
                let selectAllMatching = false;
                const totalMatching = {{ $mediaList->total() }};
                @php
                    $listFilters = request()->only(['vendor_id', 'category_id', 'district_id', 'city_id', 'month', 'year', 'from_date', 'to_date', 'hoarding_code']);
                @endphp
                const listFilters = @json((object) $listFilters);

                function refreshSelection() {
                    let count = $('.media-select:checked').length;
                    let pageFull = count > 0 && count === $('.media-select').length;

                    if (!pageFull) selectAllMatching = false;

                    $('.bulk-availability').prop('disabled', count === 0);
                    $('#selectAllMedia').prop('checked', pageFull);
                    $('#selectedCount').text(selectAllMatching ? '' : (count ? count + ' selected' : ''));
                    $('#selectAllPrompt').toggleClass('d-none', !pageFull || selectAllMatching);
                    $('#clearAllPrompt').toggleClass('d-none', !selectAllMatching);
                }

                $(document).on('change', '#selectAllMedia', function() {
                    $('.media-select').prop('checked', this.checked);
                    refreshSelection();
                });

                $(document).on('change', '.media-select', refreshSelection);

                $(document).on('click', '#selectAllMatching', function() {
                    selectAllMatching = true;
                    refreshSelection();
                });

                $(document).on('click', '#clearSelection', function() {
                    selectAllMatching = false;
                    $('.media-select').prop('checked', false);
                    refreshSelection();
                });

                $(document).on('click', '.bulk-availability', function() {

                    let ids = $('.media-select:checked').map(function() {
                        return this.value;
                    }).get();
                    if (!ids.length) return;

                    let available = $(this).data('available');
                    let label = available == 1 ? 'Available' : 'Not Available';
                    let total = selectAllMatching ? totalMatching : ids.length;

                    Swal.fire({
                        title: 'Mark as ' + label + '?',
                        text: available == 1 ?
                            total + ' media will be bookable on the website again.' :
                            total + ' media will show as Not Available on the website and cannot be booked.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: available == 1 ? '#198754' : '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, mark ' + label
                    }).then((result) => {

                        if (!result.isConfirmed) return;

                        // select_all carries the list's current filters (from the URL) so the
                        // server picks the same records the list is showing.
                        let payload = selectAllMatching ?
                            Object.assign({}, listFilters, {
                                select_all: 1
                            }) : {
                                ids: ids
                            };

                        $.post("{{ route('media.availability') }}", Object.assign(payload, {
                            _token: "{{ csrf_token() }}",
                            is_available: available
                        }), function(response) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 700);
                        }).fail(function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'Failed to update availability');
                        });

                    });

                });


                // ================= DELETE =================
                $(document).on('click', '.delete-btn', function() {

                    let id = $(this).data('id');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This media will be permanently deleted.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it'
                    }).then((result) => {

                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: "{{ route('media.delete') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                id: id
                            },
                            success: function() {
                                location.reload();
                            }
                        });

                    });

                });
            </script>
        @endsection
    @endsection
