{{-- ================= LOCATION: STATE → DISTRICT → CITY → AREA =================
     Options are filled by partials/location-script (each list depends on the
     one before it), so the selects start with only their placeholder.

     $fields picks which selects to render, so the form can put State in the
     first row beside Category / Vendor and District / City / Area in the next. --}}
@php
    $labels = [
        'state_id'    => 'State',
        'district_id' => 'District',
        'city_id'     => 'City',
        'area_id'     => 'Area',
    ];
@endphp
@foreach ($fields ?? array_keys($labels) as $field)
    @php $label = $labels[$field]; @endphp
    <div class="col-md-4 mb-3">
        <label>{{ $label }} <span class="text-danger">*</span></label>
        <select name="{{ $field }}" id="{{ $field }}"
            class="form-control location-select @error($field) is-invalid @enderror">
            <option value="">Select {{ $label }}</option>
        </select>
        @error($field)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
@endforeach
