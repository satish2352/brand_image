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

    /* .range-slider-container {
        position: relative;
        height: 40px;
    }

    .range-slider-container input[type=range] {
        position: absolute;
        width: 100%;
        pointer-events: none;
        -webkit-appearance: none;
        background: none;
    }

    .range-slider-container input[type=range]::-webkit-slider-thumb {
        pointer-events: auto;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #f28123;
        cursor: pointer;
        -webkit-appearance: none;
    } */
    /* Uniform height for all inputs & selects */
    .media-search-card .form-select,
    .media-search-card .form-control {
        height: 44px;
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
</style>
<div class="container-fluid mt-5 mb-5">
    <h3 class="text-center orange-text">Discover Media Spaces Near You</h3>
    <div class="media-search-card">

        <form method="POST" id="searchForm" action="{{ route('website.search') }}">
            @csrf
            <input type="hidden" name="clear" id="clearFlag">

            <div class="row g-3 justify-content-between">

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

                <!-- Area Type -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="area_type_wrapper">
                    <label class="form-label">Area Type</label>
                    <select name="area_type" class="form-select" id="area_type">
                        <option value="">Select Type</option>
                        <option value="rural" {{ ($filters['area_type'] ?? '') == 'rural' ? 'selected' : '' }}>Rural
                        </option>
                        <option value="urban" {{ ($filters['area_type'] ?? '') == 'urban' ? 'selected' : '' }}>Urban
                        </option>
                    </select>
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
                        <option value="7" {{ ($filters['available_days'] ?? '') == '7' ? 'selected' : '' }}>After
                            7 Days</option>
                        <option value="15" {{ ($filters['available_days'] ?? '') == '15' ? 'selected' : '' }}>After
                            15 Days</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6" id="days_wrapper">

                    <!-- Budget Slider -->
                    <div class="d-flex justify-content-between">
                        <span id="minRangeLabel" style="font-weight:600">
                            ₹{{ number_format($filters['min_price'] ?? 0) }}
                        </span>

                        <span id="maxRangeLabel" style="font-weight:600">
                            ₹{{ number_format($filters['max_price'] ?? 200000) }}
                        </span>
                    </div>
                    <div class="range-slider-container">
                        <input type="hidden" name="min_price" id="min_price" value="{{ $filters['min_price'] ?? 0 }}">
                        <input type="hidden" name="max_price" id="max_price"
                            value="{{ $filters['max_price'] ?? 200000 }}">

                        <div class="range-slider-fill" id="rangeFill"></div>

                        <input type="range" id="minRange" min="0" max="200000" step="1000"
                            value="{{ $filters['min_price'] ?? 0 }}">
                        <input type="range" id="maxRange" min="0" max="200000" step="1000"
                            value="{{ $filters['max_price'] ?? 200000 }}">


                    </div>

                </div>

                <div class="row " style="padding-top:15px">
                    <!-- Buttons -->
                    <div class="col-lg-2 col-md-6 col-sm-12 d-grid mt-md-auto">
                        <button type="button" class="btn btn-search"
                            onclick="document.getElementById('searchForm').submit();">
                            Search Media
                        </button>
                    </div>

                    <div class="col-lg-2 col-md-6 col-sm-12 d-grid mt-md-auto mt-3 ">
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
                                    {{-- <span class="label">for {{ $catName }}</span> --}}
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const selectedState = "{{ $filters['state_id'] ?? '' }}";
    const selectedDistrict = "{{ $filters['district_id'] ?? '' }}";
    const selectedCity = "{{ $filters['city_id'] ?? '' }}";
    const selectedArea = "{{ $filters['area_id'] ?? '' }}";
</script>

<script>
    $(document).ready(function() {
        const csrf = "{{ csrf_token() }}";

        // Load Districts
        function loadDistricts(stateId, selected = '') {
            if (!stateId) return;
            $.post("{{ route('ajax.districts') }}", {
                _token: csrf,
                state_id: stateId
            }, function(data) {
                let html = '<option value="">Select District</option>';
                data.forEach(d => {
                    html +=
                        `<option value="${d.id}" ${d.id == selected ? 'selected' : ''}>${d.district_name}</option>`;
                });
                $('#district_id').html(html);
            });
        }

        // Load Cities
        function loadCities(districtId, selected = '') {
            if (!districtId) return;
            $.post("{{ route('ajax.cities') }}", {
                _token: csrf,
                district_id: districtId
            }, function(data) {
                let html = '<option value="">Select City</option>';
                data.forEach(c => {
                    html +=
                        `<option value="${c.id}" ${c.id == selected ? 'selected' : ''}>${c.city_name}</option>`;
                });
                $('#city_id').html(html);
            });
        }

        // Load Areas
        function loadAreas(cityId, selected = '') {
            if (!cityId) return;
            $.post("{{ route('ajax.areas') }}", {
                _token: csrf,
                city_id: cityId
            }, function(data) {
                let html = '<option value="">Select Area</option>';
                data.forEach(a => {
                    html +=
                        `<option value="${a.id}" ${a.id == selected ? 'selected' : ''}>${a.area_name}</option>`;
                });
                $('#area_id').html(html);
            });
        }

        // Change Events
        $('#state_id').on('change', function() {
            loadDistricts(this.value);
            $('#city_id').html('<option value="">Select City</option>');
            $('#area_id').html('<option value="">Select Area</option>');
        });

        $('#district_id').on('change', function() {
            loadCities(this.value);
            $('#area_id').html('<option value="">Select Area</option>');
        });

        $('#city_id').on('change', function() {
            loadAreas(this.value);
        });

        // Initial selection
        if (selectedState) loadDistricts(selectedState, selectedDistrict);
        if (selectedDistrict) loadCities(selectedDistrict, selectedCity);
        if (selectedCity) loadAreas(selectedCity, selectedArea);

    });
</script>

<script>
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('clearFlag').value = '1';
        this.closest('form').submit();
    });
