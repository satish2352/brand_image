{{-- Select2 powers the searchable District / Town dropdowns below. jQuery is
     already loaded by the layout <head>, so only the plugin is needed here.
     Blade @-once keeps it to one copy if the partial is ever included twice. --}}
@once
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // The theme footer loads a SECOND, much older jQuery (1.11.3) that takes
        // over window.jQuery/$ — and it does so while the page is still parsing,
        // so by DOM-ready the global $ is the old one, which has no Select2 on it.
        // Select2 registered itself on the jQuery that is current right here, so
        // pin that instance now and drive the searchable dropdowns with it.
        window.jQuerySelect2 = window.jQuery;
    </script>
@endonce

<style>
    .bg-light {
        background-color: #E5E7EB !important;
    }

    .result-badge {
        background: rgba(249, 115, 22, 0.12);
        border-left: 5px solid #F97316;
        padding: 0px 15px;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 15px;
    }

    .result-badge .icon {
        font-size: 18px;
    }

    .result-badge .count {
        color: #0F172A;
        font-weight: 700;
    }

    .result-badge .label {
        color: #0F172A;
    }

    .result-badge.no-result {
        border-left-color: #F97316;
        background: rgba(249, 115, 22, 0.12);
    }

    .result-badge.no-result .count {
        color: #F97316;
    }

    /* Uniform height for all inputs & selects */
    .media-search-card .form-select,
    .media-search-card .form-control {
        height: 44px;
    }

    /* ===== Select2 styled to match the form (light-orange hover, no blue) =====
       The native <select> blue highlight cannot be restyled in Chrome, so these
       selects are upgraded to Select2 which is fully styleable. */
    .media-search-card .select2-container {
        width: 100% !important;
    }

    .media-search-card .select2-container--default .select2-selection--single {
        /* Matches .media-search-card .form-select exactly — the District and Town
           fields are the only Select2 ones, and at 44px/6px they sat short and
           squarer than the native selects beside them. */
        height: 46px;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        display: flex;
        align-items: center;
        padding: 0 10px;
        background: #FFFFFF;
    }

    .media-search-card .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.4;
        color: #0F172A;
        padding: 0;
    }

    .media-search-card .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #0F172A;
    }

    .media-search-card .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px;
    }

    .media-search-card .select2-container--default.select2-container--focus .select2-selection--single,
    .media-search-card .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #F97316;
        outline: none;
    }

    /* the type-ahead box at the top of the District / Town dropdowns */
    .bi-orange-dropdown .select2-search--dropdown {
        padding: 8px;
    }

    .bi-orange-dropdown .select2-search--dropdown .select2-search__field {
        height: 38px;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        padding: 0 10px;
        outline: none;
    }

    .bi-orange-dropdown .select2-search--dropdown .select2-search__field:focus {
        border-color: #F97316;
        box-shadow: none;
    }

    /* "Searching…" / "No match found" rows */
    .bi-orange-dropdown .select2-results__message {
        color: #0F172A;
        padding: 8px 12px;
    }

    /* dropdown options — hover/active in LIGHT ORANGE (not blue) */
    .bi-orange-dropdown .select2-results__option--highlighted[aria-selected] {
        background-color: rgba(249, 115, 22, 0.12) !important;
        color: #F97316 !important;
    }

    /* the already-selected option */
    .bi-orange-dropdown .select2-results__option[aria-selected=true] {
        background-color: rgba(249, 115, 22, 0.12) !important;
        color: #F97316 !important;
        font-weight: 600;
    }

    /* Keep the open dropdown ABOVE the fixed site header (z-index 9999) and any
       floating buttons, so the options are actually visible when clicked. */
    .select2-container--open {
        z-index: 10050 !important;
    }

    .select2-container--open .select2-dropdown,
    .bi-orange-dropdown.select2-dropdown {
        z-index: 10050 !important;
    }

    /* ensure every option renders with readable colours and full height
       (guards against global list/anchor resets hiding them) */
    .bi-orange-dropdown .select2-results__options {
        max-height: 280px;
        overflow-y: auto;
    }

    .bi-orange-dropdown .select2-results__option {
        display: list-item;
        list-style: none;
        color: #0F172A;
        background-color: #FFFFFF;
        padding: 8px 12px;
    }

    /* Fix date input height */
    .media-search-card input[type="date"] {
        height: 44px;
    }

    /* SLIDER WRAP */
    .range-slider-container {
        position: relative;
        width: 100%;
        padding-top: 15px;
        padding-bottom: 30px;
        margin-top: 10px;
    }

    .range-slider-container input[type=range] {
        -webkit-appearance: none;
        width: 100%;
        background: transparent;
        position: absolute;
        top: 10px !important;
        /* keep thumb centered */
        pointer-events: none;
    }

    .range-slider-container input[type=range]::-webkit-slider-runnable-track {
        height: 6px;
        background: #E5E7EB;
        border-radius: 3px;
    }

    .range-slider-container input[type=range]::-webkit-slider-thumb {
        -webkit-appearance: none;
        pointer-events: auto;
        width: 18px;
        height: 18px;
        background: #F97316;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0px 0px 5px rgba(15, 23, 42, 0.3);
        margin-top: -6px;
        /* ⭐ PERFECT vertical centering */
        z-index: 5;
        position: relative;
    }

    .range-slider-fill {
        position: absolute;
        height: 6px;
        background: #F97316;
        top: 10px;
        border-radius: 3px;
        z-index: 2;
    }

    /* FIX MEDIA SIZE RANGE */
    .range-slider-container {
        position: relative;
        width: 100%;
        height: 30px;
    }

    .range-slider-container input[type=range] {
        position: absolute;
        width: 100%;
        height: 6px;
        top: 10px;
        background: none;
        pointer-events: none;
    }

    .range-slider-container input[type=range]::-webkit-slider-runnable-track {
        height: 6px;
        background: #E5E7EB;
        border-radius: 5px;
    }

    .range-slider-container input[type=range]::-webkit-slider-thumb {
        pointer-events: auto;
        position: relative;
        z-index: 3;
    }

    .range-slider-fill {
        position: absolute;
        height: 6px;
        background: #F97316;
        top: 10px;
        border-radius: 5px;
        z-index: 2;
    }

    /* SINGLE-HANDLE SLIDER (Radius) — the pointer-events dance above exists so
       two overlapping thumbs stay grabbable; with one thumb it just makes the
       track dead, so give the whole input its clicks back. */
    .range-slider-container.single input[type=range] {
        pointer-events: auto;
        cursor: pointer;
    }

    /* Radius is only selectable for some categories, and only once a Town is
       chosen — grey the whole track out rather than tinting a box. */
    .range-slider-container.single.is-disabled {
        opacity: .45;
        pointer-events: none;
    }
