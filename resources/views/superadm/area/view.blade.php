<style>
    #areaModalContent label {
        font-weight: 600;
        color: #444;
    }
</style>
<div class="container-fluid p-3">

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">State</label>
        <div class="col-sm-8 text-color-bg">{{ $area->state_name }}</div>
    </div>

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">District</label>
        <div class="col-sm-8 text-color-bg">{{ $area->district_name }}</div>
    </div>

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">City</label>
        <div class="col-sm-8 text-color-bg">{{ $area->city_name }}</div>
    </div>

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">Area Name</label>
        <div class="col-sm-8 text-color-bg">{{ $area->area_name }}</div>
    </div>

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">Common STDICAR Name</label>
        <div class="col-sm-8 text-color-bg">{{ $area->common_stdiciar_name }}</div>
    </div>

    {{-- <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">Latitude</label>
        <div class="col-sm-8 text-color-bg">{{ $area->latitude }}</div>
    </div> --}}

    {{-- <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">Longitude</label>
        <div class="col-sm-8 text-color-bg">{{ $area->longitude }}</div>
    </div> --}}


</div>
