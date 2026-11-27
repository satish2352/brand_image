@extends('vendor.layout')
@section('title', 'Media Management')
@section('crumb', 'Vendor Panel / Media Management')
@section('heading', 'Media Management')

@section('content')
    @php
        $f = $filters;
        $sortLink = function ($key, $label) use ($f) {
            $dir = (($f['sort'] ?? '') === $key && ($f['dir'] ?? 'desc') === 'asc') ? 'desc' : 'asc';
            $arrow = ($f['sort'] ?? '') === $key ? (($f['dir'] ?? 'desc') === 'asc' ? ' ↑' : ' ↓') : '';
            $url = route('vendor.inventory', array_merge(request()->query(), ['sort' => $key, 'dir' => $dir, 'page' => null]));
            return '<a href="' . e($url) . '" style="color:#334155;text-decoration:none">' . e($label) . $arrow . '</a>';
        };
        $tabs = ['' => ['All', $counts['all']], 'approved' => ['Approved', $counts['approved']], 'pending' => ['Pending Approval', $counts['pending']], 'rejected' => ['Rejected', $counts['rejected']]];
    @endphp

    <form class="vp-card vp-card-pad" method="GET" action="{{ route('vendor.inventory') }}" aria-label="Search filters">
        @if (!empty($f['approval']))<input type="hidden" name="approval" value="{{ $f['approval'] }}">@endif
        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:16px;border-bottom:1px solid #F1F5F9">
            <div>
                <h2 class="vp-h2" style="margin:0">Your media</h2>
                <div class="vp-muted" style="font-size:13px;margin-top:2px">Filter your media, export it to Excel or add a new site.</div>
            </div>
            <div class="vp-acts">
                <a href="{{ route('vendor.inventory.export', request()->query()) }}" class="vp-btn vp-btn-g" id="invExport"><i class="fa-solid fa-download"></i>Export Excel</a>
                <a href="{{ route('vendor.media.create') }}" class="vp-btn vp-btn-o"><i class="fa-solid fa-plus"></i>Add Media</a>
            </div>
        </div>
        <div class="vp-grid">
            <div><label class="vp-label" for="f-cat">Category</label>
                <select id="f-cat" name="category_id" class="vp-input">
                    <option value="">All categories</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(($f['category_id'] ?? '') == $c->id)>{{ $c->category_name }}</option>@endforeach
                </select></div>
            <div><label class="vp-label" for="f-dis">District</label>
                <select id="f-dis" name="district_id" class="vp-input">
                    <option value="">All districts</option>
                    @foreach ($districts as $d)<option value="{{ $d->id }}" @selected(($f['district_id'] ?? '') == $d->id)>{{ $d->district_name }}</option>@endforeach
                </select></div>
            <div><label class="vp-label" for="f-town">Town</label>
                <select id="f-town" name="city_id" class="vp-input">
                    <option value="">All towns</option>
                    @foreach ($towns as $t)<option value="{{ $t->id }}" data-district="{{ $t->district_id }}" @selected(($f['city_id'] ?? '') == $t->id)>{{ $t->city_name }}</option>@endforeach
                </select></div>
            <div><label class="vp-label" for="f-year">Year</label>
                <select id="f-year" name="year" class="vp-input">
                    <option value="">Any year</option>
                    @foreach ($years as $y)<option value="{{ $y }}" @selected(($f['year'] ?? '') == $y)>{{ $y }}</option>@endforeach
                </select></div>
            <div><label class="vp-label" for="f-month">Month</label>
                <select id="f-month" name="month" class="vp-input">
                    <option value="">Any month</option>
                    @foreach ($months as $n => $m)<option value="{{ $n }}" @selected(($f['month'] ?? '') == $n)>{{ $m }}</option>@endforeach
                </select></div>
            <div><label class="vp-label" for="f-from">From</label><input id="f-from" type="date" name="from_date" class="vp-input" value="{{ $f['from_date'] ?? '' }}"></div>
            <div><label class="vp-label" for="f-to">To</label><input id="f-to" type="date" name="to_date" class="vp-input" value="{{ $f['to_date'] ?? '' }}"></div>
            <div><label class="vp-label" for="f-sec">Sector Code</label><input id="f-sec" type="text" name="sector_code" class="vp-input" placeholder="e.g. HD000142" value="{{ $f['sector_code'] ?? '' }}"></div>
            {{-- Reset / Filter sit at the right end of the last row of fields, not on a line of their own. --}}
            <div class="vp-grid-end">
                <a href="{{ route('vendor.inventory') }}" class="vp-btn vp-btn-g">Reset</a>
                <button type="submit" class="vp-btn vp-btn-n">Filter</button>
            </div>
        </div>
    </form>

    <section class="vp-card" style="overflow:hidden">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #E5E7EB">
            <nav class="vp-tabs" aria-label="Approval status" id="invTabs">
                @foreach ($tabs as $key => [$label, $count])
                    <a class="vp-tab {{ ($f['approval'] ?? '') === $key ? 'active' : '' }}"
                        href="{{ route('vendor.inventory', array_merge(request()->except(['approval', 'page']), $key ? ['approval' => $key] : [])) }}">{{ $label }} · {{ $count }}</a>
                @endforeach
            </nav>
            <form method="GET" action="{{ route('vendor.inventory') }}" class="vp-search" id="invSearch" role="search">
                @foreach (request()->except(['q', 'page']) as $k => $v)
                    @if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                @endforeach
                <label for="q" class="sr-only">Search inventory</label>
                <input id="q" type="search" name="q" class="vp-input" autocomplete="off"
                    placeholder="Search sector code, title, category, district…" title="Search by sector code, media code, title, category, state, district, city, area or address. Combine words, e.g. Hoardings Nashik." value="{{ $f['q'] ?? '' }}">
            </form>
        </div>

        {{-- Swapped in place by the live search (server-side) below. --}}
        <div id="invResults">
        <div class="vp-table-wrap">
            <table class="vp-table">
                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>{!! $sortLink('code', 'Sector Code') !!}</th>
                        <th>{!! $sortLink('title', 'Media Title') !!}</th>
                        <th>{!! $sortLink('category', 'Category') !!}</th>
                        <th>State</th>
                        <th>District</th>
                        <th>{!! $sortLink('city', 'City') !!}</th>
                        <th>Area</th>
                        <th>{!! $sortLink('price', 'Price / Month') !!}</th>
                        <th>Vendor Name</th>
                        <th>Media Code</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th>Active</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($media as $row)
                        @php $av = $row->availability; @endphp
                        <tr class="vp-row-link" data-href="{{ route('vendor.media.show', $row->id) }}" title="Open details">
                            <td class="nowrap">{{ $media->firstItem() + $loop->index }}</td>
                            <td class="nowrap" style="font-weight:700">{{ $row->hoarding_code ?: '—' }}</td>
                            <td style="font-weight:600;min-width:180px">{{ $row->media_title }}</td>
                            <td class="nowrap">{{ $row->category_name }}</td>
                            <td class="nowrap">{{ $row->state_name }}</td>
                            <td class="nowrap">{{ $row->district_name }}</td>
                            <td class="nowrap">{{ $row->city_name }}</td>
                            <td class="nowrap">{{ $row->area_name }}</td>
                            <td class="nowrap" style="font-weight:700">₹ {{ number_format((float) $row->price) }}</td>
                            <td class="nowrap">{{ $row->vendor_name }}</td>
                            <td class="nowrap">{{ $row->media_code ?: '—' }}</td>
                            <td class="nowrap">
                                <span class="vp-badge" style="background:{{ $av['bg'] }};color:{{ $av['fg'] }}">{{ $av['label'] }}</span>
                                @if ($av['to'])<div class="vp-muted" style="font-size:11px;margin-top:4px">{{ \Carbon\Carbon::parse($av['from'])->format('d M') }} – {{ \Carbon\Carbon::parse($av['to'])->format('d M') }}</div>@endif
                            </td>
                            <td class="nowrap">
                                <span class="vp-badge vp-badge-{{ $row->approval_status }}">{{ \App\Http\Services\Vendor\VendorInventoryService::APPROVAL_LABELS[$row->approval_status] ?? $row->approval_status }}</span>
                            </td>
                            <td class="nowrap">
                                <form method="POST" action="{{ route('vendor.media.toggle', $row->id) }}" style="margin:0">
                                    @csrf
                                    <button type="submit" role="switch" aria-checked="{{ $row->is_active ? 'true' : 'false' }}" aria-label="Active"
                                        class="vp-switch {{ $row->is_active ? 'on' : '' }}" @disabled($row->approval_status !== 'approved')
                                        title="{{ $row->approval_status !== 'approved' ? 'Available after approval' : ($row->is_active ? 'Deactivate' : 'Activate') }}"><span></span></button>
                                </form>
                            </td>
                            <td class="nowrap">
                                <div style="display:flex;gap:6px">
                                    <a href="{{ route('vendor.media.show', $row->id) }}" class="vp-ib" aria-label="View" title="View"><i class="fa-regular fa-eye"></i></a>
                                    <a href="{{ route('vendor.media.edit', $row->id) }}" class="vp-ib" aria-label="Edit" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    @if ($row->approval_status === 'approved')
                                        <button type="button" class="vp-ib js-availability" data-url="{{ route('vendor.availability.form', $row->id) }}" aria-label="Update availability" title="Update availability"><i class="fa-regular fa-calendar"></i></button>
                                    @endif
                                    <form method="POST" action="{{ route('vendor.media.destroy', $row->id) }}" style="margin:0"
                                        data-confirm="Delete {{ $row->hoarding_code ?: $row->media_title }}?" data-confirm-button="Yes, delete">
                                        @csrf
                                        <button type="submit" class="vp-ib danger" aria-label="Delete" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="15" style="text-align:center;padding:40px;color:#475569">
                            @if (trim($f['q'] ?? '') !== '')
                                No media matches “{{ $f['q'] }}”. <a href="{{ route('vendor.inventory', request()->except(['q', 'page'])) }}">Clear search</a>
                            @else
                                No media found. <a href="{{ route('vendor.media.create') }}">Add your first media</a>.
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="vp-pagination">
            <div style="display:flex;align-items:center;gap:12px" class="vp-muted">
                <span>Showing {{ $media->firstItem() ?? 0 }}–{{ $media->lastItem() ?? 0 }} of {{ $media->total() }} media · Page {{ $media->currentPage() }} of {{ max(1, $media->lastPage()) }}</span>
                <form method="GET" action="{{ route('vendor.inventory') }}" style="margin:0;display:flex;gap:6px;align-items:center">
                    @foreach (request()->except(['per_page', 'page']) as $k => $v)
                        @if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <label for="pp">Show</label>
                    <select id="pp" name="per_page" class="vp-input" style="width:auto;min-height:36px" onchange="this.form.submit()">
                        @foreach ([10, 20, 50, 100] as $n)<option value="{{ $n }}" @selected($perPage == $n)>{{ $n }}</option>@endforeach
                    </select>
                </form>
            </div>
            @if ($media->hasPages())
                {{ $media->onEachSide(1)->links('pagination::bootstrap-4') }}
            @else
                {{-- Laravel hides the pager on a single page; keep it visible (disabled) so paging is always clear. --}}
                <nav aria-label="Pagination">
                    <ul class="pagination" style="margin:0">
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-hidden="true">&lsaquo;</span></li>
                        <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-hidden="true">&rsaquo;</span></li>
                    </ul>
                </nav>
            @endif
        </div>
        </div>{{-- /#invResults --}}
    </section>

    @include('vendor.inventory.partials.availability-modal')
@endsection

@section('scripts')
    <style>
        .vp-row-link { cursor: pointer; }
        .vp-row-link:hover td { background: #FFF7ED; }
    </style>
    <script>
        // Live search: as the vendor types, ask the server for the matching page
        // (same URL, same filters, page 1) and swap in the table, pager, tabs and export link.
        (function () {
            var form = document.getElementById('invSearch');
            var input = document.getElementById('q');
            if (!form || !input) return;
            var timer = null, ctrl = null, last = input.value.trim();

            function run() {
                var term = input.value.trim();
                if (term === last) return;
                last = term;
                var params = new URLSearchParams(new FormData(form));
                params.delete('page');
                if (!term) params.delete('q');
                var url = form.action + (params.toString() ? '?' + params : '');

                if (ctrl) ctrl.abort();
                ctrl = new AbortController();
                document.getElementById('invResults').style.opacity = '.55';
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: ctrl.signal, credentials: 'same-origin' })
                    .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
                    .then(function (html) {
                        var doc = new DOMParser().parseFromString(html, 'text/html');
                        ['invResults', 'invTabs', 'invExport'].forEach(function (id) {
                            var fresh = doc.getElementById(id), cur = document.getElementById(id);
                            if (fresh && cur) cur.replaceWith(fresh);
                        });
                        history.replaceState(null, '', url);
                    })
                    .catch(function (e) { if (e.name !== 'AbortError') window.location.href = url; }) // fall back to a normal load
                    .finally(function () { var el = document.getElementById('invResults'); if (el) el.style.opacity = ''; });
            }

            input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(run, 350); });
            form.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); last = null; run(); });
        })();

        // Clicking anywhere on a row opens that media's details page.
        // Buttons, links, the Active switch and forms in the row keep their own action;
        // Ctrl/Cmd-click or middle-click opens the details in a new tab.
        $(document).on('click auxclick', 'tr.vp-row-link', function (e) {
            if ($(e.target).closest('a, button, input, select, textarea, label, form').length) return;
            if (window.getSelection && String(window.getSelection()).length) return; // user is selecting text
            if (e.type === 'auxclick' && e.button !== 1) return;
            const url = this.dataset.href;
            if (e.ctrlKey || e.metaKey || e.button === 1) window.open(url, '_blank');
            else window.location.href = url;
        });

        // Town list follows the chosen district.
        $('#f-dis').on('change', function () {
            const d = this.value;
            $('#f-town option[data-district]').each(function () {
                $(this).toggle(!d || this.dataset.district === d);
            });
            if (d && $('#f-town option:selected').data('district') && String($('#f-town option:selected').data('district')) !== d) {
                $('#f-town').val('');
            }
        }).trigger('change');
    </script>
@endsection
