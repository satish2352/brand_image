@extends('superadm.layout.master')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">

        <h4 class="mb-4">Contact Details</h4>

        {{-- ================= BASIC DETAILS ================= --}}
        <h6>Basic Details</h6>
        <table class="table table-bordered">
            <tr>
                <th width="25%">Category</th>
                <td width="25%">{{ $contact->category_name ?? '-' }}</td>

                <th width="25%">Vendor Name</th>
                <td width="25%">{{ $contact->vendor_name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Full Name</th>
                <td>{{ $contact->full_name ?? '-' }}</td>

                <th>Mobile</th>
                <td>{{ $contact->mobile_no ?? '-' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $contact->email ?? '-' }}</td>

                <th>Price / Budget</th>
                <td>{{ $contact->budget ?? '-' }}</td>
            </tr>
        </table>

        {{-- ================= LOCATION DETAILS ================= --}}
        <h6 class="mt-4">Location Details</h6>
        <table class="table table-bordered">
            <tr>
                <th width="25%">State</th>
                <td width="25%">{{ $contact->state_name ?? '-' }}</td>

                <th width="25%">District</th>
                <td width="25%">{{ $contact->district_name ?? '-' }}</td>
            </tr>
            <tr>
                <th>City</th>
                <td>{{ $contact->city_name ?? '-' }}</td>

                <th>Area</th>
                <td>{{ $contact->area_name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Address</th>
                <td colspan="3">{{ $contact->address ?? '-' }}</td>
            </tr>
        </table>

        {{-- ================= DIMENSIONS ================= --}}
        <h6 class="mt-4">Dimensions</h6>
        <table class="table table-bordered">
            <tr>
                <th width="25%">Width (ft)</th>
                <td width="25%">{{ $contact->width ?? '-' }}</td>

                <th width="25%">Height (ft)</th>
                <td width="25%">{{ $contact->height ?? '-' }}</td>
            </tr>
        </table>

        {{-- ================= REMARK ================= --}}
        <h6 class="mt-4">Remark / Message</h6>
        <table class="table table-bordered">
            <tr>
                <th width="25%">Remark</th>
                <td colspan="3">{{ $contact->remark ?? '-' }}</td>
            </tr>
        </table>

        {{-- ================= IMAGES ================= --}}
        <h6 class="mt-4">Images</h6>
        <div class="row">
            @forelse($contact->images ?? [] as $img)
                <div class="col-md-3 mb-3">
                    <img src="{{ config('fileConstants.IMAGE_VIEW') . $img }}"
                         class="img-fluid rounded"
                         style="height:150px; object-fit:cover;">
                </div>
            @empty
                <p class="text-muted ms-2">No images available.</p>
            @endforelse
        </div>

        <a href="{{ route('contact-us.list') }}" class="btn btn-secondary mt-3">
            Back
        </a>

    </div>
</div>
@endsection
