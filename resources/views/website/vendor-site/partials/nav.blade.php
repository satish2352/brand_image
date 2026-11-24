{{-- Header links of the vendor website. $active: home | media | contact --}}
@php
    $home = route('vendor-site.show', $vendor->slug);
    $onHome = ($active ?? '') === 'home';
@endphp
<a href="{{ $onHome ? '#inventory' : $home . '#inventory' }}" class="vs-link">Inventory</a>
<a href="{{ $onHome ? '#map' : $home . '#map' }}" class="vs-link">Map</a>
<a href="{{ route('vendor-site.contact', $vendor->slug) }}" class="vs-link"
    @if (($active ?? '') === 'contact') aria-current="page" style="color:#C2410C;box-shadow:inset 0 -3px 0 #F97316" @endif>Contact</a>
<span style="width:1px;height:32px;background:#E5E7EB;margin:0 8px" aria-hidden="true"></span>
