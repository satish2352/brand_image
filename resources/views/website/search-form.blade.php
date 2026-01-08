<style>
    .bg-light{
        background-color: rgb(202, 196, 196) !important;
    }
    .result-badge {
    background: #fff9d9;
    border-left: 5px solid #ffb100;
    padding: 8px 15px;
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

</style>
<div class="container mt-5 mb-5">
    <h3 class="text-center orange-text">Discover Media Spaces Near You</h3>
    <div class="media-search-card">
 
        <form method="POST" id="searchForm" action="{{ route('website.search') }}">
            @csrf
            <input type="hidden" name="clear" id="clearFlag">
 
            <div class="row g-3">
 
                <!-- Category -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
 
                <!-- State -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label">State</label>
                    <select name="state_id" id="state_id" class="form-select">
                        <option value="">Select State</option>
                        @foreach($states as $state)
                            <option value="{{ $state->location_id }}"
                                {{ ($filters['state_id'] ?? '') == $state->location_id ? 'selected' : '' }}>
                                {{ $state->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
 
                <!-- District -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label">District</label>
                    <select name="district_id" id="district_id" class="form-select">
                        <option value="">Select District</option>
                    </select>
                </div>
 
                <!-- City -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label">City</label>
                    <select name="city_id" id="city_id" class="form-select">
                        <option value="">Select City</option>
                    </select>
                </div>
 
                <!-- Area -->
                <div class="col-lg-3 col-md-4 col-sm-6">
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
                        @foreach($radiusList as $r)
                            <option value="{{ $r->radius }}"
                                {{ (string)($filters['radius_id'] ?? '') === (string)$r->radius ? 'selected' : '' }}>
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
                        <option value="rural" {{ ($filters['area_type'] ?? '') == 'rural' ? 'selected' : '' }}>Rural</option>
                        <option value="urban" {{ ($filters['area_type'] ?? '') == 'urban' ? 'selected' : '' }}>Urban</option>
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
                <div class="col-lg-2 col-md-4 col-sm-6"  id="days_wrapper">
                    <label class="form-label">Available Days</label>
                    <select name="available_days" id="available_days" class="form-select">
                        <option value="">Select Days</option>
                        <option value="7" {{ ($filters['available_days'] ?? '') == '7' ? 'selected' : '' }}>7 Days</option>
                        <option value="15" {{ ($filters['available_days'] ?? '') == '15' ? 'selected' : '' }}>15 Days</option>
                    </select>
                </div>
 
                <!-- Buttons -->
                {{-- <div class="col-lg-2 col-md-6 col-sm-12 d-grid mt-md-auto">
                    <button type="button"
                            class="btn btn-search"
                            onclick="document.getElementById('searchForm').submit();">
                        Search Media
                    </button>
                </div> --}}
 <div class="col-lg-2 col-md-6 col-sm-12 d-grid mt-md-auto">
    <button type="button"
            class="btn btn-search"
            onclick="document.getElementById('searchForm').submit();">
        Search Media
    </button>
</div>

                <div class="col-lg-2 col-md-6 col-sm-12 d-grid mt-md-auto">
                    <button type="button" class="btn btn-clear" id="clearFilters">
                        Clear Filters
                    </button>
                </div>
               
                {{-- BEAUTIFUL COUNT DISPLAY --}}
                @if(($filters['category_id'] ?? '') != '')
                    @php $catName = $mediaList->first()->category_name ?? ''; @endphp

                    <div class="col-lg-6 col-md-8 col-sm-12 d-flex align-items-center mt-2">
                        @if($mediaList->total() > 0)
                            <div class="result-badge" style="margin-top: 32px">
                                <span class="icon">📍</span>
                                <span class="count">{{ $mediaList->total() }} Results</span>
                                <span class="label">for {{ $catName }}</span>
                            </div>
                        @else
                            <div class="result-badge no-result">
                                <span class="icon">❌</span>
                                <span class="count">No Results</span>
                                <span class="label">for {{ $catName }}</span>
                            </div>
                        @endif
                    </div>
                @endif
                
            </div>
        </form>
 
    </div>
</div>
 

 
{{-- jQuery --}}


{{-- <script>
$(function () {

    const csrf = "{{ csrf_token() }}";

    /* State → District */
    $('#state_id').change(function () {

        $('#district_id').html('<option>Loading...</option>');
        $('#city_id').html('<option value="">Select City</option>');
        $('#area_id').html('<option value="">Select Area</option>');

        $.post("{{ route('locations.districts') }}", {
            _token: csrf,
            state_id: $(this).val()
        }, function (data) {

            let html = '<option value="">Select District</option>';
            data.forEach(d => html += `<option value="${d.location_id}">${d.name}</option>`);
            $('#district_id').html(html);
        });
    });

    /* District → City */
    $('#district_id').change(function () {

        $('#city_id').html('<option>Loading...</option>');
        $('#area_id').html('<option value="">Select Area</option>');

        $.post("{{ route('locations.cities') }}", {
            _token: csrf,
            district_id: $(this).val()
        }, function (data) {

            let html = '<option value="">Select City</option>';
            data.forEach(c => html += `<option value="${c.location_id}">${c.name}</option>`);
            $('#city_id').html(html);
        });
    });

    /* City → Area */
    $('#city_id').change(function () {

        $('#area_id').html('<option>Loading...</option>');

        $.post("{{ route('locations.areas') }}", {
            _token: csrf,
            city_id: $(this).val()
        }, function (data) {

            let html = '<option value="">Select Area</option>';
            data.forEach(a => html += `<option value="${a.id}">${a.area_name}</option>`);
            $('#area_id').html(html);
        });
    });

});
</script> --}}

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const selectedState    = "{{ $filters['state_id'] ?? '' }}";
    const selectedDistrict = "{{ $filters['district_id'] ?? '' }}";
    const selectedCity     = "{{ $filters['city_id'] ?? '' }}";
    const selectedArea     = "{{ $filters['area_id'] ?? '' }}";
</script>

<script>
$(document).ready(function () {

    const csrf = "{{ csrf_token() }}";

    /* ================= LOAD DISTRICTS ================= */
    function loadDistricts(stateId, selected = '') {
        if (!stateId) return;

        $.post("{{ route('locations.districts') }}", {
            _token: csrf,
            state_id: stateId
        }, function (data) {

            let html = '<option value="">Select District</option>';
            data.forEach(d => {
                html += `<option value="${d.location_id}" ${d.location_id == selected ? 'selected' : ''}>
                            ${d.name}
                         </option>`;
            });

            $('#district_id').html(html);
        });
    }

    /* ================= LOAD CITIES ================= */
    function loadCities(districtId, selected = '') {
        if (!districtId) return;

        $.post("{{ route('locations.cities') }}", {
            _token: csrf,
            district_id: districtId
        }, function (data) {

            let html = '<option value="">Select City</option>';
            data.forEach(c => {
                html += `<option value="${c.location_id}" ${c.location_id == selected ? 'selected' : ''}>
                            ${c.name}
                         </option>`;
            });

            $('#city_id').html(html);
        });
    }

    /* ================= LOAD AREAS ================= */
    function loadAreas(cityId, selected = '') {
        if (!cityId) return;

        $.post("{{ route('locations.areas') }}", {
            _token: csrf,
            city_id: cityId
        }, function (data) {

            let html = '<option value="">Select Area</option>';
            data.forEach(a => {
                html += `<option value="${a.id}" ${a.id == selected ? 'selected' : ''}>
                            ${a.area_name}
                         </option>`;
            });

            $('#area_id').html(html);
        });
    }

    /* ================= ON CHANGE EVENTS ================= */
    $('#state_id').on('change', function () {
        loadDistricts(this.value);
        $('#city_id').html('<option value="">Select City</option>');
        $('#area_id').html('<option value="">Select Area</option>');
    });

    $('#district_id').on('change', function () {
        loadCities(this.value);
        $('#area_id').html('<option value="">Select Area</option>');
    });

    $('#city_id').on('change', function () {
        loadAreas(this.value);
    });

    /* ================= PAGE LOAD ================= */
    if (selectedState) {
        loadDistricts(selectedState, selectedDistrict);
    }

    if (selectedDistrict) {
        loadCities(selectedDistrict, selectedCity);
    }

    if (selectedCity) {
        loadAreas(selectedCity, selectedArea);
    }

});
</script>

<script>
document.getElementById('clearFilters').addEventListener('click', function () {
    document.getElementById('clearFlag').value = '1';
    this.closest('form').submit();
});
</script>

<script>
$(document).ready(function () {

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
    $('select[name="category_id"]').on('change', function () {
        toggleDateFields($(this).val());
    });

    // 🔥 On page load (important for search page reload)
    toggleDateFields($('select[name="category_id"]').val());

});
</script>
<script>
$(document).ready(function () {

    // ✅ Allowed categories for Radius
    const radiusEnabledCategories = [1, 2]; 
    // 1 = Hoardings/Billboards
    // 2 = Digital Wall Painting

    function toggleRadiusField(categoryId) {

        if (radiusEnabledCategories.includes(parseInt(categoryId))) {
            $('#radius_id')
                .prop('disabled', false)
                .removeClass('bg-light');
        } else {
            $('#radius_id')
                .prop('disabled', true)
                .addClass('bg-light')
                .val('');
        }
    }

    // 🔥 On category change
    $('select[name="category_id"]').on('change', function () {
        toggleRadiusField($(this).val());
    });

    // 🔥 On page load (important)
    toggleRadiusField($('select[name="category_id"]').val());

});
</script>
<script>
$(document).ready(function () {

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
    $('select[name="category_id"]').on('change', function () {
        toggleFields($(this).val(), true);
    });

    // 🔥 Page load → KEEP values
    toggleFields($('select[name="category_id"]').val(), false);
});
</script>

{{-- <script>
$(document).ready(function () {

    function toggleFields(categoryId) {

        // 🔹 Reset all (HIDE + DISABLE)
        $('#radius_wrapper, #area_type_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper')
            .hide()
            .find('select, input')
            .prop('disabled', true)
            .val('');

        // 🔹 Hoardings (ID = 1)
        if (categoryId == 1) {

            $('#radius_wrapper, #area_type_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper')
                .show()
                .find('select, input')
                .prop('disabled', false);
        }

        // 🔹 Digital Wall Painting (ID = 3)
        if (categoryId == 2) {

            $('#radius_wrapper')
                .show()
                .find('select')
                .prop('disabled', false);
        }
    }

    // On category change
    $('select[name="category_id"]').on('change', function () {
        toggleFields($(this).val());
    });

    // On page load
    toggleFields($('select[name="category_id"]').val());
});
</script> --}}
