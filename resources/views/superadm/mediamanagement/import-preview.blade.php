@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="mb-1">Import Preview</h4>
                            <small class="text-muted">
                                File: <b>{{ $batch['file_name'] }}</b> &nbsp;|&nbsp;
                                Mode:
                                <b>{{ $batch['mode'] === 'upsert' ? 'Add new and update existing' : 'Add new records only' }}</b>
                            </small>
                        </div>
                        <a href="{{ route('media.import-export') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>

                    {{-- SUMMARY --}}
                    <div class="row mb-4">
                        @php
                            $cards = [
                                ['Rows Read', $batch['summary']['total_rows'], 'secondary', 'fa-table'],
                                ['Ready To Import', $batch['summary']['ready'], 'success', 'fa-check-circle'],
                                ['New Records', $batch['summary']['insert'], 'primary', 'fa-plus-circle'],
                                ['Records To Update', $batch['summary']['update'], 'info', 'fa-sync'],
                                ['Rows With Errors', $batch['summary']['failed'], 'danger', 'fa-exclamation-triangle'],
                            ];
                        @endphp

                        @foreach ($cards as [$label, $value, $colour, $icon])
                            <div class="col-md col-6 mb-2">
                                <div class="card border-{{ $colour }} h-100">
                                    <div class="card-body text-center py-3">
                                        <i class="fa {{ $icon }} text-{{ $colour }}" style="font-size:20px;"></i>
                                        <h3 class="mb-0 mt-2 text-{{ $colour }}">{{ number_format($value) }}</h3>
                                        <small class="text-muted">{{ $label }}</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- ERROR LOG --}}
                    @if (!empty($batch['errors']))
                        <div class="card border-danger mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-danger mb-0">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        Error Log — {{ count($batch['errors']) }} row(s) will be skipped
                                    </h5>
                                    <a href="{{ route('media.import.errorlog', $batch['token']) }}"
                                        class="btn btn-danger btn-sm">
                                        <i class="fa fa-download"></i> Download Error Log
                                    </a>
                                </div>

                                <div class="table-responsive" style="max-height:360px; overflow:auto;">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:90px;">Sheet Row</th>
                                                <th style="width:140px;">Hoarding Code</th>
                                                <th style="width:200px;">Media Title</th>
                                                <th>Problem(s) Found</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($batch['errors'] as $error)
                                                <tr>
                                                    <td>{{ $error['row'] }}</td>
                                                    <td>{{ $error['hoarding_code'] ?: '-' }}</td>
                                                    <td>{{ $error['media_title'] ?: '-' }}</td>
                                                    <td class="text-danger">{{ $error['issues'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <small class="text-muted d-block mt-2">
                                    Fix these rows in your file and upload them again — publishing now will import only
                                    the valid rows listed below.
                                </small>
                            </div>
                        </div>
                    @endif

                    {{-- VALID ROWS --}}
                    @if (!empty($batch['rows']))
                        <div class="card border-success mb-4">
                            <div class="card-body">
                                <h5 class="text-success mb-3">
                                    <i class="fa fa-check-circle"></i>
                                    Records ready to publish ({{ count($batch['rows']) }})
                                    @if (count($batch['rows']) > $previewLimit)
                                        <small class="text-muted">
                                            — showing the first {{ $previewLimit }}
                                        </small>
                                    @endif
                                </h5>

                                <div class="table-responsive" style="max-height:460px; overflow:auto;">
                                    <table class="table table-sm table-bordered table-striped mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Sheet Row</th>
                                                <th>Action</th>
                                                <th>Hoarding Code</th>
                                                <th>Media Title</th>
                                                <th>Category</th>
                                                <th>State</th>
                                                <th>District</th>
                                                <th>City</th>
                                                <th>Area</th>
                                                <th>Vendor</th>
                                                <th>Size (WxH)</th>
                                                <th>GPS</th>
                                                <th>Price</th>
                                                <th>Images</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($previewRows as $row)
                                                <tr>
                                                    <td>{{ $row['row'] }}</td>
                                                    <td>
                                                        @if ($row['action'] === 'update')
                                                            <span class="badge badge-info">Update</span>
                                                        @else
                                                            <span class="badge badge-primary">New</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $row['display']['hoarding_code'] }}</td>
                                                    <td>{{ $row['display']['media_title'] ?: '-' }}</td>
                                                    <td>{{ $row['display']['category'] }}</td>
                                                    <td>{{ $row['display']['state'] }}</td>
                                                    <td>{{ $row['display']['district'] }}</td>
                                                    <td>{{ $row['display']['city'] }}</td>
                                                    <td>{{ $row['display']['area'] }}</td>
                                                    <td>{{ $row['display']['vendor'] }}</td>
                                                    <td>{{ $row['display']['size'] }}</td>
                                                    <td>{{ $row['display']['gps'] }}</td>
                                                    <td>₹ {{ number_format((float) $row['display']['price'], 2) }}</td>
                                                    <td>
                                                        @if (!empty($row['display']['images']))
                                                            <span class="badge badge-secondary">
                                                                {{ $row['display']['images'] }}
                                                            </span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $row['display']['status'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <b>No valid rows found.</b> Every row in this file failed validation — download the error log,
                            correct the issues and upload the file again.
                        </div>
                    @endif

                    {{-- ACTIONS --}}
                    <div class="d-flex flex-wrap align-items-center">
                        @if (!empty($batch['rows']))
                            <form action="{{ route('media.import.publish') }}" method="POST" id="publishForm"
                                class="m-1">
                                @csrf
                                <input type="hidden" name="token" value="{{ $batch['token'] }}">
                                <button type="submit" class="btn btn-success" id="publishBtn">
                                    <i class="fa fa-upload"></i>
                                    Confirm &amp; Publish {{ count($batch['rows']) }} Record(s)
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('media.import.discard') }}" method="POST" class="m-1">
                            @csrf
                            <input type="hidden" name="token" value="{{ $batch['token'] }}">
                            <button type="submit" class="btn btn-danger">
                                <i class="fa fa-times"></i> Cancel Import
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function () {
            $('#publishForm').on('submit', function (e) {
                if ($(this).data('confirmed')) return;

                e.preventDefault();
                const form = this;

                Swal.fire({
                    title: 'Publish this import?',
                    text: "{{ count($batch['rows']) }} record(s) will be written to the media inventory."
                        + "{{ $batch['summary']['update'] > 0 ? ' ' . $batch['summary']['update'] . ' existing record(s) will be overwritten.' : '' }}",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, publish'
                }).then(result => {
                    if (!result.isConfirmed) return;

                    $(form).data('confirmed', true);
                    $('#publishBtn')
                        .prop('disabled', true)
                        .html('<i class="fa fa-spinner fa-spin"></i> Publishing…');
                    form.submit();
                });
            });
        });
    </script>
@endsection
