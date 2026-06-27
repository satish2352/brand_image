<style>
    .bg-light {
        background-color: rgb(233 225 225) !important;
    }

    .result-badge {
        background: #fff9d9;
        border-left: 5px solid #ffb100;
        padding: 0px 15px;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 15px;
    }

    .result-badge .icon {
        font-size: 18px;
    }

    .result-badge .count {
        color: #007bff;
        font-weight: 700;
    }

    .result-badge .label {
        color: #333;
    }

    .result-badge.no-result {
        border-left-color: #dc3545;
        background: #ffe6e8;
    }

    .result-badge.no-result .count {
        color: #dc3545;
    }


    /* Uniform height for all inputs & selects */
    .media-search-card .form-select,
    .media-search-card .form-control {
        height: 44px;
    }

    /* ===== Select2 styled to match the form (light-orange hover, no blue) =====
       The native <select> blue highlight cannot be restyled in Chrome, so these
       selects are upgraded to Select2 which is fully styleable. */
    .media-search-card .select2-container {
        width: 100% !important;
    }

    .media-search-card .select2-container--default .select2-selection--single {
        height: 44px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        display: flex;
        align-items: center;
        padding: 0 10px;
        background: #fff;
    }

    .media-search-card .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.4;
        color: #333;
        padding: 0;
    }

    .media-search-card .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6c757d;
    }

    .media-search-card .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px;
    }

    .media-search-card .select2-container--default.select2-container--focus .select2-selection--single,
    .media-search-card .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #f28123;
        outline: none;
    }

    /* dropdown options — hover/active in LIGHT ORANGE (not blue) */
    .bi-orange-dropdown .select2-results__option--highlighted[aria-selected] {
        background-color: #fde3cf !important;
        color: #b95e16 !important;
    }

    /* the already-selected option */
    .bi-orange-dropdown .select2-results__option[aria-selected=true] {
        background-color: #fff3e6 !important;
        color: #b95e16 !important;
        font-weight: 600;
    }

    /* Keep the open dropdown ABOVE the fixed site header (z-index 9999) and any
       floating buttons, so the options are actually visible when clicked. */
    .select2-container--open {
        z-index: 10050 !important;
    }

    .select2-container--open .select2-dropdown,
    .bi-orange-dropdown.select2-dropdown {
        z-index: 10050 !important;
    }

    /* ensure every option renders with readable colours and full height
       (guards against global list/anchor resets hiding them) */
    .bi-orange-dropdown .select2-results__options {
        max-height: 280px;
        overflow-y: auto;
    }

    .bi-orange-dropdown .select2-results__option {
        display: list-item;
        list-style: none;
        color: #333;
        background-color: #fff;
        padding: 8px 12px;
    }

    /* Fix date input height */
    .media-search-card input[type="date"] {
        height: 44px;
    }

    /* SLIDER WRAP */
    .range-slider-container {
        position: relative;
        width: 100%;
        padding-top: 15px;
        padding-bottom: 30px;
        margin-top: 10px;
    }

    .range-slider-container input[type=range] {
        -webkit-appearance: none;
        width: 100%;
        background: transparent;
        position: absolute;
        top: 10px !important;
        /* keep thumb centered */
        pointer-events: none;
    }

    .range-slider-container input[type=range]::-webkit-slider-runnable-track {
        height: 6px;
        background: #d7d7d7;
        border-radius: 3px;
    }

    .range-slider-container input[type=range]::-webkit-slider-thumb {
        -webkit-appearance: none;
        pointer-events: auto;
        width: 18px;
        height: 18px;
        background: #f28123;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.3);
        margin-top: -6px;
        /* ⭐ PERFECT vertical centering */
        z-index: 5;
        position: relative;
    }

    .range-slider-fill {
        position: absolute;
        height: 6px;
        background: #f28123;
        top: 10px;
        border-radius: 3px;
        z-index: 2;
    }

    /* FIX MEDIA SIZE RANGE */
    .range-slider-container {
        position: relative;
        width: 100%;
        height: 30px;
    }

    .range-slider-container input[type=range] {
        position: absolute;
        width: 100%;
        height: 6px;
        top: 10px;
        background: none;
        pointer-events: none;
    }

    .range-slider-container input[type=range]::-webkit-slider-runnable-track {
        height: 6px;
        background: #d7d7d7;
        border-radius: 5px;
    }

    .range-slider-container input[type=range]::-webkit-slider-thumb {
        pointer-events: auto;
        position: relative;
        z-index: 3;
    }

    .range-slider-fill {
        position: absolute;
        height: 6px;
        background: #f28123;
        top: 10px;
        border-radius: 5px;
        z-index: 2;
    }
