{{-- Operations board (PRD 2.8), shared by Super Admin and the vendor panel.

     Super Admin runs the workflow ($canEdit = true: upload photos, share them with
     the user, mark the media removed). The vendor panel shows the same board
     read-only ($canEdit = false) so the vendor sees every photo Brand Adda added.

     Params: $routes ['index', and when $canEdit: 'photo', 'share', 'complete'],
             $baseQuery (extra query kept on every link, e.g. vendor_id),
             $canEdit, $showVendor, $mediaUrl (fn (int $mediaId) => url),
             plus the controller data: $stage, $stageLabels, $counts, $list,
             $pendingMedia, $selected, $photos, $shots, $monitorDate. --}}
@php
    $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
    $stageKeys = array_keys($stageLabels);
    $link = fn (array $q = []) => route($routes['index'], array_filter(array_merge($baseQuery, $q), fn ($v) => $v !== null && $v !== ''));
@endphp

<nav aria-label="Operation stages" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px">
    @foreach ($stageLabels as $key => $label)
        @php $on = $key === $stage; @endphp
        <a href="{{ $link(['stage' => $key, 'q' => request('q')]) }}" @if ($on) aria-current="page" @endif
            style="display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:12px;text-decoration:none;color:#0F172A;border:{{ $on ? '2px solid #F97316' : '1px solid #E5E7EB' }};background:{{ $on ? '#FFF7ED' : '#fff' }}">
            <span style="width:30px;height:30px;border-radius:999px;display:grid;place-items:center;font-size:13px;font-weight:800;background:{{ $on ? '#F97316' : '#0F172A' }};color:{{ $on ? '#0F172A' : '#fff' }}">{{ $loop->iteration }}</span>
            <span style="flex:1;font-size:14px;font-weight:700">{{ $label }}</span>
            <span style="font-size:16px;font-weight:800">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</nav>

