@extends('vendor.layout')
@section('title', 'Operations')
@section('crumb', 'Vendor Panel / Operations')
@section('heading', 'Operations')

@section('actions')
    <form method="GET" action="{{ route('vendor.operations') }}" class="vp-search">
        <input type="hidden" name="stage" value="{{ $stage }}">
        <label for="opq" class="sr-only">Search booking or hoarding code</label>
        <input id="opq" type="search" name="q" value="{{ request('q') }}" class="vp-input" style="width:300px" placeholder="Search booking no. or hoarding code">
    </form>
@endsection

@section('content')
    <div class="vp-alert vp-alert-info" style="margin:0">
        <i class="fa-solid fa-circle-info"></i> Brand Adda uploads the installation, monitoring and end-date photos and shares them with the user. You can see every photo here.
    </div>

    {{-- Read-only: the workflow itself runs in Super Admin (admin.vendor.operations). --}}
    @include('operations._board', [
        'routes'     => ['index' => 'vendor.operations'],
        'baseQuery'  => [],
        'canEdit'    => false,
        'showVendor' => false,
        'mediaUrl'   => fn ($id) => route('vendor.media.show', $id),
    ])
@endsection