</style>
@php
    /* ============ SHARED SHORTLIST MODE ============
       The /shared/{token} page reuses this whole card. $biScope is set there
       and nowhere else, and carries two things:

         locked  — Category / State / District / Town, fixed by the link. They
                   render disabled and the controller recomputes them from the
                   link's own rows, so the posted values are never read.
         options — the levels the client CAN still use (Area, Area Type,
                   Highway, Landmarks), narrowed to values that actually occur
                   on the shortlisted media. Offering the full masters would
                   fill them with choices that cannot match anything.

       Absent on /search and the home page, where every branch below falls
       back to exactly what it rendered before. */
    $biScope   = $biScope ?? null;
    $biLocked  = $biScope['locked'] ?? [];
    $biOptions = $biScope['options'] ?? null;

    $biFormAction = $searchFormAction ?? route('website.search');

    /* Highways and Landmarks are the view composer's masters on every page,
       a shared shortlist included. They used to be narrowed there to the
       values the shortlisted rows happened to carry, which left a client
       looking at a Highway menu holding one entry and a Landmarks menu
       holding none — no way to tell a filter with nothing to offer from one
       that is simply broken. The result set is bounded by media_ids either
       way, so the only thing the narrowing bought was a shorter menu. */
    $biHighways = $highways;
    $biLandmarks = $landmarks;

    /* A shortlist can hold a single hoarding, or several of one size — and
       then min and max coincide, which leaves <input type=range> degenerate
       and the fill maths dividing by zero. Give it a hair of width. */
    $biAreaMin = (float) ($areaRange->min_area ?? 0);
    $biAreaMax = (float) ($areaRange->max_area ?? 0);
    if ($biAreaMax <= $biAreaMin) {
        $biAreaMax = $biAreaMin + 1;
    }

    /* /search paginates, a shortlist does not. */
    $biResultCount = !isset($mediaList)
        ? null
        : (is_object($mediaList) && method_exists($mediaList, 'total') ? $mediaList->total() : count($mediaList));
@endphp
{{-- On the results page the same partial renders in a compact form: the
     marketing copy is dropped and the card tightens up, so results sit above
     the fold instead of a screen further down. Same path test the layout uses
     for the footer — and a shared shortlist, which is a results page too. --}}