</script>

<script>
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('clearFlag').value = '1';
        this.closest('form').submit();
    });
</script>

<script>
    $(document).ready(function() {

        function toggleDateFields(categoryId) {

            // ✅ Only category ID = 1 allows date selection
            if (categoryId == 1) {
                $('#from_date, #to_date, #available_days, #area_type')
                    .prop('disabled', false)
                    .removeClass('bg-light');
            } else {
                $('#from_date, #to_date, #available_days, #area_type')
                    .prop('disabled', true)
                    .addClass('bg-light')
                    .val(''); // clear values
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

        const allowedCategories = [1, 2];

        function toggleRadius() {
            let categoryId = parseInt($('select[name="category_id"]').val());
            let hasCity = $('#city_id').val();

            // No city, OR category not allowed = disable
            if (!allowedCategories.includes(categoryId) || !hasCity) {
                $('#radius_id')
                    .val('')
                    .prop('disabled', true)
                    .addClass('bg-light');
            } else {
                $('#radius_id')
                    .prop('disabled', false)
                    .removeClass('bg-light');
            }
        }

        // Category change
        $('select[name="category_id"]').on('change', toggleRadius);

        // City change
        $('#city_id').on('change', toggleRadius);

        // Always run after AJAX loads dropdowns
        setTimeout(toggleRadius, 500);

        // On page load (important!)
        toggleRadius();
    });
</script>

{{-- <script>
    $(document).ready(function() {

        function toggleFields(categoryId, clearValues = false) {

            // Hide & disable all
            $('#radius_wrapper, #area_type_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper')
                .hide()
                .find('select, input')
                .prop('disabled', true);

            // ❗ Clear only when explicitly requested
            if (clearValues) {
                $('#radius_id, #area_type, #from_date, #to_date, #available_days').val('');
            }

            // 🔹 Hoardings (ID = 1)
            if (categoryId == 1) {
                $('#radius_wrapper, #area_type_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper')
                    .show()
                    .find('select, input')
                    .prop('disabled', false);
            }

            // 🔹 Digital Wall Painting (ID = 2)
            if (categoryId == 2) {
                $('#radius_wrapper')
                    .show()
                    .find('select')
                    .prop('disabled', false);
            }
        }

        // 🔥 Category change → CLEAR values
        $('select[name="category_id"]').on('change', function() {
            toggleFields($(this).val(), true);
        });

        // 🔥 Page load → KEEP values
        toggleFields($('select[name="category_id"]').val(), false);
    });
</script> --}}
<script>
    $(document).ready(function() {

        function toggleCategoryFilters() {
            let categoryId = $('select[name="category_id"]').val();

            // ❌ Hide everything by default
            $('#radius_wrapper, #area_type_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper')
                .hide()
                .find('select, input')
                .prop('disabled', true);

            // 🟢 Category 1 → show ALL
            if (categoryId == 1) {
                $('#radius_wrapper, #area_type_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper')
                    .show()
                    .find('select, input')
                    .prop('disabled', false);
            }

            // 🟡 Category 2 → show ONLY radius
            else if (categoryId == 2) {
                $('#radius_wrapper')
                    .show()
                    .find('select')
                    .prop('disabled', false);
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

        function toggleRadius() {
            let categoryId = parseInt($('select[name="category_id"]').val());

            let hasCity = $('#city_id').val();
            let hasArea = $('#area_id').val();

            if (
                !allowedCategories.includes(categoryId) ||
                !hasCity ||
                hasArea
            ) {
                $('#radius_id')
                    .prop('disabled', true)
                    .addClass('bg-light');
            } else {
                $('#radius_id')
                    .prop('disabled', false)
                    .removeClass('bg-light');
            }
        }

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

        // Restore values from search page
        let savedMin = {{ $filters['min_price'] ?? 0 }};
        let savedMax = {{ $filters['max_price'] ?? 200000 }};

        minSlider.val(savedMin);
        maxSlider.val(savedMax);

        updateSlider(); // initial paint

        // ⭐⭐ MOST IMPORTANT — Update UI when dragging ⭐⭐
        minSlider.on("input change", updateSlider);
        maxSlider.on("input change", updateSlider);
    });
</script>
