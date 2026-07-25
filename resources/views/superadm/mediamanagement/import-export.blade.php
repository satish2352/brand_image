@extends('superadm.layout.master')

@section('content')
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
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- HEADER --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Bulk Data — Import &amp; Export</h4>
                        <a href="{{ route('media.list') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Media List
                        </a>
                    </div>

                    {{-- TABS --}}
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'import' ? 'active' : '' }}" data-toggle="tab"
                                href="#tab-import" role="tab">
                                <i class="fa fa-upload"></i> Import
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'export' ? 'active' : '' }}" data-toggle="tab"
                                href="#tab-export" role="tab">
                                <i class="fa fa-download"></i> Export
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content pt-4">

                        {{-- ==========================================================
                             IMPORT TAB
                        =========================================================== --}}
                        <div class="tab-pane fade {{ $activeTab === 'import' ? 'show active' : '' }}" id="tab-import"
                            role="tabpanel">

                            <div class="row">
                                {{-- STEP 1 : TEMPLATE (per category, horizontal) --}}
                                <div class="col-12 mb-4">
                                    <div class="card border">
                                        <div class="card-body">
                                            <h5 class="mb-2">
                                                <span class="badge badge-primary">Step 1</span>
                                                Pick a category &amp; download its template
                                            </h5>
                                            <p class="text-muted mb-3">
                                                Choose the category you want to upload. Each template carries the exact
                                                header row the importer expects, example rows filled for that category,
                                                a column-by-column instruction sheet and a <b>Master Reference</b> sheet
                                                listing every valid State, District, City, Area, Vendor, Category,
                                                Illumination, Area Type, Highway and Landmark value in the system.
                                            </p>

                                            {{-- Responsive category grid: wraps evenly, never overflows --}}
                                            <div class="row">
                                                @forelse ($options['categories'] as $category)
                                                    <div class="col-6 col-md-4 col-xl-3 mb-3">
                                                        <div class="border rounded p-3 h-100 text-center d-flex flex-column justify-content-between">
                                                            <div class="mb-3">
                                                                <i class="fa fa-th-large fa-2x text-primary d-block mb-2"></i>
                                                                <b class="d-block">{{ $category->category_name }}</b>
                                                            </div>
                                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                                <a href="{{ route('media.import.template', ['category' => $category->id]) }}"
                                                                    class="btn btn-success" title="Download sample template">
                                                                    <i class="fa fa-file-excel"></i> Template
                                                                </a>
                                                                <button type="button" class="btn btn-primary btn-import-cat"
                                                                    data-category-id="{{ $category->id }}"
                                                                    data-category-name="{{ $category->category_name }}"
                                                                    title="Upload a file for this category">
                                                                    <i class="fa fa-upload"></i> Import
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="col-12">
                                                        <div class="text-muted">No categories found. Please add a category first.</div>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- STEP 2 : UPLOAD --}}
                                <div class="col-12 mb-4">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <h5 class="mb-3">
                                                <span class="badge badge-primary">Step 2</span>
                                                Upload your file
                                            </h5>

                                            <form action="{{ route('media.import.preview') }}" method="POST"
                                                enctype="multipart/form-data" id="importForm">
                                                @csrf

                                                <input type="hidden" name="category_id" id="importCategoryId" value="">

                                                <div id="importCategoryBadge" class="alert alert-info d-flex justify-content-between align-items-center py-2"
                                                    style="display:none;">
                                                    <span>Importing under: <b id="importCategoryName"></b>
                                                        — rows with a blank <b>Category</b> cell will use this category.</span>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        id="clearImportCategory">Change</button>
                                                </div>

                                                <div class="form-group">
                                                    <label><b>Excel / CSV file</b> <span class="text-danger">*</span></label>
                                                    <input type="file" name="file" id="importFile"
                                                        class="form-control" accept=".xlsx,.xls,.csv" required>
                                                    <small class="text-muted">
                                                        Accepted: .xlsx, .xls, .csv &nbsp;|&nbsp; Max size 10MB
                                                        &nbsp;|&nbsp; Max {{ number_format(\App\Http\Services\Superadm\MediaImportExportService::MAX_ROWS) }}
                                                        rows per file
                                                    </small>
                                                </div>

                                                <div class="form-group">
                                                    <label><b>If a Hoarding Code already exists</b></label>
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" id="modeInsert" name="mode" value="insert"
                                                            class="custom-control-input" checked>
                                                        <label class="custom-control-label" for="modeInsert">
                                                            <b>Add new records only</b> — report existing codes as errors
                                                        </label>
                                                    </div>
                                                    <div class="custom-control custom-radio mt-2">
                                                        <input type="radio" id="modeUpsert" name="mode" value="upsert"
                                                            class="custom-control-input">
                                                        <label class="custom-control-label" for="modeUpsert">
                                                            <b>Add new and update existing</b> — rows whose Hoarding Code
                                                            already exists will overwrite that record
                                                        </label>
                                                    </div>
                                                </div>

                                                <button type="submit" class="btn btn-primary" id="importSubmit">
                                                    <i class="fa fa-search"></i> Validate &amp; Preview
                                                </button>
                                                <small class="text-muted d-block mt-2">
                                                    Nothing is saved yet — you will see a preview and an error log
                                                    before anything is published.
                                                </small>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- FIELD REFERENCE --}}
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <h5 class="text-danger mb-3">
                                                Mandatory columns ({{ count($requiredColumns) }})
                                            </h5>
                                            <div class="table-responsive" style="max-height:420px; overflow:auto;">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width:35%">Column</th>
                                                            <th>How to fill</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($requiredColumns as $column)
                                                            <tr>
                                                                <td><b>{{ $column['label'] }}</b></td>
                                                                <td class="text-muted">{{ $column['help'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <h5 class="mb-3">Optional columns ({{ count($optionalColumns) }})</h5>
                                            <div class="table-responsive" style="max-height:420px; overflow:auto;">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width:35%">Column</th>
                                                            <th>How to fill</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($optionalColumns as $column)
                                                            <tr>
                                                                <td>{{ $column['label'] }}</td>
                                                                <td class="text-muted">{{ $column['help'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mb-0">
                                <b>What gets checked before publishing:</b>
                                every mandatory field is present · master names (State → District → City → Area, Vendor,
                                Category, Illumination, Area Type, Highway, Landmarks) exist and match each other ·
                                Width, Height, Price and GPS coordinates are valid numbers in range ·
                                category specific fields (Mall Name, Airport Zone, Transit details …) are filled ·
                                Hoarding Code and Media Code are not repeated in the file and not already taken ·
                                no two records share the same Vendor and GPS position.
                            </div>
                        </div>

                        {{-- ==========================================================
                             EXPORT TAB
                        =========================================================== --}}
                        <div class="tab-pane fade {{ $activeTab === 'export' ? 'show active' : '' }}" id="tab-export"
                            role="tabpanel">

                            {{-- POST, not GET: a large "export selected" list would overflow the URL --}}
                            <form action="{{ route('media.export') }}" method="POST" id="exportForm">
                                @csrf
                                <input type="hidden" name="ids" id="selectedIds">

                                <div class="row">
                                    <div class="col-md-3 form-group">
                                        <label><b>State</b></label>
                                        <select name="state_id" id="f_state" class="form-control">
                                            <option value="">All States</option>
                                            @foreach ($options['states'] as $state)
                                                <option value="{{ $state->id }}"
                                                    {{ ($filters['state_id'] ?? '') == $state->id ? 'selected' : '' }}>
                                                    {{ $state->state_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>District</b></label>
                                        <select name="district_id" id="f_district" class="form-control">
                                            <option value="">All Districts</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>City / Town</b></label>
                                        <select name="city_id" id="f_city" class="form-control">
                                            <option value="">All Cities</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Category (Media Type)</b></label>
                                        <select name="category_id" class="form-control">
                                            <option value="">All Categories</option>
                                            @foreach ($options['categories'] as $category)
                                                <option value="{{ $category->id }}"
                                                    {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->category_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Vendor / Owner</b></label>
                                        <select name="vendor_id" class="form-control">
                                            <option value="">All Vendors</option>
                                            @foreach ($options['vendors'] as $vendor)
                                                <option value="{{ $vendor->id }}"
                                                    {{ ($filters['vendor_id'] ?? '') == $vendor->id ? 'selected' : '' }}>
                                                    {{ $vendor->vendor_name }} ({{ $vendor->vendor_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Illumination</b></label>
                                        <select name="illumination_id" class="form-control">
                                            <option value="">All</option>
                                            @foreach ($options['illuminations'] as $illumination)
                                                <option value="{{ $illumination->id }}"
                                                    {{ ($filters['illumination_id'] ?? '') == $illumination->id ? 'selected' : '' }}>
                                                    {{ $illumination->illumination_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Area Type</b></label>
                                        <select name="areatype_id" class="form-control">
                                            <option value="">All</option>
                                            @foreach ($options['areatypes'] as $areatype)
                                                <option value="{{ $areatype->id }}"
                                                    {{ ($filters['areatype_id'] ?? '') == $areatype->id ? 'selected' : '' }}>
                                                    {{ $areatype->areatype_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Highway</b></label>
                                        <select name="highway_id" class="form-control">
                                            <option value="">All</option>
                                            @foreach ($options['highways'] as $highway)
                                                <option value="{{ $highway->id }}"
                                                    {{ ($filters['highway_id'] ?? '') == $highway->id ? 'selected' : '' }}>
                                                    {{ $highway->highway_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Status</b></label>
                                        <select name="status" class="form-control">
                                            <option value="">All</option>
                                            <option value="1" {{ ($filters['status'] ?? '') === '1' ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="0" {{ ($filters['status'] ?? '') === '0' ? 'selected' : '' }}>
                                                Inactive</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Media Type</b></label>
                                        <input type="text" name="media_type" class="form-control"
                                            placeholder="e.g. Unipole" value="{{ $filters['media_type'] ?? '' }}">
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Search (Code / Title)</b></label>
                                        <input type="text" name="hoarding_code" class="form-control"
                                            placeholder="e.g. HD000007" value="{{ $filters['hoarding_code'] ?? '' }}">
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Price From</b></label>
                                        <input type="number" name="min_price" class="form-control" min="0"
                                            step="any" value="{{ $filters['min_price'] ?? '' }}">
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Price To</b></label>
                                        <input type="number" name="max_price" class="form-control" min="0"
                                            step="any" value="{{ $filters['max_price'] ?? '' }}">
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Created From</b></label>
                                        <input type="date" name="from_date" class="form-control"
                                            value="{{ $filters['from_date'] ?? '' }}">
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>Created To</b></label>
                                        <input type="date" name="to_date" class="form-control"
                                            value="{{ $filters['to_date'] ?? '' }}">
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label><b>File Format</b></label>
                                        <select name="format" class="form-control">
                                            <option value="xlsx">Excel (.xlsx)</option>
                                            <option value="csv">CSV (.csv)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap align-items-center mt-2">
                                    <button type="submit" class="btn btn-success m-1" id="btnExportAll">
                                        <i class="fa fa-download"></i> Export Matching Records
                                    </button>
                                    <button type="button" class="btn btn-primary m-1" id="btnLoadRecords">
                                        <i class="fa fa-list"></i> Load Records To Select
                                    </button>
                                    <button type="button" class="btn btn-dark m-1" id="btnExportSelected" disabled>
                                        <i class="fa fa-check-square"></i> Export Selected (<span
                                            id="selectedCount">0</span>)
                                    </button>
                                    <a href="{{ route('media.import-export', ['tab' => 'export']) }}"
                                        class="btn btn-secondary m-1">Reset Filters</a>
                                </div>

                                <small class="text-muted d-block mt-2">
                                    With no filters applied, <b>Export Matching Records</b> exports the complete media
                                    database. The export includes location, commercial, GPS and media specification
                                    details.
                                </small>
                            </form>

                            {{-- RECORD PICKER --}}
                            <div id="recordPicker" class="mt-4" style="display:none;">
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">Matching Records (<span id="recordTotal">0</span>)</h5>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectPage">
                                            Select all on this page
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearSelection">
                                            Clear selection
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive" style="max-height:460px; overflow:auto;">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:40px;"><input type="checkbox" id="checkAll"></th>
                                                <th>Hoarding Code</th>
                                                <th>Media Title</th>
                                                <th>Category</th>
                                                <th>City</th>
                                                <th>Area</th>
                                                <th>Vendor</th>
                                                <th>Price</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recordBody"></tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted" id="recordPageInfo"></small>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-secondary" id="btnPrevPage">Previous</button>
                                        <button type="button" class="btn btn-sm btn-secondary" id="btnNextPage">Next</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {

            /* ============ CASCADING LOCATION FILTERS ============ */
            const DISTRICTS = @json($options['districts']);
            const CITIES = @json($options['cities']);

            const preset = {
                district: "{{ $filters['district_id'] ?? '' }}",
                city: "{{ $filters['city_id'] ?? '' }}"
            };

            function fillDistricts(stateId, selected) {
                let html = '<option value="">All Districts</option>';
                DISTRICTS.filter(d => !stateId || String(d.state_id) === String(stateId))
                    .forEach(d => {
                        html += `<option value="${d.id}" ${String(d.id) === String(selected) ? 'selected' : ''}>${d.district_name}</option>`;
                    });
                $('#f_district').html(html);
            }

            function fillCities(districtId, selected) {
                let html = '<option value="">All Cities</option>';
                CITIES.filter(c => !districtId || String(c.district_id) === String(districtId))
                    .forEach(c => {
                        html += `<option value="${c.id}" ${String(c.id) === String(selected) ? 'selected' : ''}>${c.city_name}</option>`;
                    });
                $('#f_city').html(html);
            }

            fillDistricts($('#f_state').val(), preset.district);
            fillCities($('#f_district').val(), preset.city);

            $('#f_state').on('change', function () {
                fillDistricts($(this).val(), '');
                fillCities('', '');
                resetPicker();
            });

            $('#f_district').on('change', function () {
                fillCities($(this).val(), '');
                resetPicker();
            });

            $('#exportForm').on('change', 'select, input', function () {
                if (this.id !== 'selectedIds') resetPicker();
            });

            /* ============ IMPORT : PER-CATEGORY IMPORT BUTTON ============ */
            $('.btn-import-cat').on('click', function () {
                const id = $(this).data('category-id');
                const name = $(this).data('category-name');

                $('#importCategoryId').val(id);
                $('#importCategoryName').text(name);
                $('#importCategoryBadge').show();

                // Bring the upload box into view and prompt for the file.
                const target = $('#importForm');
                if (target.length) {
                    $('html, body').animate({ scrollTop: target.offset().top - 90 }, 300);
                }
                $('#importFile').focus();
            });

            $('#clearImportCategory').on('click', function () {
                $('#importCategoryId').val('');
                $('#importCategoryBadge').hide();
            });

            /* ============ IMPORT : GUARD AGAINST DOUBLE SUBMIT ============ */
            $('#importForm').on('submit', function () {
                $('#importSubmit')
                    .prop('disabled', true)
                    .html('<i class="fa fa-spinner fa-spin"></i> Validating…');
            });

            /* ============ EXPORT : RECORD PICKER ============ */
            const selected = new Set();
            let currentPage = 1;
            let lastPage = 1;

            function resetPicker() {
                selected.clear();
                currentPage = 1;
                $('#recordPicker').hide();
                $('#recordBody').empty();
                syncSelection();
            }

            function syncSelection() {
                $('#selectedCount').text(selected.size);
                $('#btnExportSelected').prop('disabled', selected.size === 0);
                $('#selectedIds').val(Array.from(selected).join(','));
            }

            function loadRecords(page) {
                const params = $('#exportForm').serializeArray()
                    .filter(f => !['ids', 'format', '_token'].includes(f.name) && f.value !== '');
                params.push({ name: 'page', value: page });

                $('#recordBody').html('<tr><td colspan="9" class="text-center">Loading…</td></tr>');
                $('#recordPicker').show();

                $.get("{{ route('media.export.records') }}", $.param(params), function (res) {
                    if (!res.status) {
                        $('#recordBody').html('<tr><td colspan="9" class="text-center text-danger">Could not load records</td></tr>');
                        return;
                    }

                    currentPage = res.current_page;
                    lastPage = res.last_page;

                    $('#recordTotal').text(res.total);
                    $('#recordPageInfo').text(`Page ${res.current_page} of ${res.last_page} — ${res.total} record(s) match`);

                    if (!res.data.length) {
                        $('#recordBody').html('<tr><td colspan="9" class="text-center">No media records match these filters</td></tr>');
                        return;
                    }

                    let html = '';
                    res.data.forEach(row => {
                        const checked = selected.has(String(row.id)) ? 'checked' : '';
                        html += `<tr>
                            <td><input type="checkbox" class="row-check" value="${row.id}" ${checked}></td>
                            <td>${row.hoarding_code}</td>
                            <td>${row.media_title}</td>
                            <td>${row.category_name}</td>
                            <td>${row.city_name}</td>
                            <td>${row.area_name}</td>
                            <td>${row.vendor_name}</td>
                            <td>₹ ${Number(row.price).toLocaleString('en-IN')}</td>
                            <td>${row.status}</td>
                        </tr>`;
                    });

                    $('#recordBody').html(html);
                    $('#checkAll').prop('checked', false);
                }).fail(function () {
                    $('#recordBody').html('<tr><td colspan="9" class="text-center text-danger">Could not load records</td></tr>');
                });
            }

            $('#btnLoadRecords').on('click', () => loadRecords(1));
            $('#btnPrevPage').on('click', () => currentPage > 1 && loadRecords(currentPage - 1));
            $('#btnNextPage').on('click', () => currentPage < lastPage && loadRecords(currentPage + 1));

            $(document).on('change', '.row-check', function () {
                this.checked ? selected.add(this.value) : selected.delete(this.value);
                syncSelection();
            });

            $('#checkAll, #btnSelectPage').on('click', function () {
                const check = this.id === 'btnSelectPage' ? true : $('#checkAll').is(':checked');
                $('.row-check').each(function () {
                    this.checked = check;
                    check ? selected.add(this.value) : selected.delete(this.value);
                });
                if (this.id === 'btnSelectPage') $('#checkAll').prop('checked', true);
                syncSelection();
            });

            $('#btnClearSelection').on('click', function () {
                selected.clear();
                $('.row-check').prop('checked', false);
                $('#checkAll').prop('checked', false);
                syncSelection();
            });

            // Selected export sends ids; the plain export must never carry them.
            $('#btnExportSelected').on('click', function () {
                syncSelection();
                $('#exportForm').submit();
            });

            $('#btnExportAll').on('click', function () {
                $('#selectedIds').val('');
            });
        });
    </script>
@endsection
