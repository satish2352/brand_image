{{-- Pager footer for Vendor Inventory & Access lists (server-side paging).
     $page: paginator · $sizeKey: per-page query key · $size: current per page · $noun: "vendors" / "media"
     $pageKey (optional): page query key (default "page") · $anchor (optional): fragment to return to --}}
@php
    $pageKey = $pageKey ?? 'page';
    $anchor = isset($anchor) ? '#' . $anchor : '';
@endphp
<div class="vi-pager">
    <div class="vi-pager-info">
        <span>
            @if ($page->total())
                Showing {{ $page->firstItem() }}–{{ $page->lastItem() }} of {{ $page->total() }} {{ $noun }}
            @else
                No {{ $noun }}
            @endif
            · Page {{ $page->currentPage() }} of {{ max(1, $page->lastPage()) }}
        </span>
        <form method="GET" action="{{ route('admin.vendor.inventory') }}{{ $anchor }}" class="vi-pager-size">
            @foreach (request()->except([$sizeKey, $pageKey]) as $k => $v)
                @if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
            @endforeach
            <label for="size-{{ $sizeKey }}">Show</label>
            <select id="size-{{ $sizeKey }}" name="{{ $sizeKey }}" class="form-control form-control-sm" onchange="this.form.submit()">
                @foreach ($pageSizes as $n)<option value="{{ $n }}" @selected($size == $n)>{{ $n }}</option>@endforeach
            </select>
        </form>
    </div>
    @if ($page->hasPages())
        {{ $page->onEachSide(1)->links('pagination::bootstrap-4') }}
    @endif
</div>
