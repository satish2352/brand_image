<style>
    #areaModalContent label {
        font-weight: 600;
        color: #444;
    }
</style>
<div class="container-fluid p-3">

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">State</label>
        <div class="col-sm-8">{{ $area->state_name }}</div>
    </div>

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">District</label>
        <div class="col-sm-8">{{ $area->district_name }}</div>
    </div>

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">City</label>
        <div class="col-sm-8">{{ $area->city_name }}</div>
    </div>

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">Area Name</label>
        <div class="col-sm-8">{{ $area->area_name }}</div>
    </div>

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">Common STDICAR Name</label>
        <div class="col-sm-8">{{ $area->common_stdiciar_name }}</div>
    </div>

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">Latitude</label>
        <div class="col-sm-8">{{ $area->latitude }}</div>
    </div>

    <div class="row mb-2">
        <label class="col-sm-4 font-weight-bold">Longitude</label>
        <div class="col-sm-8">{{ $area->longitude }}</div>
    </div>


</div>
