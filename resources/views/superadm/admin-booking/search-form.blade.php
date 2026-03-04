<style>
    .form-label {
        font-size: 0.85rem;
        font-weight: 600;
    }

    .card {
        border-radius: 10px;
    }

    .card-body {
        padding: 1.5rem;
    }

    #radius_wrapper {
        display: block !important;
        visibility: visible !important;
    }

    .range-slider-container {
        position: relative;
        width: 100%;
        height: 35px;
    }

    /* grey background track */
    .range-slider-container::before {
        content: "";
        position: absolute;
        width: 100%;
        height: 6px;
        background: #dcdcdc;
        top: 14px;
        border-radius: 5px;
    }

    /* blue selected range */
    .range-slider-fill {
        position: absolute;
        height: 6px;
        background: #0d6efd;
        top: 14px;
        border-radius: 5px;
        z-index: 1;
    }

    /* slider inputs */
    .range-slider-container input[type="range"] {
        position: absolute;
        width: 100%;
        background: none;
        pointer-events: none;
        appearance: none;
        height: 35px;
        z-index: 2;
    }

    /* slider circle */
    .range-slider-container input[type="range"]::-webkit-slider-thumb {
        appearance: none;
        pointer-events: auto;
        width: 16px;
        height: 16px;
        background: #0d6efd;
        border-radius: 50%;
        cursor: pointer;
    }

    .range-slider-container input[type="range"]::-moz-range-thumb {
        pointer-events: auto;
        width: 16px;
        height: 16px;
        background: #0d6efd;
        border-radius: 50%;
        cursor: pointer;
    }
