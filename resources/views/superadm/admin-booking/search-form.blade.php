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
            <div class="row g-3">

                {{-- <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select form-control">
                        <option value="">Select Category</option>
                        @if ($category)
                            <option value="{{ $category->id }}"
                                {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->category_name }}
                            </option>
                        @endif
                    </select>
                </div> --}}
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
                            {{-- <option value="{{ $state->location_id }}"
                                {{ ($filters['state_id'] ?? '') == $state->location_id ? 'selected' : '' }}>
                                {{ $state->name }}
                            </option> --}}
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

                <div class="col-xl-2 col-lg-4 col-md-6">
                    <label class="form-label">Area</label>
                    <select name="area_id" id="area_id" class="form-select form-control">
                        <option value="">Select Area</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6" id="radius_wrapper">
                    <label class="form-label">Radius</label>
                    <select name="radius_id" id="radius_id" class="form-select form-control">
                        <option value="">Radius</option>
                        @foreach ($radiusList as $r)
                            <option value="{{ $r->radius }}">{{ $r->radius }} KM</option>
                        @endforeach
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
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}">
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label">Available Days</label>
                    <select name="available_days" class="form-select form-control">
                        <option value="">Select Days</option>
                        <option value="7" {{ ($filters['available_days'] ?? '') == '7' ? 'selected' : '' }}>After
                            7
                        </option>
                        <option value="15" {{ ($filters['available_days'] ?? '') == '15' ? 'selected' : '' }}>After
                            15
                        </option>
                    </select>
                </div>

            </div>

            {{-- ================= BUTTONS ================= --}}
            <div class="row g-3 mt-3 justify-content-center">

                <div class="col-xl-2 col-lg-3 col-md-4 d-grid">
                    <button type="submit" class="btn btn-success">
                        🔍 Search
                    </button>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4 d-grid">
                    <button type="button" id="clearFilters" class="btn btn-outline-secondary">
                        ✖ Clear
                    </button>
                </div>

            </div>

        </form>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    $(document).ready(function() {

        function toggleRadius() {
            const cityId = $('#city_id').val();
            const areaId = $('#area_id').val();

            //  Enable ONLY when city selected AND area NOT selected
            if (cityId && !areaId) {
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
