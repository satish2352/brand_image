@extends('vendor.layout')
@section('title', 'Operations')
@section('crumb', 'Vendor Panel / Operations')
@section('heading', 'Operations')


@section('content')
    <div class="vp-alert vp-alert-info" style="margin:0">
        <i class="fa-solid fa-circle-info"></i> Brand Adda uploads the installation, monitoring and end-date photos and shares them with the user. You can see every photo here.
    </div>

    {{-- Read-only: the workflow itself runs in Super Admin (admin.vendor.operations). --}}
    @include('operations._board', [
        'routes'     => ['index' => 'vendor.operations', 'share' => 'vendor.operations.share'],
        'canShare'   => true, // the vendor can send uploaded photos to the user
        'baseQuery'  => [],
        'canEdit'    => false,
        'showVendor' => false,
        'mediaUrl'   => fn ($id) => route('vendor.media.show', $id),
    ])
@endsection
