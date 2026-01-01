<div class="container">
    <div class="search-box-wrapper my-4">
        <div class="search-box p-3 p-md-4">

          <form method="POST" id="searchForm" action="{{ route('website.search') }}">
@csrf

<input type="hidden" name="clear" id="clearFlag">

<div class="row g-2">

    {{-- Category --}}
    <div class="col-md-3">
        <select name="category_id" class="form-select">
            <option value="">Category</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}"
                    {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->category_name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- State --}}
    <div class="col-md-2">
        <select name="state_id" id="state_id" class="form-select">
            <option value="">State</option>
            @foreach($states as $state)
                <option value="{{ $state->location_id }}"
                    {{ ($filters['state_id'] ?? '') == $state->location_id ? 'selected' : '' }}>
                    {{ $state->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- District --}}
    <div class="col-md-2">
        <select name="district_id" id="district_id" class="form-select">
            <option value="">Select District</option>
        </select>
    </div>

    {{-- City --}}
    <div class="col-md-2">
        <select name="city_id" id="city_id" class="form-select">
            <option value="">Select City</option>
        </select>
    </div>

    {{-- Area --}}
    <div class="col-md-3">
        <select name="area_id" id="area_id" class="form-select">
            <option value="">Select Area</option>
        </select>
    </div>

    {{-- Radius --}}
    <div class="col-md-3">
        {{-- <select name="radius_id" class="form-select">
            <option value="">Radius</option>
            @foreach($radiusList as $r)
                <option value="{{ $r->radius }}"
                    {{ ($filters['radius_id'] ?? '') == $r->radius ? 'selected' : '' }}>
                    {{ $r->radius }} KM
                </option>
            @endforeach
        </select> --}}
        <select name="radius_id" class="form-select">
    <option value="">Radius</option>
    @foreach($radiusList as $r)
        <option value="{{ $r->radius }}"
            {{ (string)($filters['radius_id'] ?? '') === (string)$r->radius ? 'selected' : '' }}>
            {{ $r->radius }} KM
        </option>
    @endforeach
</select>

    </div>

    {{-- Dates --}}
    <div class="col-md-2">
        <input type="date" name="from_date" class="form-control"
               value="{{ $filters['from_date'] ?? '' }}">
    </div>

    <div class="col-md-2">
        <input type="date" name="to_date" class="form-control"
               value="{{ $filters['to_date'] ?? '' }}">
    </div>

      <div class="col-md-3 mb-3">
               
         <select name="area_type" class="form-control">
    <option value="">Select Area Type</option>
    <option value="rural" {{ ($filters['area_type'] ?? '') == 'rural' ? 'selected' : '' }}>Rural</option>
    <option value="urban" {{ ($filters['area_type'] ?? '') == 'urban' ? 'selected' : '' }}>Urban</option>
</select>


             
            </div>

 <div class="col-md-2 mb-3">
               
              <select name="available_days" class="form-control">
    <option value="">Select Available Days</option>
    <option value="7" {{ ($filters['available_days'] ?? '') == '7' ? 'selected' : '' }}>7 Days</option>
    <option value="15" {{ ($filters['available_days'] ?? '') == '15' ? 'selected' : '' }}>15 Days</option>
</select>

             
            </div>
    <div class="col-md-2">
    <button type="button"
            class="btn btn-dark w-100"
            onclick="document.getElementById('searchForm').submit();">
        Search Media
    </button>
</div>


    <div class="col-md-2">
        <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters">
            Clear
        </button>
    </div>

</div>
</form>



        </div>
    </div>
</div>

{{-- jQuery --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
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
</script>
<script>
document.getElementById('clearFilters').addEventListener('click', function () {
    document.getElementById('clearFlag').value = '1';
    this.closest('form').submit();
});
</script>



{{-- 
<div class="container">
    <div class="search-box-wrapper mt-5 my-4">
        <div class="container">
            <div class="search-box p-3 p-md-4">

                <div class="row g-3 align-items-center">

                    <div class="col-md-2 col-12">
                        <select class="form-select custom-select">
                            <option selected>Select Media Type</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-12">
                        
                    </div>

                    <div class="col-md-2 col-12">
                        <select class="form-select custom-select" id="district_id">
                            <option value="">Select District</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-12">
                        <select class="form-select custom-select" id="city_id">
                            <option value="">Select City</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-12">
                        <select class="form-select custom-select">
                            <option selected>Select Radius</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-12">
                        <select class="form-select custom-select">
                            <option selected>Select Type</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-12">
                        <button class="btn btn-dark w-100 fw-bold">Search Media</button>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div> --}}