</style>
<div class="container-fluid mt-5 mb-5">
    <h3 class="text-center orange-text">Discover Media Spaces Near You</h3>
    <div class="media-search-card">

        <form method="POST" id="searchForm" action="{{ route('website.search') }}">
            @csrf
            {{-- <input type="hidden" name="clear" id="clearFlag"> --}}

            <div class="row g-3 justify-content-center justify-content-lg-between">

                <!-- Category -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">Select Category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- State -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">State</label>
                    <select name="state_id" id="state_id" class="form-select">
                        <option value="">Select State</option>
                        @foreach ($states as $state)
                            {{-- <option value="{{ $state->location_id }}"
                        {{ ($filters['state_id'] ?? '') == $state->location_id ? 'selected' : '' }}>
                        {{ $state->name }} --}}
                            <option value="{{ $state->id }}"
                                {{ ($filters['state_id'] ?? '') == $state->id ? 'selected' : '' }}>
                                {{ $state->state_name }}

                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- District -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">District</label>
                    <select name="district_id" id="district_id" class="form-select">
                        <option value="">Select District</option>
                    </select>
                </div>

                <!-- City -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Town</label>
                    <select name="city_id" id="city_id" class="form-select">
                        <option value="">Select Town</option>
                    </select>
                </div>

                <!-- Area -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Area</label>
                    <select name="area_id" id="area_id" class="form-select">
                        <option value="">Select Area</option>
                    </select>
                </div>

                <!-- Radius -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="radius_wrapper">
                    <label class="form-label">Radius</label>
                    <select name="radius_id" class="form-select" id="radius_id">
                        <option value="">Radius</option>
                        @foreach ($radiusList as $r)
                            <option value="{{ $r->radius }}"
                                {{ (string) ($filters['radius_id'] ?? '') === (string) $r->radius ? 'selected' : '' }}>
                                {{ $r->radius }} KM
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 col-sm-6" id="radius_wrapper">
                    <label class="form-label">Area Type</label>
                    <select name="areatype_id" class="form-select" id="areatype_id">
                        <option value="">Select Type</option>

                        @foreach ($areaTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ ($filters['areatype_id'] ?? '') == $type->id ? 'selected' : '' }}>
                                {{ $type->areatype_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- <div class="col-lg-2 col-md-4 col-sm-6" id="area_type_wrapper">
                    <label class="form-label">Area Type</label>
                    <select name="area_type" class="form-select" id="area_type">
                        <option value="">Select Type</option>
                        <option value="rural" {{ ($filters['area_type'] ?? '') == 'rural' ? 'selected' : '' }}>Rural
                        </option>
                        <option value="urban" {{ ($filters['area_type'] ?? '') == 'urban' ? 'selected' : '' }}>Urban
                        </option>
                    </select>
                </div> --}}

                <!-- Highway -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="highway_wrapper">
                    <label class="form-label">Highway</label>
                    <select name="highway_id" id="highway_id" class="form-select">
                        <option value="">Select Highway</option>
                        @foreach ($highways as $hw)
                            <option value="{{ $hw->id }}"
                                {{ ($filters['highway_id'] ?? '') == $hw->id ? 'selected' : '' }}>
                                {{ $hw->highway_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Landmarks (multi-select checkbox dropdown) -->
                @php $selectedLandmarks = (array) ($filters['landmark_ids'] ?? []); @endphp
                <div class="col-lg-2 col-md-4 col-sm-6" id="landmark_wrapper">
                    <label class="form-label">Landmarks</label>
                    <div class="landmark-dropdown" id="landmarkDropdown">
                        <button type="button" class="form-select text-start landmark-toggle" id="landmarkToggle">
                            <span class="landmark-toggle-text">Select Landmarks</span>
                        </button>
                        <div class="landmark-menu" id="landmarkMenu">
                            @forelse ($landmarks as $lm)
                                <label class="landmark-option">
                                    <input type="checkbox" name="landmark_ids[]" value="{{ $lm->id }}"
                                        {{ in_array($lm->id, $selectedLandmarks) ? 'checked' : '' }}>
                                    <span>{{ $lm->landmark_name }}</span>
                                </label>
                            @empty
                                <div class="landmark-empty">No landmarks available</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- From Date -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="date_wrapper">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" id="from_date" class="form-control"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>

                <!-- To Date -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="to_date_wrapper">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" id="to_date" class="form-control"
                        value="{{ $filters['to_date'] ?? '' }}">
                </div>

                <!-- Available Days -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="days_wrapper">
                    <label class="form-label">Available Days</label>
                    <select name="available_days" id="available_days" class="form-select">
                        <option value="">Select Days</option>

                        <option value="0" {{ ($filters['available_days'] ?? '') == '0' ? 'selected' : '' }}>
                            Instantly Available
                        </option>

                        <option value="7" {{ ($filters['available_days'] ?? '') == '7' ? 'selected' : '' }}>
                            Available After 7 Days
                        </option>

                        <option value="15" {{ ($filters['available_days'] ?? '') == '15' ? 'selected' : '' }}>
                            Available After 15 Days
                        </option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">

                    <label class="form-label">Media Size (sq.ft)</label>

                    <div class="d-flex justify-content-between">
                        <span id="minAreaLabel">
                            {{ number_format($filters['min_area'] ?? $areaRange->min_area) }} sqft
                        </span>

                        <span id="maxAreaLabel">
                            {{ number_format($filters['max_area'] ?? $areaRange->max_area) }} sqft
                        </span>
                    </div>

                    <div class="range-slider-container">

                        <input type="hidden" name="min_area" id="min_area" value="{{ $filters['min_area'] ?? '' }}">

                        <input type="hidden" name="max_area" id="max_area" value="{{ $filters['max_area'] ?? '' }}">

                        <div class="range-slider-fill" id="areaRangeFill"></div>

                        <input type="range" id="minAreaRange" min="{{ $areaRange->min_area }}"
                            max="{{ $areaRange->max_area }}" step="1"
                            value="{{ $filters['min_area'] ?? $areaRange->min_area }}">

                        <input type="range" id="maxAreaRange" min="{{ $areaRange->min_area }}"
                            max="{{ $areaRange->max_area }}" step="1"
                            value="{{ $filters['max_area'] ?? $areaRange->max_area }}">

                    </div>

                </div>
                {{-- <div class="col-lg-2 col-md-4 col-sm-6" id="size_wrapper">
                    <label class="form-label">Media Size</label>

                    <select name="size_id" class="form-select">
                        <option value="">Select Media Size</option>

                        @foreach ($sizes as $id => $size)
                            <option value="{{ $size }}"
                                {{ ($filters['size_id'] ?? '') == $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </div> --}}

                <div class="col-lg-2 col-md-4 col-sm-6" id="days_wrapper">
                    <label class="form-label">Budget</label>

                    <!-- Budget Slider -->
                    <div class="d-flex justify-content-between">
                        <span id="minRangeLabel" style="font-weight:600">
                            ₹{{ number_format($filters['min_price'] ?? 0) }}
                        </span>

                        <span id="maxRangeLabel" style="font-weight:600">
                            ₹{{ number_format($filters['max_price'] ?? 1000000) }}
                        </span>
                    </div>
                    <div class="range-slider-container">
                        <input type="hidden" name="min_price" id="min_price"
                            value="{{ $filters['min_price'] ?? 0 }}">
                        <input type="hidden" name="max_price" id="max_price"
                            value="{{ $filters['max_price'] ?? 1000000 }}">

                        <div class="range-slider-fill" id="rangeFill"></div>

                        <input type="range" id="minRange" min="0" max="1000000" step="1000"
                            value="{{ $filters['min_price'] ?? 0 }}">
                        <input type="range" id="maxRange" min="0" max="1000000" step="1000"
                            value="{{ $filters['max_price'] ?? 1000000 }}">


                    </div>

                </div>

                <div class="row " style="padding-top:15px">
                    <!-- Buttons -->
                    <div class="col-lg-2 col-md-4 col-sm-12 d-grid mt-md-auto">
                        <button type="button" class="btn btn-search"
                            onclick="document.getElementById('searchForm').submit();">
                            Search Media
                        </button>
                    </div>

                    <div class="col-lg-2 col-md-4 col-sm-12 d-grid mt-md-auto mt-3 ">
                        <button type="button" class="btn btn-clear" id="clearFilters">
                            Clear Filters
                        </button>
                    </div>
                    @if (($filters['category_id'] ?? '') != '')
                        @php $catName = $mediaList->first()->category_name ?? ''; @endphp

                        <div class="col-lg-2 col-md-8 col-sm-12 d-flex align-items-center mt-3 ">
                            @if ($mediaList->total() > 0)
                                <div class="result-badge">
                                    <span class="icon">📍</span>
                                    <span class="count">{{ $mediaList->total() }} Results</span>
                                </div>
                            @else
                                <div class="result-badge no-result">
                                    <span class="icon">❌</span>
                                    <span class="count">No Results</span>
                                    {{-- <span class="label">for {{ $catName }}</span> --}}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>


            </div>
        </form>

    </div>
</div>

{{-- jQuery is already loaded by the layout <head>. Native <select> dropdowns are
     used here for reliability (Select2 was breaking the cascading filters). --}}
<script>
    const selectedState = "{{ $filters['state_id'] ?? '' }}";
    const selectedDistrict = "{{ $filters['district_id'] ?? '' }}";
    const selectedCity = "{{ $filters['city_id'] ?? '' }}";
    const selectedArea = "{{ $filters['area_id'] ?? '' }}";
</script>
<script>
    $(document).ready(function() {

        let minSlider = $("#minAreaRange");
        let maxSlider = $("#maxAreaRange");
        let fill = $("#areaRangeFill");

        let minLabel = $("#minAreaLabel");
        let maxLabel = $("#maxAreaLabel");

        let minLimit = Number(minSlider.attr("min"));
        let maxLimit = Number(maxSlider.attr("max"));

        function updateAreaSlider() {

            let minVal = parseInt(minSlider.val());
            let maxVal = parseInt(maxSlider.val());

            if (minVal > maxVal - 1) {
                minVal = maxVal - 1;
                minSlider.val(minVal);
            }

            // correct percent calculation
            let minPercent = ((minVal - minLimit) / (maxLimit - minLimit)) * 100;
            let maxPercent = ((maxVal - minLimit) / (maxLimit - minLimit)) * 100;

            fill.css({
                left: minPercent + "%",
                width: (maxPercent - minPercent) + "%"
            });

            minLabel.text(minVal + " sqft");
            maxLabel.text(maxVal + " sqft");

            // Only set hidden fields when user actually moves slider
            if (window._areaSliderTouched) {
                $("#min_area").val(minVal);
                $("#max_area").val(maxVal);
            }
        }

        updateAreaSlider();

        minSlider.on("input change", function() {
            window._areaSliderTouched = true;
            updateAreaSlider();
        });
        maxSlider.on("input change", function() {
            window._areaSliderTouched = true;
            updateAreaSlider();
        });

    });
</script>
<script>
    $(document).ready(function() {
        let today = new Date().toISOString().split('T')[0];
        $('#from_date').attr('min', today);

        // Optional: also restrict "To Date" not to be before From Date
        $('#from_date').on('change', function() {
            $('#to_date').attr('min', $(this).val());
        });
    });
</script>
<script>
    // ===============================
    // GLOBAL FUNCTION (IMPORTANT)
    // ===============================
    function toggleRadius() {

        const allowedCategories = [1, 2];

        let categoryId = parseInt($('select[name="category_id"]').val());
        let hasCity = $('#city_id').val();
        let hasArea = $('#area_id').val();

        if (
            !allowedCategories.includes(categoryId) ||
            !hasCity ||
            hasArea
        ) {
            $('#radius_id')
                // .val('')
                .prop('disabled', true)
                .addClass('bg-light')
                .trigger('change.select2');
        } else {
            $('#radius_id')
                .prop('disabled', false)
                .removeClass('bg-light')
                .trigger('change.select2');
        }
    }
</script>
<script>
    $(document).ready(function() {

        const csrf = "{{ csrf_token() }}";

        // After replacing a dependent select's <option>s, Select2 must be told to
        // re-read them — otherwise the dropdown shows "No results found". Destroy +
        // re-init is the most reliable refresh. Falls back to nothing for native.
        function refreshSelect2(sel) {
            const $sel = $(sel);
            if ($sel.hasClass('select2-hidden-accessible')) {
                $sel.select2('destroy');
                $sel.select2({
                    width: '100%',
                    minimumResultsForSearch: 15,
                    dropdownCssClass: 'bi-orange-dropdown',
                    placeholder: ($sel.find('option[value=""]').first().text() || 'Select').trim()
                });
            }
        }

        function loadDistricts(stateId, selected = '') {

            if (!stateId) return;

            $.post("{{ route('ajax.districts') }}", {
                _token: csrf,
                state_id: stateId
            }, function(data) {

                let html = '<option value="">Select District</option>';

                data.forEach(d => {
                    html += `<option value="${d.id}" ${d.id == selected ? 'selected' : ''}>
                            ${d.district_name}
                         </option>`;
                });

                $('#district_id').html(html);
            });
        }

        function loadCities(districtId, selected = '') {

            if (!districtId) return;

            $.post("{{ route('ajax.cities') }}", {
                _token: csrf,
                district_id: districtId
            }, function(data) {

                let html = '<option value="">Select Town</option>';

                data.forEach(c => {
                    html += `<option value="${c.id}" ${c.id == selected ? 'selected' : ''}>
                            ${c.city_name}
                         </option>`;
                });

                $('#city_id').html(html);
                refreshSelect2('#city_id');

                toggleRadius(); // ⭐ IMPORTANT
            });
        }

        function loadAreas(cityId, selected = '') {

            if (!cityId) return;

            $.post("{{ route('ajax.areas') }}", {
                _token: csrf,
                city_id: cityId
            }, function(data) {

                let html = '<option value="">Select Area</option>';

                data.forEach(a => {
                    html += `<option value="${a.id}" ${a.id == selected ? 'selected' : ''}>
                            ${a.area_name}
                         </option>`;
                });

                $('#area_id').html(html);
                refreshSelect2('#area_id');

                toggleRadius(); // ⭐ IMPORTANT
            });
        }

        // ================= EVENTS (delegated → fire reliably with Select2) ====

        $(document).on('change', '#state_id', function() {

            loadDistricts(this.value);

            $('#city_id').html('<option value="">Select Town</option>');
            $('#area_id').html('<option value="">Select Area</option>');
            refreshSelect2('#city_id');
            refreshSelect2('#area_id');

            toggleRadius();
        });

        $(document).on('change', '#district_id', function() {

            loadCities(this.value);

            $('#area_id').html('<option value="">Select Area</option>');
            refreshSelect2('#area_id');
        });

        $(document).on('change', '#city_id', function() {

            loadAreas(this.value);
            toggleRadius();
        });

        $(document).on('change', '#area_id', toggleRadius);

        // INITIAL LOAD
        if (selectedState) loadDistricts(selectedState, selectedDistrict);
        if (selectedDistrict) loadCities(selectedDistrict, selectedCity);
        if (selectedCity) loadAreas(selectedCity, selectedArea);

        toggleRadius();
    });
</script>
<script>
    document.getElementById('clearFilters').addEventListener('click', function() {

        // Reset form fields
        document.getElementById('searchForm').reset();

        // Reset dependent dropdowns
        $('#district_id').html('<option value="">Select District</option>').trigger('change.select2');
        $('#city_id').html('<option value="">Select Town</option>').trigger('change.select2');
        $('#area_id').html('<option value="">Select Area</option>').trigger('change.select2');

        // Reset slider
        $("#minRange").val(0);
        $("#maxRange").val(1000000);
        $("#min_price").val(0);
        $("#max_price").val(1000000);
        $("#minRangeLabel").text("₹0");
        $("#maxRangeLabel").text("₹10,00,000");

        // Reset slider fill
        $("#rangeFill").css({
            left: "0%",
            width: "100%"
        });

        // Optional: reload default media via form submit
        // (keeps layout stable)
        let form = document.getElementById('searchForm');

        let input = document.createElement("input");
        input.type = "hidden";
        input.name = "clear";
        input.value = "1";

        form.appendChild(input);
        form.submit();

    });
</script>
<script>
    $(document).ready(function() {

        function toggleDateFields(categoryId) {

            // ✅ Only category ID = 1 allows date selection
            if (categoryId == 1) {
                $('#from_date, #to_date, #available_days, #area_type')
                    .prop('disabled', false)
                    .removeClass('bg-light')
                    .trigger('change.select2');
            } else {
                $('#from_date, #to_date, #available_days, #area_type')
                    .prop('disabled', true)
                    .addClass('bg-light')
                    .val('') // clear values
                    .trigger('change.select2');
            }
        }

        // 🔥 On category change
        $('select[name="category_id"]').on('change', function() {
            toggleDateFields($(this).val());
        });

        // 🔥 On page load (important for search page reload)
        toggleDateFields($('select[name="category_id"]').val());

    });
</script>

<script>
    $(document).ready(function() {

        function toggleCategoryFilters() {
            let categoryId = $('select[name="category_id"]').val();

            // ❌ Hide everything by default
            $('#radius_wrapper, #area_type_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper, #highway_wrapper, #landmark_wrapper')
                .hide()
                .find('select, input')
                .prop('disabled', true)
                .trigger('change.select2');

            // 🟢 Category 1 → show ALL
            if (categoryId == 1) {
                $('#radius_wrapper, #area_type_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper, #highway_wrapper, #landmark_wrapper')
                    .show()
                    .find('select, input')
                    .prop('disabled', false)
                    .trigger('change.select2');
            }

            // 🟡 Category 2 → show ONLY radius
            else if (categoryId == 2) {
                $('#radius_wrapper')
                    .show()
                    .find('select')
                    .prop('disabled', false)
                    .trigger('change.select2');
            }
        }

        // 🔥 On category change
        $('select[name="category_id"]').on('change', toggleCategoryFilters);

        // 🔥 On page load
        toggleCategoryFilters();
    });
</script>

<script>
    $(document).ready(function() {

        const allowedCategories = [1, 2];
        // 🔥 EVENTS (THIS IS IMPORTANT)
        $('select[name="category_id"]').on('change', toggleRadius);
        $('#city_id').on('change', toggleRadius);
        $('#area_id').on('change', toggleRadius);

        // 🔥 Page load
        toggleRadius();
    });
</script>

<script>
    $(document).ready(function() {

        let minSlider = $("#minRange");
        let maxSlider = $("#maxRange");
        let fill = $("#rangeFill");
        let minLabel = $("#minRangeLabel");
        let maxLabel = $("#maxRangeLabel");
        let maxValue = parseInt(maxSlider.attr("max"));

        function updateSlider() {
            let minVal = parseInt(minSlider.val());
            let maxVal = parseInt(maxSlider.val());

            if (minVal > maxVal - 1000) {
                minVal = maxVal - 1000;
                minSlider.val(minVal);
            }

            let minPercent = (minVal / maxValue) * 100;
            let maxPercent = (maxVal / maxValue) * 100;

            fill.css({
                left: minPercent + "%",
                width: (maxPercent - minPercent) + "%"
            });

            minLabel.text("₹" + minVal.toLocaleString('en-IN'));
            maxLabel.text("₹" + maxVal.toLocaleString('en-IN'));

            $("#min_price").val(minVal);
            $("#max_price").val(maxVal);
        }

        // ✅ FIXED Blade values
        let savedMin = {{ $filters['min_price'] ?? 0 }};
        let savedMax = {{ $filters['max_price'] ?? 1000000 }};

        minSlider.val(savedMin);
        maxSlider.val(savedMax);

        updateSlider();

        minSlider.on("input change", updateSlider);
        maxSlider.on("input change", updateSlider);

    });
</script>

{{-- ===== Custom checkbox dropdown for the Landmarks multi-select filter ===== --}}
<style>
    .landmark-dropdown {
        position: relative;
    }
    .landmark-toggle {
        width: 100%;
        /* keep the same dropdown caret as the native .form-select dropdowns
           (a plain `background:#fff` shorthand had wiped out the caret image) */
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
        padding-right: 2.25rem;
        cursor: pointer;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .landmark-menu {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 1000;
        max-height: 220px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        padding: 6px;
    }
    .landmark-dropdown.open .landmark-menu {
        display: block;
    }
    .landmark-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        margin: 0;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 400;
    }
    .landmark-option:hover {
        background: #f0f0f0;
    }
    .landmark-option input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #f28123;
        cursor: pointer;
    }
    .landmark-empty {
        padding: 8px 10px;
        color: #888;
        font-size: 14px;
    }
</style>
<script>
    $(function () {
        const $dropdown = $('#landmarkDropdown');
        const $toggle = $('#landmarkToggle');
        const $text = $toggle.find('.landmark-toggle-text');

        function updateLabel() {
            const labels = $dropdown.find('input[type="checkbox"]:checked')
                .map(function () {
                    return $(this).siblings('span').text().trim();
                }).get();

            if (labels.length === 0) {
                $text.text('Select Landmarks');
            } else if (labels.length <= 2) {
                $text.text(labels.join(', '));
            } else {
                $text.text(labels.length + ' selected');
            }
        }

        $toggle.on('click', function (e) {
            e.stopPropagation();
            $dropdown.toggleClass('open');
        });

        // keep menu open while ticking checkboxes
        $('#landmarkMenu').on('click', function (e) {
            e.stopPropagation();
        });

        $dropdown.on('change', 'input[type="checkbox"]', updateLabel);

        // close when clicking outside
        $(document).on('click', function () {
            $dropdown.removeClass('open');
        });

        updateLabel();
    });
</script>

<script>
    // ===== ROBUST category-dependent filter visibility =====
    // Uses a DELEGATED change handler on document so it always fires when the
    // category changes (Select2 dispatches a native change event), regardless of
    // script/binding order or an error in any other ready handler. This is the
    // authoritative toggle; the earlier inline one is now redundant but harmless.
    jQuery(function ($) {
        function applyCategoryFilters() {
            var cat = String($('select[name="category_id"]').val() || '').trim();
            var $deps = $('#radius_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper, #highway_wrapper, #landmark_wrapper');

            // hide + disable everything first
            $deps.hide().find('select, input').prop('disabled', true).trigger('change.select2');

            if (cat === '1') {
                // Hoardings/Billboards → show ALL dependent filters
                $deps.show().find('select, input').prop('disabled', false).trigger('change.select2');
            } else if (cat === '2') {
                // Digital Wall Painting → show only Radius
                $('#radius_wrapper').show().find('select, input').prop('disabled', false).trigger('change.select2');
            }
        }

        // Fire whenever the category changes (delegated → survives Select2 wrapping)
        $(document).on('change', 'select[name="category_id"]', applyCategoryFilters);

        // Run once after Select2 has initialised so the initial state is correct
        setTimeout(applyCategoryFilters, 350);
    });
</script>