@if ($stage === 'pending')
    <section class="vp-card" style="overflow:hidden">
        <div style="padding:16px 20px;border-bottom:1px solid #E5E7EB">
            <h2 class="vp-h2">Pending Media</h2>
            <div class="vp-muted" style="margin-top:4px">Approved media that is Available with no booking coming up. When Brand Adda books it for a user, it moves to Installation.</div>
        </div>
        <div class="vp-table-wrap">
            <table class="vp-table">
                <thead><tr>@if ($showVendor)<th>Vendor</th>@endif<th>Hoarding Code</th><th>Media Title</th><th>Location</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($pendingMedia as $m)
                        <tr>
                            @if ($showVendor)<td class="nowrap">{{ $m->vendor_name ?: '—' }}</td>@endif
                            <td class="nowrap" style="font-weight:700">{{ $m->hoarding_code ?: $m->media_code ?: '—' }}</td>
                            <td>{{ $m->media_title }}</td>
                            <td>{{ $m->area_name }}{{ $m->city_name ? ', ' . $m->city_name : '' }}</td>
                            <td class="nowrap"><a href="{{ $mediaUrl($m->id) }}" class="vp-btn vp-btn-g vp-btn-sm">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $showVendor ? 5 : 4 }}" style="text-align:center;padding:40px;color:#475569">No pending media.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@else
    <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start">
        <section class="vp-card" aria-label="Bookings in {{ $stageLabels[$stage] }}" style="flex:1 1 280px;padding:8px">
            <div class="vp-muted" style="padding:10px 12px;font-weight:700">{{ $stageLabels[$stage] }} · {{ $list->total() }} booking(s)</div>
            @forelse ($list as $op)
                @php $sel = $selected && $selected->id === $op->id; @endphp
                <a href="{{ $link(['stage' => $stage, 'op' => $op->id, 'q' => request('q'), 'page' => $list->currentPage() > 1 ? $list->currentPage() : null]) }}"
                    style="display:block;padding:14px;border-radius:10px;text-decoration:none;color:#0F172A;margin-top:4px;{{ $sel ? 'background:#FFF7ED;border:1px solid #FDBA74' : 'border:1px solid transparent' }}">
                    <div style="display:flex;justify-content:space-between;gap:8px;font-size:14px;font-weight:800"><span>{{ $op->order_no }}</span></div>
                    <div style="font-size:13px;color:#334155;margin-top:6px">{{ $op->hoarding_code ?: $op->media_code }} · {{ $op->media_title }}</div>
                    @if ($showVendor && $op->vendor_name)<div style="font-size:12px;color:#C2410C;font-weight:600;margin-top:2px">{{ $op->vendor_name }}</div>@endif
                    <div class="vp-muted" style="font-size:12px;margin-top:2px">{{ $fmt($op->from_date) }} – {{ $fmt($op->to_date) }}</div>
                </a>
            @empty
                <p class="vp-muted" style="padding:10px 12px;margin:0">Nothing in this stage.</p>
            @endforelse
            @if ($list->hasPages())
                <div style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 4px 4px">
                    <span class="vp-muted" style="font-size:12px">{{ $list->firstItem() }}–{{ $list->lastItem() }} of {{ $list->total() }}</span>
                    {{ $list->onEachSide(0)->links('pagination::simple-bootstrap-4') }}
                </div>
            @endif
        </section>

        @if ($selected)
            @php
                $current = $selected->effective_stage;
                $currentIndex = array_search($current, $stageKeys);
                $stepMeta = [
                    'pending' => 'Booked ' . $fmt($selected->created_at),
                    'installation' => $selected->installation_shared_at ? 'Shared ' . $fmt($selected->installation_shared_at) : 'Due ' . $fmt($selected->from_date),
                    'monitoring' => $current === 'monitoring' ? 'In progress' : ($currentIndex > 2 ? 'Done' : '—'),
                    'end_date' => $selected->end_date_shared_at ? 'Shared ' . $fmt($selected->end_date_shared_at) : 'Due ' . $fmt($selected->to_date),
                    'completed' => $selected->completed_at ? $fmt($selected->completed_at) : '—',
                ];
                $panelStage = in_array($current, ['installation', 'monitoring', 'end_date']) ? $current : null;
                $dateKey = $panelStage === 'monitoring' ? $monitorDate : '_';
                $set = $panelStage ? ($photos[$panelStage][$dateKey] ?? []) : [];
                $setShared = collect($set)->every(fn($p) => $p->shared_at) && count($set) === 3;
            @endphp
            <div style="flex:999 1 620px;min-width:0;display:flex;flex-direction:column;gap:20px">
                <section class="vp-card vp-card-pad">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px">
                        <div><div class="vp-muted" style="font-size:12px;font-weight:600">Booking No. (Order No.)</div><div style="font-size:15px;font-weight:700;margin-top:4px">{{ $selected->order_no }}</div></div>
                        <div><div class="vp-muted" style="font-size:12px;font-weight:600">Hoarding Code</div><div style="font-size:15px;font-weight:700;margin-top:4px">{{ $selected->hoarding_code ?: $selected->media_code }} · {{ $selected->media_title }}</div></div>
                        @if ($showVendor)
                            <div><div class="vp-muted" style="font-size:12px;font-weight:600">Vendor</div><div style="font-size:15px;font-weight:700;margin-top:4px">{{ $selected->vendor_name ?: '—' }}</div></div>
                        @endif
                        <div><div class="vp-muted" style="font-size:12px;font-weight:600">User</div><div style="font-size:15px;font-weight:700;margin-top:4px">{{ $selected->user_name ?: '—' }}</div></div>
                        <div><div class="vp-muted" style="font-size:12px;font-weight:600">Current Status</div><div style="margin-top:4px"><span class="vp-badge vp-badge-pending">{{ $stageLabels[$current] }}</span></div></div>
                    </div>
                    <ol aria-label="Operations workflow" style="list-style:none;margin:22px 0 0;padding:0;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px">
                        @foreach ($stageLabels as $key => $label)
                            @php $i = $loop->index; $bar = $i < $currentIndex || $current === 'completed' ? '#16A34A' : ($i === $currentIndex ? '#F97316' : '#E2E8F0'); @endphp
                            <li style="display:flex;flex-direction:column;gap:8px">
                                <span style="height:6px;border-radius:999px;background:{{ $bar }}"></span>
                                <span style="font-size:12px;font-weight:700;color:{{ $i <= $currentIndex ? '#0F172A' : '#475569' }}">{{ $loop->iteration }}. {{ $label }}</span>
                                <span class="vp-muted" style="font-size:11px">{{ $stepMeta[$key] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </section>

                <section class="vp-card vp-card-pad">
                    <h2 class="vp-h2" style="margin-bottom:14px">Booking Details</h2>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px">
                        @php
                            $bookingRows = ['Name' => $selected->user_name, 'Email ID' => $selected->user_email, 'Mobile Number' => $selected->user_mobile, 'Booking Start Date' => $fmt($selected->from_date), 'Booking End Date' => $fmt($selected->to_date)];
                            // Booked through Update Availability (no website / Admin Booking order, no user).
                            if (!empty($selected->booked_by)) {
                                $bookingRows = ['Booked by' => ($selected->booked_by === 'admin' ? 'Brand Adda' : 'Vendor') . ' (booked dates, no user)'] + $bookingRows;
                            }
                        @endphp
                        @foreach ($bookingRows as $k => $v)
                            <div><div class="vp-muted" style="font-size:12px;font-weight:600">{{ $k }}</div><div style="font-size:15px;font-weight:700;margin-top:4px;word-break:break-word">{{ $v ?: '—' }}</div></div>
                        @endforeach
                    </div>
                </section>

                @if ($panelStage)
                    <section class="vp-card vp-card-pad">
                        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;margin-bottom:16px">
                            <div>
                                <h2 class="vp-h2">{{ $stageLabels[$panelStage] }}</h2>
                                <div class="vp-muted" style="margin-top:4px">
                                    @if ($panelStage === 'installation')
                                        Installation date (booking start): <strong style="color:#0F172A">{{ $fmt($selected->from_date) }}</strong>.{{ $canEdit ? ' Upload after the media is installed.' : '' }}
                                    @elseif ($panelStage === 'monitoring')
                                        Photos for the date the user requested.
                                    @else
                                        End date: <strong style="color:#0F172A">{{ $fmt($selected->to_date) }}</strong>.{{ $canEdit ? ' Share these before removing the flex.' : '' }}
                                    @endif
                                    @unless ($canEdit)<br>Photos are uploaded and shared by Brand Adda.@endunless
                                </div>
                            </div>
                            @if ($panelStage === 'monitoring')
                                <form method="GET" action="{{ route($routes['index']) }}" style="display:flex;gap:8px;align-items:flex-end;margin:0">
                                    @foreach ($baseQuery as $bk => $bv)<input type="hidden" name="{{ $bk }}" value="{{ $bv }}">@endforeach
                                    <input type="hidden" name="stage" value="{{ $stage }}"><input type="hidden" name="op" value="{{ $selected->id }}">
                                    <div><label class="vp-label" for="mdate">Requested date</label>
                                        <input id="mdate" type="date" name="date" class="vp-input" value="{{ $monitorDate }}"
                                            min="{{ \Carbon\Carbon::parse($selected->from_date)->toDateString() }}" max="{{ \Carbon\Carbon::parse($selected->to_date)->toDateString() }}"></div>
                                    <button type="submit" class="vp-btn vp-btn-g">Open date</button>
                                </form>
                            @endif
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
                            @foreach ($shots as $shotKey => $shotLabel)
                                @php $p = $set[$shotKey] ?? null; @endphp
                                <div style="display:flex;flex-direction:column;gap:8px">
                                    <div style="font-size:13px;font-weight:700">{{ $shotLabel }}</div>
                                    @if ($p)
                                        <a href="{{ $p->url }}" target="_blank" rel="noopener"><img src="{{ $p->url }}" alt="{{ $shotLabel }}" class="vp-thumb" style="width:100%;height:150px"></a>
                                        <div style="display:flex;justify-content:space-between;font-size:12px;color:#475569;gap:8px">
                                            <span>{{ $p->shared_at ? 'Shared ' . $p->shared_at->format('d M, h:i A') : 'Uploaded ' . $p->updated_at->format('d M, h:i A') }}</span>
                                            @if ($canEdit && !$p->shared_at)
                                                <label style="font-weight:700;color:#C2410C;cursor:pointer;margin:0">Replace
                                                    <input type="file" accept="image/*" class="sr-only js-photo" data-form="ph-{{ $shotKey }}"></label>
                                            @endif
                                        </div>
                                    @elseif ($canEdit)
                                        <label class="vp-drop" style="height:150px;margin:0">
                                            <i class="fa fa-upload" style="font-size:22px"></i>Upload Photo
                                            <input type="file" accept="image/*" class="sr-only js-photo" data-form="ph-{{ $shotKey }}">
                                        </label>
                                        <div style="font-size:12px;color:#B91C1C;font-weight:600">Required before sharing</div>
                                    @else
                                        <div style="height:150px;display:grid;place-items:center;text-align:center;padding:10px;border:1px dashed #CBD5E1;border-radius:10px;background:#F8FAFC;font-size:13px;color:#64748B">
                                            Not uploaded yet</div>
                                    @endif
                                    @if ($canEdit)
                                        <form id="ph-{{ $shotKey }}" method="POST" action="{{ route($routes['photo'], $selected->id) }}" enctype="multipart/form-data" style="display:none">
                                            @csrf
                                            @foreach ($baseQuery as $bk => $bv)<input type="hidden" name="{{ $bk }}" value="{{ $bv }}">@endforeach
                                            <input type="hidden" name="stage" value="{{ $panelStage }}">
                                            <input type="hidden" name="return_stage" value="{{ $stage }}">
                                            <input type="hidden" name="shot_type" value="{{ $shotKey }}">
                                            @if ($panelStage === 'monitoring')<input type="hidden" name="photo_date" value="{{ $monitorDate }}">@endif
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-top:16px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;padding:12px 14px;border-radius:10px;background:#F8FAFC">
                            @php $noUser = empty($selected->user_email) && !empty($selected->booked_by); @endphp
                            <span style="font-size:13px;color:#334155">
                                @if ($setShared)
                                    {!! $noUser ? 'Photos recorded.' : 'Shared with <strong>' . e($selected->user_email) . '</strong>.' !!}
                                @elseif ($canEdit && $noUser)
                                    This booking has no user to email. Saving moves it to the next stage and keeps the photos in its history.
                                @elseif ($canEdit)
                                    Photos will be emailed to <strong>{{ $selected->user_email ?: 'the user' }}</strong> and kept in this booking’s history.
                                @else
                                    {{ count($set) }} of 3 photos uploaded · not shared with the user yet.
                                @endif
                            </span>
                            @if ($canEdit)
                                <form method="POST" action="{{ route($routes['share'], $selected->id) }}" style="margin:0"
                                    data-confirm="{{ $noUser ? 'Save these photos and move the booking to the next stage?' : 'Email these photos to ' . $selected->user_email . '?' }}" data-confirm-button="{{ $noUser ? 'Yes, save' : 'Yes, share' }}">
                                    @csrf
                                    @foreach ($baseQuery as $bk => $bv)<input type="hidden" name="{{ $bk }}" value="{{ $bv }}">@endforeach
                                    <input type="hidden" name="stage" value="{{ $panelStage }}">
                                    @if ($panelStage === 'monitoring')<input type="hidden" name="photo_date" value="{{ $monitorDate }}">@endif
                                    <button type="submit" class="vp-btn vp-btn-o" @disabled(count($set) < 3 || $setShared)>
                                        @if ($noUser)<i class="fa fa-check"></i>Save &amp; Move On @else<i class="fa fa-envelope"></i>Share with User @endif</button>
                                </form>
                            @endif
                        </div>
                    </section>
                @endif

                @if ($canEdit && $current === 'end_date' && $selected->end_date_shared_at)
                    <section class="vp-card vp-card-pad">
                        <h2 class="vp-h2" style="margin-bottom:6px">Completed / Media Remove</h2>
                        <p class="vp-muted" style="margin:0 0 14px">End-date photos were shared on {{ $fmt($selected->end_date_shared_at) }}. Remove the flex, then record it here.</p>
                        <form method="POST" action="{{ route($routes['complete'], $selected->id) }}" style="display:flex;flex-direction:column;gap:12px">
                            @csrf
                            @foreach ($baseQuery as $bk => $bv)<input type="hidden" name="{{ $bk }}" value="{{ $bv }}">@endforeach
                            <label style="display:flex;gap:10px;align-items:center;font-size:14px;margin:0">
                                <input type="checkbox" name="confirm" value="1" required style="width:18px;height:18px;accent-color:#F97316">
                                Installation, monitoring and end-date photos are done and the flex / advertisement has been removed.
                            </label>
                            <div><label class="vp-label" for="rnote">Note (optional)</label><textarea id="rnote" name="removal_note" class="vp-input" maxlength="500"></textarea></div>
                            <div><button type="submit" class="vp-btn vp-btn-n">Mark Media Removed</button></div>
                        </form>
                    </section>
                @endif

                <section class="vp-card vp-card-pad">
                    <h2 class="vp-h2" style="margin-bottom:14px">History</h2>
                    @php $hasHistory = false; @endphp
                    @foreach (['installation' => 'Installation photos', 'monitoring' => 'Monitoring photos', 'end_date' => 'End-date photos'] as $hs => $hl)
                        @foreach ($photos[$hs] ?? [] as $dk => $shotsSet)
                            @php $hasHistory = true; $first = collect($shotsSet)->first(); @endphp
                            <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;padding:12px 0;border-top:1px solid #F1F5F9">
                                <div style="min-width:220px;flex:1">
                                    <div style="font-size:14px;font-weight:700">{{ $hl }}{{ $dk !== '_' ? ' · ' . $fmt($dk) : '' }}</div>
                                    <div class="vp-muted" style="font-size:12px;margin-top:2px">
                                        {{ count($shotsSet) }} of 3 uploaded{{ $first && $first->shared_at ? ' · shared ' . $first->shared_at->format('d M Y, h:i A') : ' · not shared yet' }}</div>
                                </div>
                                <div style="display:flex;gap:8px">
                                    @foreach ($shotsSet as $sk => $sp)
                                        <a href="{{ $sp->url }}" target="_blank" rel="noopener" title="{{ $shots[$sk] }}"><img src="{{ $sp->url }}" alt="{{ $shots[$sk] }}" class="vp-thumb" style="width:88px;height:60px"></a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                    @if ($selected->completed_at)
                        <div style="padding:12px 0;border-top:1px solid #F1F5F9;font-size:14px">
                            <strong>Media removed</strong> · {{ \Carbon\Carbon::parse($selected->completed_at)->format('d M Y, h:i A') }}{{ $selected->removal_note ? ' · ' . $selected->removal_note : '' }}
                        </div>
                        @php $hasHistory = true; @endphp
                    @endif
                    @unless ($hasHistory)<p class="vp-muted" style="margin:0">No photos uploaded yet.</p>@endunless
                </section>
            </div>
        @else
            <section class="vp-card vp-card-pad" style="flex:999 1 620px">
                <p class="vp-muted" style="margin:0">Select a booking to see its details.</p>
            </section>
        @endif
    </div>
@endif

@if ($canEdit)
    <script>
        // A file picked in a photo slot is moved into that slot's hidden form and sent.
        document.addEventListener('change', function (e) {
            const input = e.target;
            if (!input.classList || !input.classList.contains('js-photo') || !input.files.length) return;
            const form = document.getElementById(input.dataset.form);
            input.name = 'photo';
            input.classList.remove('js-photo');
            form.appendChild(input);
            form.submit();
        });
    </script>
@endif
