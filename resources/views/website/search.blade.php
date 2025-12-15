{{-- Search Section --}}
<div class="container">
    <div class="search-box-wrapper my-4">
        <div class="container">
            <div class="search-box p-3 p-md-4">

                <div class="row g-3 align-items-center">

                    <div class="col-md-2 col-12">
                        <select class="form-select custom-select">
                            <option selected>Select Media Type</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-12">
                        <select class="form-select custom-select" id="state_id">
                            <option value="">Select State</option>
                            @foreach($states as $s)
                                <option value="{{ $s->id }}">{{ $s->state }}</option>
                            @endforeach
                        </select>
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
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {

    // When State changes → load Districts
    $("#state_id").on("change", function() {
        let stateId = $(this).val();
        $("#district_id").html('<option>Loading...</option>');
        $("#city_id").html('<option>Select City</option>');

        if (!stateId) {
            $("#district_id").html('<option value="">Select District</option>');
            return;
        }

        $.ajax({
            url: "{{ route('api.districts') }}",
            method: "GET",
            data: { state_id: stateId },
            success: function(data) {
                let html = '<option value="">Select District</option>';
                data.forEach(function(d) {
                    html += `<option value="${d.id}">${d.district}</option>`;
                });
                $("#district_id").html(html);
            }
        });
    });

    // When District changes → load Cities
    $("#district_id").on("change", function() {
        let districtId = $(this).val();
        $("#city_id").html('<option>Loading...</option>');

        if (!districtId) {
            $("#city_id").html('<option value="">Select City</option>');
            return;
        }

        $.ajax({
            url: "{{ route('api.cities') }}",
            method: "GET",
            data: { district_id: districtId },
            success: function(data) {
                let html = '<option value="">Select City</option>';
                data.forEach(function(c) {
                    html += `<option value="${c.id}">${c.city}</option>`;
                });
                $("#city_id").html(html);
            }
        });
    });

});
</script>