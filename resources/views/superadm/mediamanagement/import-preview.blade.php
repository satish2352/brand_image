@extends('superadm.layout.master')

@php
    use Illuminate\Support\Str;

    /**
     * Confirmation wording for the publish step, in plain language: what will be
     * added, what will be overwritten, and what is being left behind.
     */
    $newCount = (int) $batch['summary']['insert'];
    $updateCount = (int) $batch['summary']['update'];
    $failedCount = (int) $batch['summary']['failed'];

    // This exact sheet has been published before and would add copies rather than
    // change anything, so publishing has to be opted into instead of being the
    // obvious next step.
    $isReupload = !empty($batch['already_published']) && $newCount > 0;

    $readyCount = $newCount + $updateCount;

    $publishTitle = $readyCount === 1
        ? 'Save this media record now?'
        : 'Save these ' . number_format($readyCount) . ' media records now?';

    $parts = [];

    if ($newCount > 0) {
        $parts[] = '<b>' . number_format($newCount) . '</b> new '
            . Str::plural('media record', $newCount) . ' will be added to your inventory';
    }

    if ($updateCount > 0) {
        $parts[] = '<b>' . number_format($updateCount) . '</b> existing '
            . Str::plural('record', $updateCount)
            . ' will be overwritten with the values from your file';
    }

    $publishMessage = ucfirst(implode(', and ', $parts)) . '.';

    if ($failedCount > 0) {
        $publishMessage .= ' The ' . number_format($failedCount) . ' '
            . Str::plural('row', $failedCount)
            . ' listed in the error log will be skipped — you can fix '
            . ($failedCount === 1 ? 'it' : 'them') . ' and upload again afterwards.';
    }

    $publishMessage .= '<br><br>This cannot be undone automatically, so please make sure the preview '
        . 'below looks right.';

    // Last chance to stop an accidental second import of the same sheet.
    if (!empty($batch['already_published'])) {
        $publishTitle = 'This file has already been imported';
        $publishMessage = '<b>' . e($batch['file_name']) . '</b> was published before, adding '
            . number_format((int) $batch['already_published']['inserted']) . ' '
            . Str::plural('record', (int) $batch['already_published']['inserted'])
            . '. Publishing it again adds <b>another '
            . number_format($newCount) . ' ' . Str::plural('copy', $newCount)
            . '</b> rather than changing those records.<br><br>Are you sure you want to continue?';
    }
