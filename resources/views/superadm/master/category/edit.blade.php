@extends('superadm.layout.master')

@section('content')
<div class="row">
    <div class="col-lg-6 col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">

                <h4>Edit Category</h4>

              <form action="{{ route('category.update', $encodedId) }}" method="POST">
    @csrf

    <div class="form-group">
        <label>Category Name <span class="text-danger">*</span></label>
        <input type="text"
               name="category_name"
               class="form-control"
               value="{{ old('category_name', $category->category_name) }}">
        @error('category_name')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group d-flex justify-content-end mt-3">
        <a href="{{ route('category.list') }}" class="btn btn-secondary mr-2">
            Cancel
        </a>
        <button type="submit" class="btn btn-success">
            Update
        </button>
    </div>
</form>


            </div>
        </div>
    </div>
</div>
@endsection
