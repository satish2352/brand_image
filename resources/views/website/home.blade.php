@extends('website.layout')

@section('title', 'Home')

@section('content')

    <div class="container-fluid g-0">
        <div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                <img src="{{ asset('asset/images/website/banner.webp') }}" class="d-block w-100" alt="...">
                </div>
                <div class="carousel-item">
                <img src="{{ asset('asset/images/website/banner.webp') }}" class="d-block w-100" alt="...">
                </div>
                <div class="carousel-item">
                <img src="{{ asset('asset/images/website/banner.webp') }}" class="d-block w-100" alt="...">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>

    {{-- Search Section Include --}}
    @include('website.search')
  @include('website.mediacarts')

@endsection
