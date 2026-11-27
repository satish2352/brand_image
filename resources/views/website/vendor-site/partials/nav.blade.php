{{-- Header menu of the vendor website. $active: home | media | map | contact --}}
@php
    $home = route('vendor-site.show', $vendor->slug);
    $onHome = ($active ?? '') === 'home';
    $onMap = ($active ?? '') === 'map';
    $onContact = ($active ?? '') === 'contact';
@endphp
<a href="{{ $home }}" class="vs-link {{ $onHome ? 'is-active' : '' }}" @if ($onHome) aria-current="page" @endif>
    <i class="fa-solid fa-house" aria-hidden="true"></i><span>Home</span></a>
<a href="{{ route('vendor-site.map', $vendor->slug) }}" class="vs-link {{ $onMap ? 'is-active' : '' }}" @if ($onMap) aria-current="page" @endif>
    <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i><span>Map</span></a>
<a href="{{ route('vendor-site.contact', $vendor->slug) }}" class="vs-link {{ $onContact ? 'is-active' : '' }}" @if ($onContact) aria-current="page" @endif>
    <i class="fa-regular fa-envelope" aria-hidden="true"></i><span>Contact</span></a>
<a href="{{ route('vendor-site.contact', $vendor->slug) }}#enquiry" class="vs-cta">
    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>Enquire Now</span></a>