</style>
<div class="card shadow-sm mb-4">
    <div class="card-body">

        {{-- 🔍 TITLE --}}
        <h4 class="text-center fw-bold mb-4">
            <b>Discover Media Spaces Near You</b>
        </h4>

        <form method="POST" id="searchForm" action="{{ route('admin-booking.search') }}">
            @csrf
            <input type="hidden" name="clear" id="clearFlag">
            <input type="hidden" name="category_id" id="category_id" value="1">


            {{-- ================= ROW 1 ================= --}}
            <div class="row g-3 d-flex">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Category</label>
                    <input type="text" value="{{ $firstCategoryName }}" class="form-control"
                        style="background-color: #ffe6e6; color: red; font-weight:bold;" readonly>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">State</label>
                    <select name="state_id" id="state_id" class="form-select form-control">
                        <option value="">Select State</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}"
                                {{ ($filters['state_id'] ?? '') == $state->id ? 'selected' : '' }}>
                                {{ $state->state_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">District</label>
                    <select name="district_id" id="district_id" class="form-select form-control">
                        <option value="">Select District</option>
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Town</label>
                    <select name="city_id" id="city_id" class="form-select form-control">
                        <option value="">Select Town</option>
                    </select>
                </div>



            </div>

            {{-- ================= ROW 2 ================= --}}
            <div class="row g-3 mt-1">

                <div class="col-xl-2 col-lg-3 col-md-6" id="radius_wrapper">
                    <label class="form-label">Radius</label>
                    <select name="radius_id" id="radius_id" class="form-select form-control">
                        <option value="">Radius</option>
                        @foreach ($radiusList as $r)
                            <option value="{{ $r->radius }}"
                                {{ ($filters['radius_id'] ?? '') == $r->radius ? 'selected' : '' }}>
                                {{ $r->radius }} KM
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Area</label>
                    <select name="area_id" id="area_id" class="form-select form-control">
                        <option value="">Select Area</option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-6" id="area_type_wrapper">
                    <label class="form-label">Area Type</label>
                    <select name="area_type" id="area_type" class="form-select form-control">
                        <option value="">Select Type</option>
                        <option value="urban" {{ ($filters['area_type'] ?? '') == 'urban' ? 'selected' : '' }}>Urban
                        </option>
                        <option value="rural" {{ ($filters['area_type'] ?? '') == 'rural' ? 'selected' : '' }}>Rural
                        </option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-6">

                    <label class="form-label">Media Size (sq.ft)</label>

                    <div class="d-flex justify-content-between">
                        <span id="minAreaLabel">
                            {{ $filters['min_area'] ?? $areaRange->min_area }} sqft
                        </span>

                        <span id="maxAreaLabel">
                            {{ $filters['max_area'] ?? $areaRange->max_area }} sqft
                        </span>
                    </div>

                    <div class="range-slider-container">

                        <input type="hidden" name="min_area" id="min_area"
                            value="{{ $filters['min_area'] ?? $areaRange->min_area }}">

                        <input type="hidden" name="max_area" id="max_area"
                            value="{{ $filters['max_area'] ?? $areaRange->max_area }}">

                        <div class="range-slider-fill" id="areaRangeFill"></div>

                        <input type="range" id="minAreaRange" min="{{ $areaRange->min_area }}"
                            max="{{ $areaRange->max_area }}"
                            value="{{ $filters['min_area'] ?? $areaRange->min_area }}">

                        <input type="range" id="maxAreaRange" min="{{ $areaRange->min_area }}"
                            max="{{ $areaRange->max_area }}"
                            value="{{ $filters['max_area'] ?? $areaRange->max_area }}">

                    </div>

                </div>
                {{-- <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label">Media Size</label>

                    <select name="size_id" class="form-select form-control">
                        <option value="">Select Media Size</option>

                        @foreach ($sizes as $id => $size)
                            <option value="{{ $size }}"
                                {{ ($filters['size_id'] ?? '') == $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </div> --}}

                <div class="col-xl-3 col-lg-3 col-md-6">
                    <label class="form-label">Available Days</label>
                    <select name="available_days" class="form-select form-control">
                        <option value="">Select Days</option>
                        <option value="0" {{ ($filters['available_days'] ?? '') == '0' ? 'selected' : '' }}>
                            Instantly Available
                        </option>
                        <option value="7" {{ ($filters['available_days'] ?? '') == '7' ? 'selected' : '' }}>
                            Available in next 7
                        </option>
                        <option value="15" {{ ($filters['available_days'] ?? '') == '15' ? 'selected' : '' }}>
                            Available in next
                            15
                        </option>
                    </select>
                </div>
            </div>

            {{-- ================= BUTTONS ================= --}}
            <div class="row g-3 mt-1">
                <div class="col-xl-3 col-lg-3 col-md-6">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>

                <div class="col-xl-3 col-lg-3 col-md-6">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control"
                        value="{{ $filters['to_date'] ?? '' }}">
                </div>
                <div class="col-xl-2 col-lg-2 col-md-6">
                    @if (isset($totalCount))
                        <label class="form-label"></label>
                        <div class="result-badge alert alert-info text-center">
                            <span class="icon">📍</span>
                            <span class="count">{{ $totalCount }} Results</span>

                        </div>
                    @else
                        <label class="form-label"></label>
                        <div class="result-badge no-result">
                            <span class="icon">❌</span>
                            <span class="count">No Results</span>

                        </div>
                    @endif
                </div>

                <div class="col-xl-2 col-lg-2 col-md-6">
                    <label class="form-label"></label>
                    <button type="submit" class="btn btn-success" style = "padding: 11px; width: inherit;">
                        🔍 Search
                    </button>
                </div>

                <div class="col-xl-2 col-lg-2 col-md-6">
                    <label class="form-label"></label>
                    <button type="button" id="clearFilters" class="btn btn-outline-secondary"
                        style = "padding: 11px; width: inherit;">
                        ✖ Clear
                    </button>
                </div>

            </div>

        </form>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function toggleRadius() {
        const cityId = $('#city_id').val();
        const areaId = $('#area_id').val();

        if (cityId && !areaId) {
            $('#radius_id')
                .prop('disabled', false)
                .removeClass('bg-light');
        } else {
            $('#radius_id')
                .prop('disabled', true)
                .addClass('bg-light')

        }
    }
</script>
<script>
    $(document).ready(function() {



        const categoryId = $('#category_id').val();

        // Hoardings / Digital Wall → Radius visible
        if (categoryId == 1 || categoryId == 2) {
            $('#radius_wrapper').show();
            $('#radius_id').prop('disabled', false);
        } else {
            $('#radius_wrapper').hide();
            $('#radius_id').prop('disabled', true).val('');
        }

    });
</script>

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
                toggleRadius();
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
        if (selectedState) {
            $.post("{{ route('ajax.districts') }}", {
                _token: csrf,
                state_id: selectedState
            }, function(data) {

                let html = '<option value="">Select District</option>';
                data.forEach(d => {
                    html += `<option value="${d.id}" ${d.id == selectedDistrict ? 'selected' : ''}>
                        ${d.district_name}
                    </option>`;
                });

                $('#district_id').html(html);

                // NOW load cities
                if (selectedDistrict) {
                    loadCities(selectedDistrict, selectedCity);

                    // AFTER city loaded → areas
                    setTimeout(function() {
                        if (selectedCity) {
                            loadAreas(selectedCity, selectedArea);
                        }
                    }, 300);
                }
            });
        }

        if (selectedDistrict) {
            loadCities(selectedDistrict, selectedCity);
        }

        if (selectedCity) {
            loadAreas(selectedCity, selectedArea);
        }

        // FINAL CHECK

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



        // 🔁 When city changes
        $('#city_id').on('change', function() {
            $('#area_id').val(''); // reset area
            toggleRadius();
        });

        // 🔁 When area changes
        $('#area_id').on('change', function() {
            toggleRadius();
        });

        // 🔁 On page load (important for search reload)
        toggleRadius();

    });
</script>
<script>
    $(document).ready(function() {

        let minSlider = $("#minAreaRange");
        let maxSlider = $("#maxAreaRange");

        let minLabel = $("#minAreaLabel");
        let maxLabel = $("#maxAreaLabel");

        function updateAreaSlider() {

            let minVal = parseInt(minSlider.val());
            let maxVal = parseInt(maxSlider.val());

            if (minVal >= maxVal) {
                minVal = maxVal - 1;
                minSlider.val(minVal);
            }

            minLabel.text(minVal + " sqft");
            maxLabel.text(maxVal + " sqft");

            $("#min_area").val(minVal);
            $("#max_area").val(maxVal);

            let min = parseInt(minSlider.attr("min"));
            let max = parseInt(minSlider.attr("max"));

            let percent1 = ((minVal - min) / (max - min)) * 100;
            let percent2 = ((maxVal - min) / (max - min)) * 100;

            $("#areaRangeFill").css({
                left: percent1 + "%",
                width: (percent2 - percent1) + "%"
            });

        }

        updateAreaSlider();

        minSlider.on("input", updateAreaSlider);
        maxSlider.on("input", updateAreaSlider);

    });
</script>