@php $biSearchCompact = $biScope !== null || request()->is('search') || request()->is('brand_image/public/search'); @endphp
<section class="bi-search-hero{{ $biSearchCompact ? ' is-compact' : '' }}">
<div class="container-fluid mt-5 mb-5">
    {{-- Hero copy, sitting on the pale left half of the artwork. --}}
    <div class="bi-search-hero-copy">
        <span class="bi-search-eyebrow">Outdoor Media Platform</span>
        <h2 class="bi-search-hero-title">
            Big Spaces.
            <span class="accent">Bigger Opportunities.</span>
        </h2>
        <p class="bi-search-hero-sub">
            Discover, plan and book the best outdoor media spaces across India
        </p>
        
    </div>
    <div class="media-search-card">

        <form method="POST" id="searchForm" action="{{ $biFormAction }}">
            @csrf
            {{-- <input type="hidden" name="clear" id="clearFlag"> --}}

            <div class="row g-3 justify-content-center justify-content-lg-between">

                {{-- ===== The four levels a shared link fixes =====
                     Inside a shortlist each renders as a disabled select
                     holding only what the link covers: one value when the
                     whole shortlist sits at it, otherwise a plain count, since
                     there is nothing to pick between. They are labels there,
                     not filters — the controller derives them from the link's
                     own rows and never reads them off the request. --}}

                <!-- Category -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Category</label>
                    @isset($biLocked['category_id'])
                        @include('website.partials.locked-filter', [
                            'name'    => 'category_id',
                            'id'      => null,
                            'options' => $biLocked['category_id'],
                            'plural'  => 'categories',
                        ])
                    @else
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->category_name }}
                                </option>
                            @endforeach
                        </select>
                    @endisset
                </div>

                <!-- State -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">State</label>
                    @isset($biLocked['state_id'])
                        @include('website.partials.locked-filter', [
                            'name'    => 'state_id',
                            'id'      => 'state_id',
                            'options' => $biLocked['state_id'],
                            'plural'  => 'states',
                        ])
                    @else
                        <select name="state_id" id="state_id" class="form-select">
                            <option value="">Select State</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}"
                                    {{ ($filters['state_id'] ?? '') == $state->id ? 'selected' : '' }}>
                                    {{ $state->state_name }}
                                </option>
                            @endforeach
                        </select>
                    @endisset
                </div>

                <!-- District -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">District</label>
                    @isset($biLocked['district_id'])
                        @include('website.partials.locked-filter', [
                            'name'    => 'district_id',
                            'id'      => 'district_id',
                            'options' => $biLocked['district_id'],
                            'plural'  => 'districts',
                        ])
                    @else
                        <select name="district_id" id="district_id" class="form-select">
                            <option value="">Select District</option>
                        </select>
                    @endisset
                </div>

                <!-- City -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Town</label>
                    @isset($biLocked['city_id'])
                        @include('website.partials.locked-filter', [
                            'name'    => 'city_id',
                            'id'      => 'city_id',
                            'options' => $biLocked['city_id'],
                            'plural'  => 'towns',
                        ])
                    @else
                        <select name="city_id" id="city_id" class="form-select">
                            <option value="">Select Town</option>
                        </select>
                    @endisset
                </div>

                <!-- Area -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Area</label>
                    {{-- Free to use inside a shortlist, but filled server-side
                         there: the cascade that normally loads it hangs off the
                         Town dropdown, and that one is disabled. --}}
                    <select name="area_id" id="area_id" class="form-select">
                        <option value="">Select Area</option>
                        @if ($biOptions)
                            @foreach ($biOptions['area_id'] as $areaId => $areaName)
                                <option value="{{ $areaId }}"
                                    {{ ($filters['area_id'] ?? '') == $areaId ? 'selected' : '' }}>
                                    {{ $areaName }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Area Type -->
                {{-- No wrapper id on purpose: it used to carry a stray duplicate
                     id="radius_wrapper", and now that it sits BEFORE the Radius
                     slider, $('#radius_wrapper') would have resolved to this box
                     instead and the category show/hide would have driven the
                     wrong field. Nothing targets this wrapper. --}}
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Area Type</label>
                    <select name="areatype_id" class="form-select" id="areatype_id">
                        <option value="">Select Type</option>

                        @foreach ($areaTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ ($filters['areatype_id'] ?? '') == $type->id ? 'selected' : '' }}>
                                {{ $type->areatype_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- <div class="col-lg-2 col-md-4 col-sm-6" id="area_type_wrapper">
                    <label class="form-label">Area Type</label>
                    <select name="area_type" class="form-select" id="area_type">
                        <option value="">Select Type</option>
                        <option value="rural" {{ ($filters['area_type'] ?? '') == 'rural' ? 'selected' : '' }}>Rural
                        </option>
                        <option value="urban" {{ ($filters['area_type'] ?? '') == 'urban' ? 'selected' : '' }}>Urban
                        </option>
                    </select>
                </div> --}}

                <!-- Highway -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="highway_wrapper">
                    <label class="form-label">Highway</label>
                    <select name="highway_id" id="highway_id" class="form-select">
                        <option value="">Select Highway</option>
                        @foreach ($biHighways as $hw)
                            <option value="{{ $hw->id }}"
                                {{ ($filters['highway_id'] ?? '') == $hw->id ? 'selected' : '' }}>
                                {{ $hw->highway_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Landmarks (multi-select checkbox dropdown) -->
                @php $selectedLandmarks = (array) ($filters['landmark_ids'] ?? []); @endphp
                <div class="col-lg-2 col-md-4 col-sm-6" id="landmark_wrapper">
                    <label class="form-label">Landmarks</label>
                    <div class="landmark-dropdown" id="landmarkDropdown">
                        <button type="button" class="form-select text-start landmark-toggle" id="landmarkToggle">
                            <span class="landmark-toggle-text">Select Landmarks</span>
                        </button>
                        <div class="landmark-menu" id="landmarkMenu">
                            @forelse ($biLandmarks as $lm)
                                <label class="landmark-option">
                                    <input type="checkbox" name="landmark_ids[]" value="{{ $lm->id }}"
                                        {{ in_array($lm->id, $selectedLandmarks) ? 'checked' : '' }}>
                                    <span>{{ $lm->landmark_name }}</span>
                                </label>
                            @empty
                                <div class="landmark-empty">No landmarks available</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- From Date -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="date_wrapper">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" id="from_date" class="form-control"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>

                <!-- To Date -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="to_date_wrapper">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" id="to_date" class="form-control"
                        value="{{ $filters['to_date'] ?? '' }}">
                </div>

                <!-- Available Days -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="days_wrapper">
                    <label class="form-label">Available Days</label>
                    <select name="available_days" id="available_days" class="form-select">
                        <option value="">Select Days</option>

                        <option value="0" {{ ($filters['available_days'] ?? '') == '0' ? 'selected' : '' }}>
                            Instantly Available
                        </option>

                        <option value="7" {{ ($filters['available_days'] ?? '') == '7' ? 'selected' : '' }}>
                            Available After 7 Days
                        </option>

                        <option value="15" {{ ($filters['available_days'] ?? '') == '15' ? 'selected' : '' }}>
                            Available After 15 Days
                        </option>
                    </select>
                </div>

                {{-- Pads the dropdown row above to a full 12 columns. It only has
                     five fields, and justify-content-lg-between would otherwise
                     share the spare column out as gaps between them — which left
                     this row sitting on different columns than the row above it. --}}
                <div class="col-lg-2 d-none d-lg-block" aria-hidden="true"></div>

                {{-- Row break so the Media Size slider starts the slider row
                     instead of riding up into the padding above. Desktop only —
                     the md/sm layouts wrap at a different count. --}}
                <div class="w-100 d-none d-lg-block"></div>

                <div class="col-lg-2 col-md-4 col-sm-6">

                    <label class="form-label">Media Size (sq.ft)</label>

                    <div class="d-flex justify-content-between">
                        <span id="minAreaLabel">
                            {{ number_format($filters['min_area'] ?? $biAreaMin) }} sqft
                        </span>

                        <span id="maxAreaLabel">
                            {{ number_format($filters['max_area'] ?? $biAreaMax) }} sqft
                        </span>
                    </div>

                    <div class="range-slider-container">

                        <input type="hidden" name="min_area" id="min_area" value="{{ $filters['min_area'] ?? '' }}">

                        <input type="hidden" name="max_area" id="max_area" value="{{ $filters['max_area'] ?? '' }}">

                        <div class="range-slider-fill" id="areaRangeFill"></div>

                        <input type="range" id="minAreaRange" min="{{ $biAreaMin }}"
                            max="{{ $biAreaMax }}" step="1"
                            value="{{ $filters['min_area'] ?? $biAreaMin }}">

                        <input type="range" id="maxAreaRange" min="{{ $biAreaMin }}"
                            max="{{ $biAreaMax }}" step="1"
                            value="{{ $filters['max_area'] ?? $biAreaMax }}">

                    </div>

                </div>

                <!-- Radius -->
                <div class="col-lg-2 col-md-4 col-sm-6" id="radius_wrapper">
                    <label class="form-label">Radius</label>

                    <div class="d-flex justify-content-between">
                        {{-- Left = what is currently selected, right = the ceiling,
                             mirroring the Media Size slider's two-label row. --}}
                        <span id="radiusValueLabel"></span>
                        <span>{{ $radiusMax }} KM</span>
                    </div>

                    <div class="range-slider-container single" id="radiusSlider">

                        <div class="range-slider-fill" id="radiusRangeFill"></div>

                        {{-- Posts as radius_id, the same km value the old <select>
                             sent. 0 is the off position: the backend skips the
                             distance filter and falls back to the plain city match. --}}
                        <input type="range" name="radius_id" id="radius_id" min="{{ $radiusMin }}"
                            max="{{ $radiusMax }}" step="1"
                            value="{{ (int) ($filters['radius_id'] ?? $radiusMin) }}">

                    </div>
                </div>

                {{-- <div class="col-lg-2 col-md-4 col-sm-6" id="size_wrapper">
                    <label class="form-label">Media Size</label>

                    <select name="size_id" class="form-select">
                        <option value="">Select Media Size</option>

                        @foreach ($sizes as $id => $size)
                            <option value="{{ $size }}"
                                {{ ($filters['size_id'] ?? '') == $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </div> --}}

                <div class="col-lg-2 col-md-4 col-sm-6" id="days_wrapper">
                    <label class="form-label">Budget</label>

                    <!-- Budget Slider -->
                    <div class="d-flex justify-content-between">
                        <span id="minRangeLabel" style="font-weight:600">
                            ₹{{ number_format($filters['min_price'] ?? 0) }}
                        </span>

                        <span id="maxRangeLabel" style="font-weight:600">
                            ₹{{ number_format($filters['max_price'] ?? 1000000) }}
                        </span>
                    </div>
                    <div class="range-slider-container">
                        <input type="hidden" name="min_price" id="min_price"
                            value="{{ $filters['min_price'] ?? 0 }}">
                        <input type="hidden" name="max_price" id="max_price"
                            value="{{ $filters['max_price'] ?? 1000000 }}">

                        <div class="range-slider-fill" id="rangeFill"></div>

                        <input type="range" id="minRange" min="0" max="1000000" step="1000"
                            value="{{ $filters['min_price'] ?? 0 }}">
                        <input type="range" id="maxRange" min="0" max="1000000" step="1000"
                            value="{{ $filters['max_price'] ?? 1000000 }}">


                    </div>

                </div>

                {{-- Pads the slider row out to a full 12 columns. The three
                     sliders only fill half of it, and justify-content-lg-between
                     would otherwise fling them apart to the card edges instead of
                     leaving them on the grid columns the dropdowns above use.
                     On /search that same empty half is where the buttons go, so
                     there is nothing left to pad and the card saves a whole row
                     of height. --}}
                @unless ($biSearchCompact)
                    <div class="col-lg-6 d-none d-lg-block" aria-hidden="true"></div>
                @endunless

                {{-- Compact: also a column, so the three actions ride up beside
                     the sliders and sit on their bottom edge. --}}
                <div class="row {{ $biSearchCompact ? 'col-lg-6 align-self-lg-end' : '' }}" style="padding-top:15px">
                    <!-- Buttons -->
                    <div class="{{ $biSearchCompact ? 'col-lg-4' : 'col-lg-2' }} col-md-4 col-sm-12 d-grid mt-md-auto">
                        <button type="button" class="btn btn-search"
                            onclick="document.getElementById('searchForm').submit();">
                            Search Media
                        </button>
                    </div>

                    <div class="{{ $biSearchCompact ? 'col-lg-4' : 'col-lg-2' }} col-md-4 col-sm-12 d-grid mt-md-auto mt-3 ">
                        <button type="button" class="btn btn-clear" id="clearFilters">
                            Clear Filters
                        </button>
                    </div>
                    {{-- A shortlist always shows its count: its Category can be
                         blank (a link spanning two of them) and the visitor
                         still needs to see how many of the hoardings picked for
                         them survived the filters. --}}
                    @if ($biResultCount !== null && ($biScope !== null || ($filters['category_id'] ?? '') != ''))
                        <div class="{{ $biSearchCompact ? 'col-lg-4' : 'col-lg-2' }} col-md-8 col-sm-12 d-flex align-items-center mt-3 ">
                            @if ($biResultCount > 0)
                                <div class="result-badge">
                                    <span class="icon">📍</span>
                                    <span class="count">
                                        {{ $biResultCount }}@isset($totalCount) of {{ $totalCount }} @endisset Results
                                    </span>
                                </div>
                            @else
                                <div class="result-badge no-result">
                                    <span class="icon">❌</span>
                                    <span class="count">No Results</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>


            </div>
        </form>

    </div>

</div>
</section>

{{-- jQuery is already loaded by the layout <head>. Native <select> dropdowns are
     used here for reliability (Select2 was breaking the cascading filters). --}}
<script>
    const selectedState = "{{ $filters['state_id'] ?? '' }}";
    const selectedDistrict = "{{ $filters['district_id'] ?? '' }}";
    const selectedCity = "{{ $filters['city_id'] ?? '' }}";
    const selectedArea = "{{ $filters['area_id'] ?? '' }}";
</script>
<script>
    $(document).ready(function() {

        let minSlider = $("#minAreaRange");
        let maxSlider = $("#maxAreaRange");
        let fill = $("#areaRangeFill");

        let minLabel = $("#minAreaLabel");
        let maxLabel = $("#maxAreaLabel");

        let minLimit = Number(minSlider.attr("min"));
        let maxLimit = Number(maxSlider.attr("max"));

        function updateAreaSlider() {

            let minVal = parseInt(minSlider.val());
            let maxVal = parseInt(maxSlider.val());

            if (minVal > maxVal - 1) {
                minVal = maxVal - 1;
                minSlider.val(minVal);
            }

            // correct percent calculation
            let minPercent = ((minVal - minLimit) / (maxLimit - minLimit)) * 100;
            let maxPercent = ((maxVal - minLimit) / (maxLimit - minLimit)) * 100;

            fill.css({
                left: minPercent + "%",
                width: (maxPercent - minPercent) + "%"
            });

            minLabel.text(minVal + " sqft");
            maxLabel.text(maxVal + " sqft");

            // Only set hidden fields when user actually moves slider
            if (window._areaSliderTouched) {
                $("#min_area").val(minVal);
                $("#max_area").val(maxVal);
            }
        }

        updateAreaSlider();

        minSlider.on("input change", function() {
            window._areaSliderTouched = true;
            updateAreaSlider();
        });
        maxSlider.on("input change", function() {
            window._areaSliderTouched = true;
            updateAreaSlider();
        });

    });
</script>
<script>
    // ===== Radius slider =====================================================
    // Replaces the old Radius <select>: one handle from 0 km up to the largest
    // radius in the admin's radius master. It posts the same `radius_id` km
    // value the <select> used to, and the backend clamps to the same ceiling.
    $(document).ready(function() {

        const slider = $("#radius_id");
        if (!slider.length) return;

        const fill = $("#radiusRangeFill");
        const label = $("#radiusValueLabel");

        const minLimit = Number(slider.attr("min"));
        const maxLimit = Number(slider.attr("max"));

        function updateRadiusSlider() {

            const val = Number(slider.val());
            const percent = maxLimit > minLimit ?
                ((val - minLimit) / (maxLimit - minLimit)) * 100 : 0;

            // One handle, so the fill always runs from the left edge.
            fill.css({
                left: "0%",
                width: percent + "%"
            });

            // 0 is the off position — the backend skips the distance filter and
            // falls back to the plain Town match, so say that rather than "0 KM".
            label.text(val > minLimit ? val + " KM" : "Any");
        }

        slider.on("input change", updateRadiusSlider);

        // Clear Filters resets the input directly and needs the track repainted.
        window.updateRadiusSlider = updateRadiusSlider;

        updateRadiusSlider();
    });
</script>
<script>
    $(document).ready(function() {
        let today = new Date().toISOString().split('T')[0];
        $('#from_date').attr('min', today);

        // Optional: also restrict "To Date" not to be before From Date
        $('#from_date').on('change', function() {
            $('#to_date').attr('min', $(this).val());
        });
    });
</script>
<script>
    // ===============================
    // GLOBAL FUNCTION (IMPORTANT)
    // ===============================
    function toggleRadius() {

        const allowedCategories = [1, 2];

        let categoryId = parseInt($('select[name="category_id"]').val());
        let hasCity = $('#city_id').val();
        let hasArea = $('#area_id').val();

        // Boolean(), not the bare || chain: that yields hasArea's *string* value,
        // and jQuery's toggleClass(cls, state) only honours a real boolean —
        // anything else makes it TOGGLE, which flip-flopped the greyed state on
        // every call (enabled, then greyed again the moment areas finished
        // loading).
        const off = Boolean(
            !allowedCategories.includes(categoryId) ||
            !hasCity ||
            hasArea
        );

        // Radius is a slider now, so "unavailable" has to grey out the whole
        // track — .bg-light only ever tinted the old <select>'s box. A disabled
        // input is not submitted, so the value is kept rather than cleared.
        $('#radius_id').prop('disabled', off);
        $('#radiusSlider').toggleClass('is-disabled', off);
    }
</script>
<script>
    // Runs against the PINNED jQuery (window.jQuerySelect2, set where Select2 is
    // loaded at the top of this file), not the global $ — by the time this
    // executes the global is the theme's old jQuery, which has neither Select2
    // nor the handlers Select2 binds, so everything below would silently no-op.
    (function($) {
        $(function() {

        const csrf = "{{ csrf_token() }}";

        // ===== District & Town: searchable, server-backed dropdowns ============
        // These two levels are the long ones (a state has dozens of districts, a
        // district hundreds of towns), so rather than dumping every option into
        // the <select> they are Select2 dropdowns whose search box queries the
        // backend — LocationController::getDistricts / getCities take a `q` term
        // and return the parent-scoped matches. State / Area stay native: those
        // lists are short enough to scroll.

        // Wires up one searchable level. `parentVal` is read lazily on every
        // request, so the dropdown always searches inside whatever its parent
        // select currently holds — no re-init needed when the parent changes.
        function searchableSelect(sel, url, parentKey, parentVal, labelKey, placeholder) {
            $(sel).select2({
                width: '100%',
                placeholder: placeholder,
                allowClear: true,
                dropdownCssClass: 'bi-orange-dropdown',
                ajax: {
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250, // debounce keystrokes
                    data: function(params) {
                        const payload = {
                            _token: csrf,
                            q: params.term || ''
                        };
                        payload[parentKey] = parentVal();
                        return payload;
                    },
                    processResults: function(rows) {
                        return {
                            results: (rows || []).map(function(row) {
                                return {
                                    id: row.id,
                                    text: row[labelKey]
                                };
                            })
                        };
                    }
                },
                language: {
                    searching: function() {
                        return 'Searching…';
                    },
                    noResults: function() {
                        // Empty parent is the common cause, so say so instead of
                        // leaving the user staring at a bare "No results".
                        return parentVal() ? 'No match found' : 'Choose the level above first';
                    }
                }
            });
        }

        // On a shared shortlist these two are fixed by the link and render as
        // plain disabled selects already holding their label. Upgrading them
        // would replace that label with an empty Select2 box whose search
        // queries the full inventory — the one thing the page must not offer.
        const biLockedChain = $('#district_id').prop('disabled') || $('#city_id').prop('disabled');

        if (!biLockedChain) {
            searchableSelect('#district_id', "{{ route('ajax.districts') }}", 'state_id',
                function() {
                    return $('#state_id').val();
                }, 'district_name', 'Select District');

            searchableSelect('#city_id', "{{ route('ajax.cities') }}", 'district_id',
                function() {
                    return $('#district_id').val();
                }, 'city_name', 'Select Town');
        }

        // Clears a searchable select back to its placeholder. `change.select2` is
        // Select2's own namespace: it repaints the widget without firing the
        // delegated `change` handlers below, so a reset never cascades twice.
        function resetSearchable(sel, placeholder) {
            $(sel).empty()
                .append(new Option(placeholder, '', false, false))
                .val(null)
                .trigger('change.select2');
        }

        function resetAreas() {
            $('#area_id').html('<option value="">Select Area</option>');
        }

        // A Select2 in ajax mode only knows the options it has fetched, so a value
        // restored from $filters has to be injected by hand: pull the parent's
        // unfiltered list once and turn the matching row into the selected option.
        function preselect(sel, url, params, labelKey, id) {
            return $.post(url, $.extend({
                _token: csrf
            }, params)).then(function(rows) {
                const row = (rows || []).filter(function(r) {
                    return String(r.id) === String(id);
                })[0];
                if (!row) return;
                $(sel).append(new Option(row[labelKey], row.id, true, true))
                    .trigger('change.select2');
            });
        }

        function loadAreas(cityId, selected = '') {

            if (!cityId) return;

            $.post("{{ route('ajax.areas') }}", {
                _token: csrf,
                city_id: cityId
            }, function(data) {

                let html = '<option value="">Select Area</option>';

                data.forEach(a => {
                    html += `<option value="${a.id}" ${a.id == selected ? 'selected' : ''}>
                            ${a.area_name}
                         </option>`;
                });

                $('#area_id').html(html);

                toggleRadius(); // ⭐ IMPORTANT
            });
        }

        // ================= EVENTS (delegated → fire reliably with Select2) ====

        $(document).on('change', '#state_id', function() {

            resetSearchable('#district_id', 'Select District');
            resetSearchable('#city_id', 'Select Town');
            resetAreas();

            toggleRadius();
        });

        $(document).on('change', '#district_id', function() {

            resetSearchable('#city_id', 'Select Town');
            resetAreas();

            toggleRadius();
        });

        $(document).on('change', '#city_id', function() {

            // Also fires when the "×" clears the town, and then there is no city
            // to load areas for — drop the stale ones instead.
            if (this.value) {
                loadAreas(this.value);
            } else {
                resetAreas();
            }

            toggleRadius();
        });

        $(document).on('change', '#area_id', toggleRadius);

        // INITIAL LOAD — restore the saved district → town → area chain in order,
        // each step waiting for the one it depends on.
        if (biLockedChain) {
            // Nothing to restore: a shared shortlist renders District, Town and
            // Area server-side from its own rows, already selected.
            toggleRadius();
        } else if (selectedDistrict) {
            preselect('#district_id', "{{ route('ajax.districts') }}", {
                    state_id: selectedState
                }, 'district_name', selectedDistrict)
                .then(function() {
                    if (!selectedCity) return;
                    return preselect('#city_id', "{{ route('ajax.cities') }}", {
                        district_id: selectedDistrict
                    }, 'city_name', selectedCity);
                })
                .then(function() {
                    if (selectedCity) loadAreas(selectedCity, selectedArea);
                    toggleRadius();
                });
        }

        toggleRadius();
        });
    })(window.jQuerySelect2 || window.jQuery);
</script>
<script>
    document.getElementById('clearFilters').addEventListener('click', function() {

        // Reset form fields
        document.getElementById('searchForm').reset();

        // Reset dependent dropdowns. District and Town are Select2 widgets, so
        // the value has to be cleared too — swapping the <option>s alone would
        // leave the old label painted — and the repaint must go through the
        // PINNED jQuery, since that is the instance Select2's handlers live on.
        // Skipped for the levels a shared link fixes: they are not filters the
        // visitor set, so clearing is not theirs to do — and blanking them here
        // would wipe the label the reloaded page is about to paint back.
        const $s2 = window.jQuerySelect2 || window.jQuery;
        if (!$s2('#district_id').prop('disabled')) {
            $s2('#district_id').html('<option value="">Select District</option>').val(null).trigger('change.select2');
        }
        if (!$s2('#city_id').prop('disabled')) {
            $s2('#city_id').html('<option value="">Select Town</option>').val(null).trigger('change.select2');
            $('#area_id').html('<option value="">Select Area</option>');
        }

        // Reset slider
        $("#minRange").val(0);
        $("#maxRange").val(1000000);
        $("#min_price").val(0);
        $("#max_price").val(1000000);
        $("#minRangeLabel").text("₹0");
        $("#maxRangeLabel").text("₹10,00,000");

        // Reset slider fill
        $("#rangeFill").css({
            left: "0%",
            width: "100%"
        });

        // Radius back to 0 ("Any"), track repainted to match
        $("#radius_id").val($("#radius_id").attr("min"));
        if (window.updateRadiusSlider) window.updateRadiusSlider();

        // Optional: reload default media via form submit
        // (keeps layout stable)
        let form = document.getElementById('searchForm');

        let input = document.createElement("input");
        input.type = "hidden";
        input.name = "clear";
        input.value = "1";

        form.appendChild(input);
        form.submit();

    });
</script>
<script>
    $(document).ready(function() {

        function toggleDateFields(categoryId) {

            // ✅ Only category ID = 1 allows date selection
            if (categoryId == 1) {
                $('#from_date, #to_date, #available_days, #area_type')
                    .prop('disabled', false)
                    .removeClass('bg-light')
                    .trigger('change.select2');
            } else {
                $('#from_date, #to_date, #available_days, #area_type')
                    .prop('disabled', true)
                    .addClass('bg-light')
                    .val('') // clear values
                    .trigger('change.select2');
            }
        }

        // 🔥 On category change
        $('select[name="category_id"]').on('change', function() {
            toggleDateFields($(this).val());
        });

        // 🔥 On page load (important for search page reload)
        toggleDateFields($('select[name="category_id"]').val());

    });
</script>

<script>
    $(document).ready(function() {

        function toggleCategoryFilters() {
            let categoryId = $('select[name="category_id"]').val();

            // ❌ Hide everything by default
            $('#radius_wrapper, #area_type_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper, #highway_wrapper, #landmark_wrapper')
                .hide()
                .find('select, input')
                .prop('disabled', true)
                .trigger('change.select2');

            // 🟢 Category 1 → show ALL
            if (categoryId == 1) {
                $('#radius_wrapper, #area_type_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper, #highway_wrapper, #landmark_wrapper')
                    .show()
                    .find('select, input')
                    .prop('disabled', false)
                    .trigger('change.select2');
            }

            // 🟡 Category 2 → show ONLY radius
            else if (categoryId == 2) {
                $('#radius_wrapper')
                    .show()
                    .find('select')
                    .prop('disabled', false)
                    .trigger('change.select2');
            }
        }

        // 🔥 On category change
        $('select[name="category_id"]').on('change', toggleCategoryFilters);

        // 🔥 On page load
        toggleCategoryFilters();
    });
</script>

<script>
    $(document).ready(function() {

        const allowedCategories = [1, 2];
        // 🔥 EVENTS (THIS IS IMPORTANT)
        $('select[name="category_id"]').on('change', toggleRadius);
        $('#city_id').on('change', toggleRadius);
        $('#area_id').on('change', toggleRadius);

        // 🔥 Page load
        toggleRadius();
    });
</script>

<script>
    $(document).ready(function() {

        let minSlider = $("#minRange");
        let maxSlider = $("#maxRange");
        let fill = $("#rangeFill");
        let minLabel = $("#minRangeLabel");
        let maxLabel = $("#maxRangeLabel");
        let maxValue = parseInt(maxSlider.attr("max"));

        function updateSlider() {
            let minVal = parseInt(minSlider.val());
            let maxVal = parseInt(maxSlider.val());

            if (minVal > maxVal - 1000) {
                minVal = maxVal - 1000;
                minSlider.val(minVal);
            }

            let minPercent = (minVal / maxValue) * 100;
            let maxPercent = (maxVal / maxValue) * 100;

            fill.css({
                left: minPercent + "%",
                width: (maxPercent - minPercent) + "%"
            });

            minLabel.text("₹" + minVal.toLocaleString('en-IN'));
            maxLabel.text("₹" + maxVal.toLocaleString('en-IN'));

            $("#min_price").val(minVal);
            $("#max_price").val(maxVal);
        }

        // ✅ FIXED Blade values
        let savedMin = {{ $filters['min_price'] ?? 0 }};
        let savedMax = {{ $filters['max_price'] ?? 1000000 }};

        minSlider.val(savedMin);
        maxSlider.val(savedMax);

        updateSlider();

        minSlider.on("input change", updateSlider);
        maxSlider.on("input change", updateSlider);

    });
</script>

{{-- ===== Custom checkbox dropdown for the Landmarks multi-select filter ===== --}}
<style>
    .landmark-dropdown {
        position: relative;
    }
    .landmark-toggle {
        width: 100%;
        /* keep the same dropdown caret as the native .form-select dropdowns
           (a plain `background:#FFFFFF` shorthand had wiped out the caret image) */
        background-color: #FFFFFF;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
        padding-right: 2.25rem;
        cursor: pointer;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .landmark-menu {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 1000;
        max-height: 220px;
        overflow-y: auto;
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
        padding: 6px;
    }
    .landmark-dropdown.open .landmark-menu {
        display: block;
    }
    .landmark-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        margin: 0;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 400;
    }
    .landmark-option:hover {
        background: #E5E7EB;
    }
    .landmark-option input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #F97316;
        cursor: pointer;
    }
    .landmark-empty {
        padding: 8px 10px;
        color: #0F172A;
        font-size: 14px;
    }
</style>
<script>
    $(function () {
        const $dropdown = $('#landmarkDropdown');
        const $toggle = $('#landmarkToggle');
        const $text = $toggle.find('.landmark-toggle-text');

        function updateLabel() {
            const labels = $dropdown.find('input[type="checkbox"]:checked')
                .map(function () {
                    return $(this).siblings('span').text().trim();
                }).get();

            if (labels.length === 0) {
                $text.text('Select Landmarks');
            } else if (labels.length <= 2) {
                $text.text(labels.join(', '));
            } else {
                $text.text(labels.length + ' selected');
            }
        }

        $toggle.on('click', function (e) {
            e.stopPropagation();
            $dropdown.toggleClass('open');
        });

        // keep menu open while ticking checkboxes
        $('#landmarkMenu').on('click', function (e) {
            e.stopPropagation();
        });

        $dropdown.on('change', 'input[type="checkbox"]', updateLabel);

        // close when clicking outside
        $(document).on('click', function () {
            $dropdown.removeClass('open');
        });

        updateLabel();
    });
</script>

<script>
    // ===== ROBUST category-dependent filter visibility =====
    // Uses a DELEGATED change handler on document so it always fires when the
    // category changes (Select2 dispatches a native change event), regardless of
    // script/binding order or an error in any other ready handler. This is the
    // authoritative toggle; the earlier inline one is now redundant but harmless.
    jQuery(function ($) {
        function applyCategoryFilters() {
            var cat = String($('select[name="category_id"]').val() || '').trim();
            var $deps = $('#radius_wrapper, #date_wrapper, #to_date_wrapper, #days_wrapper, #highway_wrapper, #landmark_wrapper');

            // hide + disable everything first
            $deps.hide().find('select, input').prop('disabled', true).trigger('change.select2');

            if (cat === '1') {
                // Hoardings/Billboards → show ALL dependent filters
                $deps.show().find('select, input').prop('disabled', false).trigger('change.select2');
            } else if (cat === '2') {
                // Digital Wall Painting → show only Radius
                $('#radius_wrapper').show().find('select, input').prop('disabled', false).trigger('change.select2');
            }

            // This runs LAST of the category handlers (it is the delegated one),
            // and it just re-enabled every input in #radius_wrapper — including
            // the Radius slider, which has its own rules about Town and Area.
            // Let toggleRadius have the final say.
            toggleRadius();
        }

        // Fire whenever the category changes (delegated → survives Select2 wrapping)
        $(document).on('change', 'select[name="category_id"]', applyCategoryFilters);

        // Run once after Select2 has initialised so the initial state is correct
        setTimeout(applyCategoryFilters, 350);
    });
</script>

