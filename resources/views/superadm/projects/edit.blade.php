@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    {{-- @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif --}}

                    <h4>Edit Project</h4>
                    <form action="{{ route('projects.update', $encodedId) }}" method="POST">
                        @csrf


                        {{-- <div class="form-group">
                            <label for="plant_id">Plant <span class="text-danger">*</span></label>
                            <input type="hidden" name="id" class="form-control" value="{{ old('id', $data->id) }}">
                            <select name="plant_id" id="plant_id" class="form-control">
                                <option value="" disabled>Select Plant </option>
                                @foreach ($plants as $plant)
                                    <option value="{{ $plant->id }}"
                                        {{ $data->plant_id == $plant->id ? 'selected' : '' }}
                                        {{ old('plant_id') == $plant->id ? 'selected' : '' }}>
                                        {{ $plant->plant_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('plant_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div> --}}

                        <div class="form-group">
                            <label>Project Name <span class="text-danger">*</span></label>
                            <input type="hidden" name="id" class="form-control" value="{{ old('id', $data->id) }}">
                            <input type="text" name="project_name" class="form-control"
                                value="{{ old('project_name', $data->project_name) }}">
                            @error('project_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Project Short Description </label>
                            <input type="text" name="project_description" class="form-control"
                                value="{{ old('project_description', $data->project_description) }}">
                            @error('project_description')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label>Project URL <span class="text-danger">*</span></label>
                            <input type="text" name="project_url" class="form-control"
                                value="{{ old('project_url', $data->project_url) }}">
                            @error('project_url')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="plant_id">Assign Plants <span class="text-danger">*</span></label>
                            <input type="hidden" name="id" value="{{ old('id', $data->id) }}">

                            @php
                                $selectedPlants = is_array($data->plant_id)
                                    ? $data->plant_id
                                    : (is_string($data->plant_id) && json_decode($data->plant_id) ? json_decode($data->plant_id, true) : [$data->plant_id]);
                            @endphp

                            <select name="plant_id[]" id="plant_id" class="form-control" multiple>
                                @foreach ($plants as $plant)
                                    <option value="{{ $plant->id }}" {{ in_array($plant->id, old('plant_id', $selectedPlants)) ? 'selected' : '' }}>
                                        {{ $plant->plant_name }} ({{ $plant->plant_code }})
                                    </option>
                                @endforeach
                            </select>

                            @error('plant_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                        <div class="form-group">
                            <label>Status</label>
                            <select name="is_active" class="form-control">
                                <option value="">select status</option>
                                <option value="1" {{ old('is_active', $data->is_active) == '1' ? 'selected' : '' }}>
                                    Active</option>
                                <option value="0" {{ old('is_active', $data->is_active) == '0' ? 'selected' : '' }}>
                                    Inactive</option>
                            </select>
                            @error('is_active')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group d-flex justify-content-end">
                            <a href="{{ route('projects.list') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-success btn-add">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-multiselect/dist/css/bootstrap-multiselect.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-multiselect/dist/js/bootstrap-multiselect.min.js"></script>

    <script>
    $(document).ready(function () {
        $('#plant_id').multiselect({
            includeSelectAllOption: true,
            enableFiltering: true,
            maxHeight: 300,
            buttonWidth: '100%'
        });
    });
    </script>

@endsection
