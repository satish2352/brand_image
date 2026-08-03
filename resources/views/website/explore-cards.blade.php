@php
    use Illuminate\Support\Str;
@endphp

@forelse ($mediaList as $media)
    @php
        $sqft = (float) $media->width * (float) $media->height;
        $img = $media->first_image
            ? config('fileConstants.IMAGE_VIEW') . $media->first_image
            : asset('assets/img/no-image.png');
    @endphp
    <div class="explore-card" id="exp-card-{{ $media->id }}"
        data-id="{{ $media->id }}"
        data-lat="{{ $media->latitude }}"
        data-lng="{{ $media->longitude }}">

        <div class="explore-card-img">
            <img src="{{ $img }}" alt="{{ $media->media_title ?: $media->category_name }}">
            @if (!empty($media->hoarding_code))
                <span class="explore-code">{{ $media->hoarding_code }}</span>
            @endif
        </div>

        <div class="explore-card-body">
            <h6 class="explore-title">
                <a href="{{ route('website.media-details', base64_encode($media->id)) }}">
                    {{ $media->media_title ?: $media->category_name }} {{ $media->area_name }}
                </a>
            </h6>

            <div class="explore-meta">
                <span><strong>Size:</strong> {{ rtrim(rtrim(number_format($media->width, 2), '0'), '.') }} ×
                    {{ rtrim(rtrim(number_format($media->height, 2), '0'), '.') }} ft</span>
                <span><strong>Area:</strong> {{ number_format($sqft, 0) }} sqft</span>
            </div>

            @if (!empty($media->highway_name))
                <div class="explore-tagline"><i class="mdi mdi-road-variant"></i> {{ $media->highway_name }}</div>
            @endif
            @if (!empty($media->landmark_names))
                <div class="explore-tagline"><i class="mdi mdi-map-marker"></i> {{ $media->landmark_names }}</div>
            @endif

            <div class="explore-foot">
                <span class="explore-price">₹ {{ number_format($media->price, 0) }}</span>
                <a class="explore-view-btn"
                    href="{{ route('website.media-details', base64_encode($media->id)) }}">View Details</a>
            </div>
        </div>
    </div>
@empty
    <div class="explore-empty">No hoardings match your filters.</div>
@endforelse
