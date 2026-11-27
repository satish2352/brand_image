{{-- Selectable media table for Vendor Inventory & Access. --}}
<div class="table-responsive vi-scroll">
    <table class="table table-hover vi-table">
        <thead>
            <tr>
                <th style="width:40px"><input type="checkbox" class="vi-check js-pick-all" aria-label="Select all" @disabled($rows->isEmpty())></th>
                <th class="text-center" style="width:70px">Sr. No.</th>
                <th>Code</th>
                <th>Media Title</th>
                <th>Location</th>
                @if ($showVendor)<th>Current Vendor</th>@endif
                <th>Approval</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                @php
                    $approvalClass = ['approved' => 'vi-approved', 'pending' => 'vi-pending', 'rejected' => 'vi-rejected'][$r->approval_status] ?? 'vi-grey';
                @endphp
                <tr data-pick style="cursor:pointer">
                    <td><input type="checkbox" class="vi-check" name="media_ids[]" value="{{ $r->id }}" aria-label="Select {{ $r->hoarding_code ?: $r->media_title }}"></td>
                    <td class="text-center vi-muted">{{ $loop->iteration }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.vendor.approvals.show', $r->id) }}" title="Open details"><strong>{{ $r->hoarding_code ?: '—' }}</strong></a>
                    </td>
                    <td style="min-width:140px">{{ $r->media_title ?: '—' }}
                        @if ($r->category_name)<div class="vi-muted">{{ $r->category_name }}</div>@endif
                    </td>
                    <td style="min-width:130px">{{ $r->area_name }}@if ($r->city_name)<div class="vi-muted">{{ $r->city_name }}</div>@endif</td>
                    @if ($showVendor)
                        <td class="text-nowrap">{!! $r->vendor_name ? e($r->vendor_name) : '<span class="vi-badge vi-grey">Unassigned</span>' !!}</td>
                    @endif
                    <td><span class="vi-badge {{ $approvalClass }}">{{ ucfirst($r->approval_status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="{{ $showVendor ? 7 : 6 }}" class="text-center vi-muted py-4">No media here.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