@endphp

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
                                // On a re-upload these rows are copies, not progress — saying
                                // "Ready To Import" in green would contradict the warning below.
                                $isReupload
                                    ? ['Duplicate Copies', $batch['summary']['ready'], 'danger', 'fa-copy']
                                    : ['Ready To Import', $batch['summary']['ready'], 'success', 'fa-check-circle'],
                                ['New Records', $batch['summary']['insert'], $isReupload ? 'danger' : 'primary', 'fa-plus-circle'],
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

                    {{-- IMAGES ZIP --}}
                    @if (!empty($batch['images_zip']))
                        @php $zip = $batch['images_zip']; @endphp
                        <div class="alert alert-info">
                            <i class="fa fa-file-archive-o"></i>
                            <b>Images ZIP:</b> {{ number_format($zip['files']) }} picture(s) read.
                            @if ($zip['unused'] > 0)
                                <span class="text-danger">
                                    {{ number_format($zip['unused']) }} of them are not named by any row —
                                    check the Image URLs column for typos, or they will simply be discarded.
                                </span>
                            @else
                                Every picture in the archive is claimed by a row.
                            @endif
                            @if (!empty($zip['skipped']))
                                <div class="mt-1">
                                    <small>
                                        Ignored (not a JPG, PNG or WebP):
                                        {{ implode(', ', $zip['skipped']) }}@if ($zip['skipped_total'] > count($zip['skipped'])) and {{ $zip['skipped_total'] - count($zip['skipped']) }} more @endif
                                    </small>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- ALREADY IMPORTED THIS EXACT FILE --}}
                    @if (!empty($batch['already_published']))
                        @php $prior = $batch['already_published']; @endphp
                        <div class="card border-danger mb-4">
                            <div class="card-body">
                                <h5 class="text-danger mb-2">
                                    <i class="fa fa-triangle-exclamation"></i>
                                    You have already imported this file
                                </h5>
                                <p class="mb-2" style="font-size:13.5px;">
                                    <b>{{ $batch['file_name'] }}</b> was published on
                                    <b>{{ $prior['at'] ? \Carbon\Carbon::parse($prior['at'])->format('d M Y, g:i a') : 'an earlier date' }}</b>,
                                    adding <b>{{ number_format($prior['inserted']) }}</b>
                                    {{ \Illuminate\Support\Str::plural('record', $prior['inserted']) }}@if ($prior['updated'] > 0) and updating {{ number_format($prior['updated']) }} @endif.
                                </p>
                                <p class="mb-2" style="font-size:13.5px;">
                                    Publishing it again will add
                                    <b>another {{ number_format($batch['summary']['insert']) }}</b>
                                    {{ \Illuminate\Support\Str::plural('copy', $batch['summary']['insert']) }},
                                    because the <b>Hoarding Code</b> column in this file is empty — the codes from last
                                    time (HD000001…) were generated here and were never written back into your
                                    spreadsheet, so there is nothing for these rows to match against.
                                </p>
                                <p class="mb-3 text-muted" style="font-size:13px;">
                                    <b>To change what you imported last time instead:</b> press
                                    <b>Cancel Import</b>, go to the <b>Export</b> tab and download those records — that
                                    file has the Hoarding Codes in it. Edit the cells you want in <em>that</em> file and
                                    upload it with <b>Add new and update existing</b> selected.
                                </p>

                                {{-- Publishing stays possible (re-importing after a deletion is
                                     legitimate) but has to be asked for on purpose. --}}
                                @if (!empty($batch['rows']))
                                    {{-- A flex label, not Bootstrap's custom-control: that nests the
                                         explanation inside an inline <label>, where a block of text
                                         escapes the box instead of growing it. --}}
                                    <label for="allowDuplicateImport"
                                        style="display:flex; align-items:flex-start; gap:10px; margin:0; cursor:pointer;">
                                        <input type="checkbox" id="allowDuplicateImport"
                                            style="flex:0 0 auto; width:16px; height:16px; margin:2px 0 0; cursor:pointer;">
                                        <span style="flex:1 1 auto; min-width:0;">
                                            <b>I know these are duplicates — add them as
                                                {{ number_format($batch['summary']['insert']) }} new
                                                {{ \Illuminate\Support\Str::plural('record', $batch['summary']['insert']) }}
                                                anyway.</b>
                                            <span class="d-block text-muted" style="font-size:12.5px; margin-top:3px;">
                                                Tick this only if you deleted them and want them back. Otherwise press
                                                Cancel Import.
                                            </span>
                                        </span>
                                    </label>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- WORTH A SECOND LOOK (these rows still import) --}}
                    @if (!empty($batch['warnings']))
                        <div class="card border-warning mb-4">
                            <div class="card-body">
                                <h5 class="mb-2" style="color:#b8860b;">
                                    <i class="fa fa-info-circle"></i>
                                    {{ count($batch['warnings']) }}
                                    {{ \Illuminate\Support\Str::plural('row', count($batch['warnings'])) }}
                                    worth a quick check
                                </h5>
                                <p class="text-muted mb-3" style="font-size:13px;">
                                    These rows <b>will be imported</b> — one vendor can of course have several
                                    media at the same spot (two faces of a gantry, panels along one wall, a bus
                                    fleet at one depot). This is only here in case a row was pasted twice.
                                </p>

                                <div class="table-responsive" style="max-height:300px; overflow:auto;">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:70px;">Sr No.</th>
                                                <th style="width:90px;">Sheet Row</th>
                                                <th style="width:200px;">Media Title</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($batch['warnings'] as $index => $warning)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $warning['row'] }}</td>
                                                    <td>{{ $warning['media_title'] ?: '-' }}</td>
                                                    <td>{{ $warning['message'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

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
                                                <th style="width:70px;">Sr No.</th>
                                                <th style="width:90px;">Sheet Row</th>
                                                <th style="width:140px;">Hoarding Code</th>
                                                <th style="width:150px;">Already In Inventory As</th>
                                                <th style="width:200px;">Media Title</th>
                                                <th>Problem(s) Found</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($batch['errors'] as $index => $error)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $error['row'] }}</td>
                                                    <td>{{ ($error['hoarding_code'] ?? '') ?: '-' }}</td>
                                                    <td>
                                                        @if (!empty($error['existing_code']))
                                                            <b>{{ $error['existing_code'] }}</b>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ ($error['media_title'] ?? '') ?: '-' }}</td>
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
                        <div class="card {{ $isReupload ? 'border-danger' : 'border-success' }} mb-4">
                            <div class="card-body">
                                @if ($isReupload)
                                    {{-- Do not call these "ready to publish" when publishing them
                                         means a second copy of what this file already imported. --}}
                                    <h5 class="text-danger mb-1">
                                        <i class="fa fa-copy"></i>
                                        {{ count($batch['rows']) }}
                                        {{ \Illuminate\Support\Str::plural('row', count($batch['rows'])) }}
                                        would be added again as duplicate
                                        {{ \Illuminate\Support\Str::plural('copy', count($batch['rows'])) }}
                                        @if (count($batch['rows']) > $previewLimit)
                                            <small class="text-muted">— showing the first {{ $previewLimit }}</small>
                                        @endif
                                    </h5>
                                    <p class="text-muted mb-3" style="font-size:13px;">
                                        Each one is marked <b>New</b> because this file gives no Hoarding Code to
                                        match on — they would not replace the records it added last time.
                                    </p>
                                @else
                                    <h5 class="text-success mb-3">
                                        <i class="fa fa-check-circle"></i>
                                        Records ready to publish ({{ count($batch['rows']) }})
                                        @if (count($batch['rows']) > $previewLimit)
                                            <small class="text-muted">
                                                — showing the first {{ $previewLimit }}
                                            </small>
                                        @endif
                                    </h5>
                                @endif

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
                                <button type="submit"
                                    class="btn {{ $isReupload ? 'btn-outline-danger' : 'btn-success' }}"
                                    id="publishBtn" {{ $isReupload ? 'disabled' : '' }}>
                                    <i class="fa fa-upload"></i>
                                    @if ($isReupload)
                                        {{ 'Publish ' . count($batch['rows']) . ' Duplicate ' . Str::plural('Copy', count($batch['rows'])) }}
                                    @else
                                        {{ 'Confirm & Publish ' . count($batch['rows']) . ' ' . Str::plural('Record', count($batch['rows'])) }}
                                    @endif
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

            // A re-upload of an already published file keeps Publish switched off
            // until it is explicitly asked for.
            $('#allowDuplicateImport').on('change', function () {
                $('#publishBtn').prop('disabled', !this.checked);
            });

            $('#publishForm').on('submit', function (e) {
                if ($(this).data('confirmed')) return;

                e.preventDefault();
                const form = this;

                Swal.fire({
                    title: {!! json_encode($publishTitle) !!},
                    html: {!! json_encode($publishMessage) !!},
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, save them',
                    cancelButtonText: 'Not yet'
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
