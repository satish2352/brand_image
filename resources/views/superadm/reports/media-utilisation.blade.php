@extends('superadm.layout.master')

@section('content')
    <style>
        .pagination .page-link {
            padding: 6px 12px;
            font-size: 14px;
        }
    </style>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    {{-- HEADER --}}
                    <div class="d-flex justify-content-between mb-3">
                        <h4><b>Media Utilisation Report</b></h4>
                    </div>

                    {{-- FILTERS --}}
                    <form method="GET" class="row mb-3">

                        {{-- Year --}}
                        <div class="col-md-2">
                            <label class="form-label mb-1"><b>Year</b></label>
                            <select name="year" class="form-control">
                                <option value="">Select Year</option>
                                @for ($y = 2025; $y <= now()->year; $y++)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                            @error('year')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Month --}}
                        <div class="col-md-2">
                            <label class="form-label mb-1"><b>Month</b></label>
                            <select name="month" class="form-control">
                                <option value="">Select Month</option>
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- From Date --}}
                        <div class="col-md-2">
                            <label class="form-label mb-1"><b>From Date</b></label>
                            <input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}"
                                class="form-control">
                        </div>

                        {{-- To Date --}}
                        <div class="col-md-2">
                            <label class="form-label mb-1"><b>To Date</b></label>
                            <input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}"
                                class="form-control" disabled>
                        </div>

                        {{-- Media --}}
                        <div class="col-md-2">
                            <label class="form-label mb-1"><b>Media</b></label>
                            <select name="media_id" class="form-control">
                                <option value="">All Media</option>
                                @foreach ($mediaList as $m)
                                    <option value="{{ $m->id }}"
                                        {{ request('media_id') == $m->id ? 'selected' : '' }}>
                                        {{ $m->media_title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Category --}}
                        {{-- <div class="col-md-2">
                        <label class="form-label mb-1">Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">All Categories</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}"
                                    {{ request('category_id')==$c->id?'selected':'' }}>
                                    {{ $c->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div> --}}

                        {{-- Search --}}
                        <div class="col-md-4 mt-2">
                            <label class="form-label mb-1"><b>Search</b></label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Search media / user">
                        </div>

                        {{-- Buttons --}}
                        <div class="col-md-8" style="margin-top: 2rem">
                            <button class="btn btn-success">
                                <i class="mdi mdi-filter"></i> Apply
                            </button>
                            <a href="{{ route('reports.media.utilisation') }}" class="btn btn-secondary">
                                Reset
                            </a>
                        </div>

                    </form>
                    <div class="d-flex justify-content-between mb-3">
                        <h4>Media Utilisation Report</h4>

                        <div class="dropdown">
                            <button class="btn btn-success dropdown-toggle" type="button" data-toggle="dropdown">
                                <i class="mdi mdi-download"></i> Export
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a href="javascript:void(0)" onclick="exportMediaUtilisation('excel')"
                                        class="dropdown-item">
                                        <i class="mdi mdi-file-excel"></i> Export Excel
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" onclick="exportMediaUtilisation('pdf')"
                                        class="dropdown-item">
                                        <i class="mdi mdi-file-pdf"></i> Export PDF
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- TABLE --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr.No</th>
                                    <th>User Name</th>
                                    <th>Media Code</th>
                                    <th>Media Title</th>
                                    <th>Category</th>
                                    <th>Size (WxH)</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>Booked Days</th>
                                    <th>Total (₹)</th>
                                    <th class="text-end">GST (18%) (₹)</th>
                                   <th class="text-end">Final Total (₹)</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($reports as $key => $row)
                                    <tr>
                                        <td>{{ $reports->firstItem() + $key }}</td>
                                        <td>{{ $row->user_name }}</td>
                                        <td>{{ $row->media_code }}</td>
                                        <td>{{ $row->media_title }}</td>
                                        <td>{{ $row->category_name }}</td>
                                        <td>{{ $row->width }} x {{ $row->height }}</td>
                                        <td>{{ \Carbon\Carbon::parse($row->from_date)->format('d-m-Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($row->to_date)->format('d-m-Y') }}</td>
                                        <td>{{ $row->booked_days }}</td>
                                       

                                          <td>₹ {{ number_format($row->total_amount, 2) }}</td>
                                            <td>₹ {{ number_format($row->gst_amount, 2) }}</td>
                                              <td>₹ {{ number_format($row->grand_total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center">No data found</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>

                    {{-- PAGINATION --}}
                    {{-- <div class="mt-3">
                    {{ $reports->links() }}
                </div> --}}

                    {{-- @if ($reports->hasPages()) --}}
                    <div class="d-flex justify-content-between align-items-center mt-3">

                        {{-- LEFT SIDE INFO --}}
                        <div class="text-muted">
                            Showing
                            {{ $reports->firstItem() }}
                            to
                            {{ $reports->lastItem() }}
                            of
                            {{ $reports->total() }}
                            rows
                        </div>

                        {{-- RIGHT SIDE PAGINATION --}}
                        <nav>
                            <ul class="pagination mb-0">

                                {{-- Previous --}}
                                <li class="page-item {{ $reports->onFirstPage() ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $reports->previousPageUrl() }}" tabindex="-1">
                                        Previous
                                    </a>
                                </li>

                                {{-- Page Numbers --}}
                                @foreach ($reports->getUrlRange(1, $reports->lastPage()) as $page => $url)
                                    <li class="page-item {{ $page == $reports->currentPage() ? 'active' : '' }}">
                                        <a class="page-link" href="{{ $url }}">
                                            {{ $page }}
                                        </a>
                                    </li>
                                @endforeach

                                {{-- Next --}}
                                <li class="page-item {{ $reports->hasMorePages() ? '' : 'disabled' }}">
                                    <a class="page-link" href="{{ $reports->nextPageUrl() }}">
                                        Next
                                    </a>
                                </li>

                            </ul>
                        </nav>

                    </div>
                    {{-- @endif --}}


                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const fromDate = document.getElementById('from_date');
            const toDate = document.getElementById('to_date');

            // Page reload (filters applied) case
            if (fromDate.value) {
                toDate.disabled = false;
                toDate.min = fromDate.value;
            }

            fromDate.addEventListener('change', function() {

                if (this.value) {
                    // Enable To Date
                    toDate.disabled = false;

                    // Set minimum date
                    toDate.min = this.value;

                    // If To Date is smaller than From Date → clear it
                    if (toDate.value && toDate.value < this.value) {
                        toDate.value = '';
                    }
                } else {
                    // Reset To Date
                    toDate.value = '';
                    toDate.disabled = true;
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function exportMediaUtilisation(type) {

            let exportUrl = type === 'excel' ?
                "{{ route('reports.media.utilisation.export.excel') }}" :
                "{{ route('reports.media.utilisation.export.pdf') }}";

            // 🔹 Clean params (remove empty values)
            let rawParams = @json(request()->query());
            let cleanParams = {};

            Object.keys(rawParams).forEach(key => {
                if (rawParams[key] !== null && rawParams[key] !== '') {
                    cleanParams[key] = rawParams[key];
                }
            });

            let params = new URLSearchParams(cleanParams).toString();

            // 🔄 SHOW LOADER
            showLoader('Preparing export file...');

            // 1️⃣ CHECK IF DATA EXISTS
            fetch("{{ route('reports.media.utilisation.check-export') }}?" + params)
                .then(res => res.json())
                .then(resp => {

                    if (!resp.hasData) {
                        hideLoader();
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Records',
                            text: 'No records available for export',
                        });
                        return;
                    }

                    // 2️⃣ START DOWNLOAD
                    window.location.href = exportUrl + '?' + params;

                    // 3️⃣ STOP LOADER
                    setTimeout(() => {
                        hideLoader();
                        Swal.fire({
                            icon: 'success',
                            title: 'Download Started',
                            text: 'Your file is downloading',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }, 1200);
                })
                .catch(() => {
                    hideLoader();
                    Swal.fire('Error', 'Export failed', 'error');
                });
        }

        // 🔄 Loader helpers
        function showLoader(message = 'Preparing file...') {
            Swal.fire({
                title: message,
                html: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
        }

        function hideLoader() {
            Swal.close();
        }
    </script>
@endsection
