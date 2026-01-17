@extends('superadm.layout.master')

@section('content')
    <style>
        .pagination .page-link {
            padding: 6px 12px;
            font-size: 14px;
        }
    </style>

    <div class="card">
        <div class="card-body">

            <h4 class="mb-3">Revenue Report</h4>

            {{-- TABS --}}
            <ul class="nav nav-tabs mb-3">

                {{-- MEDIA --}}
                <li class="nav-item">
                    <a class="nav-link {{ $type == 'media' ? 'active' : '' }}"
                        href="{{ route('reports.revenue.index', ['report_type' => 'media']) }}">
                        Media-wise
                    </a>
                </li>

                {{-- USER --}}
                <li class="nav-item">
                    <a class="nav-link {{ $type == 'user' ? 'active' : '' }}"
                        href="{{ route('reports.revenue.index', ['report_type' => 'user']) }}">
                        User-wise
                    </a>
                </li>

                {{-- MONTH --}}
                <li class="nav-item">
                    <a class="nav-link {{ $type == 'date' ? 'active' : '' }}"
                        href="{{ route('reports.revenue.index', ['report_type' => 'date']) }}">
                        Month-wise
                    </a>
                </li>

            </ul>

            {{-- FILTERS --}}
            <form method="GET" class="row mb-4">
                <input type="hidden" name="report_type" value="{{ $type }}">

                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-control">
                        <option value="">Select Year</option>
                        @for ($y = 2024; $y <= now()->year; $y++)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                    {{--  ERROR MESSAGE --}}
                    @error('year')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-control">
                        <option value="">Select Month</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($type !== 'date')
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                            placeholder="Search media / user">
                    </div>
                @endif

                <div class="col-md-5" style="margin-top: 2rem">
                    <button class="btn btn-primary">
                        <i class="mdi mdi-filter"></i> Apply
                    </button>
                    <a href="{{ route('reports.revenue.index') }}" class="btn btn-secondary">
                        Reset
                    </a>
                </div>
            </form>

            <div class="d-flex justify-content-between mb-3">
                <h4></h4>

                <div class="dropdown">
                    <button class="btn btn-success dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="mdi mdi-download"></i> Export
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a href="javascript:void(0)" onclick="exportReport('excel')" class="dropdown-item">
                                <i class="mdi mdi-file-excel"></i> Export Excel
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" onclick="exportReport('pdf')" class="dropdown-item">
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
                            <th>Sr. No</th>

                            @if ($type === 'date')
                                <th>Period</th>
                                <th>Total Bookings</th>
                                {{-- <th>Total Revenue (₹)</th> --}}
                                <th>Amount (₹)</th>
                                <th>GST (₹)</th>
                                <th>Final Total (₹)</th>
                            @elseif($type === 'media')
                                <th>Media Code</th>
                                <th>Category</th>
                                <th>Media Title</th>
                                <th>State</th>
                                <th>District</th>
                                <th>City</th>
                                <th>Area</th>
                                <th>Size (WxH)</th>
                                <th>Total Bookings</th>
                                <th>Booked Days</th>
                                <th>Payment Mode</th>
                                {{-- <th>Total Revenue (₹)</th> --}}
                                <th>Amount (₹)</th>
                                <th>GST (₹)</th>
                                <th>Final Total (₹)</th>
                            @elseif($type === 'user')
                                <th>User Name</th>
                                <th>Total Bookings</th>
                                <th>Booked Days</th>
                                <th>Payment Mode</th>
                                {{-- <th>Total Revenue (₹)</th> --}}
                                <th>Amount (₹)</th>
                                <th>GST (₹)</th>
                                <th>Final Total (₹)</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($reports as $key => $row)
                            <tr>
                                <td>{{ $reports->firstItem() + $key }}</td>

                                @if ($type === 'date')
                                    <td>{{ $row->period }}</td>
                                    {{-- <td>{{ $row->total_bookings }}</td> --}}
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"
                                            onclick="openBookingModal('{{ $row->period }}')">
                                            <i class="mdi mdi-eye"></i> {{ $row->total_bookings }}
                                        </button>
                                    </td>
                                    {{-- PAYMENT MODE --}}

                                    {{-- <td>
                                        @if ($row->payment_status === 'PAID')
                                            <span class="badge badge-success">ONLINE</span>
                                        @elseif ($row->payment_status === 'ADMIN_BOOKED')
                                            <span class="badge badge-warning">OFFLINE</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $row->payment_status }}</span>
                                        @endif
                                    </td> --}}

                                    {{-- <td>₹ {{ number_format($row->total_revenue, 2) }}</td> --}}
                                    <td>₹ {{ number_format($row->total_amount, 2) }}</td>
                                    <td>₹ {{ number_format($row->gst_amount, 2) }}</td>
                                    <td><strong>₹ {{ number_format($row->grand_total, 2) }}</strong></td>
                                @elseif($type === 'media')
                                    <td>{{ $row->media_code }}</td>
                                    <td>{{ $row->category_name }}</td>
                                    <td>{{ $row->media_title }}</td>
                                    <td>{{ $row->state_name }}</td>
                                    <td>{{ $row->district_name }}</td>
                                    <td>{{ $row->city_name }}</td>
                                    <td>{{ $row->area_name ?? '-' }}</td>
                                    <td>{{ $row->width }} x {{ $row->height }}</td>
                                    <td>{{ $row->total_bookings }}</td>
                                    <td>{{ $row->booked_days }}</td>
                                    <td>
                                        @if ($row->payment_status === 'PAID')
                                            <span class="badge badge-success">ONLINE</span>
                                        @elseif ($row->payment_status === 'ADMIN_BOOKED')
                                            <span class="badge badge-offline">OFFLINE (Admin Booked)</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $row->payment_status }}</span>
                                        @endif
                                    </td>

                                    {{-- <td>₹ {{ number_format($row->total_revenue, 2) }}</td> --}}
                                    <td>₹ {{ number_format($row->total_amount, 2) }}</td>
                                    <td>₹ {{ number_format($row->gst_amount, 2) }}</td>
                                    <td><strong>₹ {{ number_format($row->grand_total, 2) }}</strong></td>
                                @elseif($type === 'user')
                                    <td>{{ $row->user_name }}</td>
                                    {{-- <td>{{ $row->total_bookings }}</td> --}}
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"
                                            onclick="openUserBookingModal({{ $row->id }}, '{{ $row->user_name }}')">
                                            View ({{ $row->total_bookings }})
                                        </button>
                                    </td>
                                    <td>{{ $row->booked_days }}</td>
                                    <td>
                                        @if ($row->payment_status === 'PAID')
                                            <span class="badge badge-success">ONLINE</span>
                                        @elseif ($row->payment_status === 'ADMIN_BOOKED')
                                            <span class="badge badge-warning">OFFLINE</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $row->payment_status }}</span>
                                        @endif
                                    </td>

                                    {{-- <td>₹ {{ number_format($row->total_revenue, 2) }}</td> --}}
                                    <td>₹ {{ number_format($row->total_amount, 2) }}</td>
                                    <td>₹ {{ number_format($row->gst_amount, 2) }}</td>
                                    <td><strong>₹ {{ number_format($row->grand_total, 2) }}</strong></td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center">No data found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing {{ $reports->firstItem() }} to {{ $reports->lastItem() }}
                    of {{ $reports->total() }} rows
                </div>

                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item {{ $reports->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $reports->previousPageUrl() }}">Previous</a>
                        </li>

                        @foreach ($reports->getUrlRange(1, $reports->lastPage()) as $page => $url)
                            <li class="page-item {{ $page == $reports->currentPage() ? 'active' : '' }}">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endforeach

                        <li class="page-item {{ $reports->hasMorePages() ? '' : 'disabled' }}">
                            <a class="page-link" href="{{ $reports->nextPageUrl() }}">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>
    </div>

    <div class="modal fade" id="bookingModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalTitle"></h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body" style="max-height:60vh; overflow-y:auto;">
                    <div id="bookingModalBody" class="text-center text-muted">
                        Loading...
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>


    <script>
        function openBookingModal(period) {

            // $('#bookingModalTitle').text('Revenue Breakdown – ' + period);
            $('#bookingModalTitle').html(
                `Revenue Breakdown – ${period} <small class="text-muted">(Paid bookings)</small>`
            );
            $('#bookingModalBody').html('<div class="text-center">Loading...</div>');

            // Bootstrap 4 open
            $('#bookingModal').modal('show');

            fetch("{{ route('reports.revenue.month.details') }}?period=" + period)
                .then(res => res.json())
                .then(rows => {

                    if (!rows.length) {
                        $('#bookingModalBody').html(
                            '<p class="text-center text-muted">No records found</p>'
                        );
                        return;
                    }

                    let html = `
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>User Name</th>
                        <th>Media Code</th>
                        <th>Media Title</th>
                        <th>Category</th>
                        <th>Booked Days</th>
                          <th>Payment Mode</th>
                        <th>Amount (₹)</th>
                        <th>GST Amount (₹)</th>
                        <th>Final Total (₹)</th>
                    </tr>
                </thead>
                <tbody>`;

                    rows.forEach(r => {
                        html += `
                <tr>
                    <td>${r.user_name}</td> 
                    <td>${r.media_code}</td>
                    <td>${r.media_title}</td>
                    <td>${r.category_name}</td>
                    <td>${r.booked_days}</td>
                      <td>${paymentBadge(r.payment_status)}</td>
                    <td>₹ ${parseFloat(r.total_amount).toFixed(2)}</td>
                    <td>₹ ${parseFloat(r.gst_amount).toFixed(2)}</td>
                    <td><strong>₹ ${parseFloat(r.grand_total).toFixed(2)}</strong></td>
                </tr>`;
                    });

                    html += '</tbody></table>';

                    $('#bookingModalBody').html(html);
                });
        }
    </script>

    <script>
        function openUserBookingModal(userId, userName) {

            // $('#bookingModalTitle').text('User Revenue Breakdown – ' + userName);
            $('#bookingModalTitle').html(
                `User Revenue Breakdown – ${userName} <small class="text-muted">(Paid bookings)</small>`
            );
            $('#bookingModalBody').html('Loading...');

            $('#bookingModal').modal('show');

            fetch("{{ route('reports.revenue.user.details') }}?user_id=" + userId)
                .then(res => res.json())
                .then(rows => {

                    if (!rows.length) {
                        $('#bookingModalBody').html(
                            '<p class="text-center text-muted">No records found</p>'
                        );
                        return;
                    }

                    let html = `
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Media Code</th>
                        <th>Media Title</th>
                        <th>Category</th>
                        <th>From - To</th>
                        <th>Booked Days</th>
                          <th>Payment Mode</th>
                        <th>Revenue (₹)</th>
                    </tr>
                </thead>
                <tbody>`;

                    rows.forEach(r => {
                        html += `
                <tr>
                    <td>${r.media_code}</td>
                    <td>${r.media_title}</td>
                    <td>${r.category_name}</td>
                    <td>${r.from_date} → ${r.to_date}</td>
                    <td>${r.booked_days}</td>
                   <td>${paymentBadge(r.payment_status)}</td>
                    <td>₹ ${parseFloat(r.price).toFixed(2)}</td>
                </tr>`;
                    });

                    html += '</tbody></table>';

                    $('#bookingModalBody').html(html);
                });
        }
    </script>



    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function exportReport(type) {

            let exportUrl = type === 'excel' ?
                "{{ route('reports.revenue.export.excel') }}" :
                "{{ route('reports.revenue.export.pdf') }}";

            // 🔴 IMPORTANT FIX:
            // get params & REMOVE EMPTY VALUES
            let rawParams = @json(request()->query());
            let cleanParams = {};

            Object.keys(rawParams).forEach(key => {
                if (rawParams[key] !== null && rawParams[key] !== '') {
                    cleanParams[key] = rawParams[key];
                }
            });

            let params = new URLSearchParams(cleanParams).toString();

            showLoader('Preparing export file...');

            // 1️⃣ CHECK DATA EXISTS
            fetch("{{ route('reports.revenue.check-export') }}?" + params)
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

                    // 2️⃣ DOWNLOAD
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
    </script>


    <script>
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
    <script>
        function paymentBadge(status) {
            if (status === 'PAID') {
                return '<span class="badge badge-success">ONLINE</span>';
            }
            if (status === 'ADMIN_BOOKED') {
                return '<span class="badge badge-warning">OFFLINE</span>';
            }
            return '<span class="badge badge-secondary">' + status + '</span>';
        }
    </script>

@endsection
