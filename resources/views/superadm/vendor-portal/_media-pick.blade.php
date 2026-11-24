<div class="table-responsive" style="max-height:520px;overflow:auto">
    <table class="table table-sm table-bordered vpa-table mb-0">
        <thead><tr>
            <th style="width:34px"><input type="checkbox" class="js-pick-all" aria-label="Select all"></th>
            <th>Code</th><th>Title</th><th>Location</th>@if ($showVendor)<th>Current Vendor</th>@endif<th>Approval</th>
        </tr></thead>
        <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td><input type="checkbox" name="media_ids[]" value="{{ $r->id }}" aria-label="Select {{ $r->hoarding_code ?: $r->media_title }}"></td>
                    <td class="text-nowrap">{{ $r->hoarding_code ?: '—' }}</td>
                    <td>{{ $r->media_title }}</td>
                    <td>{{ $r->area_name }}{{ $r->city_name ? ', ' . $r->city_name : '' }}</td>
                    @if ($showVendor)<td>{{ $r->vendor_name ?: 'Unassigned' }}</td>@endif
                    <td><span class="vpa-badge vpa-{{ $r->approval_status }}">{{ ucfirst($r->approval_status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="{{ $showVendor ? 6 : 5 }}" class="text-center text-muted py-3">None.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
