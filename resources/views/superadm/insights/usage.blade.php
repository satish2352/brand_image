@extends('superadm.layout.master')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-1">Location Insights — API Usage</h4>
            <p class="text-muted mb-4" style="font-size:13px;">
                Credits are spent only by <strong>Refresh Insights</strong> (admins) and the hourly
                <code>insights:enrich</code> batch — never by someone opening a media page. Hoardings at the same spot
                share one saved result. Active provider: <strong>{{ ucfirst($active) }}</strong>
                (<code>INSIGHTS_PLACES_PROVIDER</code>).
            </p>

            <div class="row mb-3">
                @foreach ($providers as $name => $q)
                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3 h-100 {{ $name === $active ? 'border-warning' : '' }}">
                            <div class="d-flex justify-content-between">
                                <strong>{{ ucfirst($name) }}</strong>
                                @if ($name === $active)
                                    <span class="badge badge-warning">Active</span>
                                @endif
                            </div>

                            @unless ($q['configured'])
                                <div class="text-muted small mt-2">API key not set in the server's .env.</div>
                            @endunless

                            <div class="mt-2" style="font-size:26px;font-weight:700;">
                                {{ $q['used'] }} <small class="text-muted" style="font-size:14px;">/ {{ $q['limit'] }} credits
                                    {{ $q['period'] === 'day' ? 'today' : 'this month' }}</small>
                            </div>
                            <div class="small">
                                Remaining: <strong style="color:#F97316;">{{ $q['remaining'] }}</strong>
                                · at most {{ $q['max_cost'] }} credit{{ $q['max_cost'] === 1 ? '' : 's' }} per request
                            </div>

                            @if ($q['paused'])
                                <div class="text-danger small mt-1">Paused for a few minutes after the provider refused a request (HTTP 429).</div>
                            @elseif ($q['configured'] && $q['remaining'] < $q['max_cost'])
                                <div class="text-danger small mt-1">Limit reached — refreshes use saved data until the {{ $q['period'] === 'day' ? 'next day' : 'next month' }}.</div>
                            @endif

                            @if (!empty($q['account']))
                                <div class="small text-muted mt-2">
                                    SerpApi's own count: {{ $q['account']['this_month_usage'] ?? '—' }} used,
                                    {{ $q['account']['total_searches_left'] ?? '—' }} left
                                    @if (!empty($q['account']['plan_renewal_date']))
                                        (renews {{ $q['account']['plan_renewal_date'] }})
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="small text-muted mb-4">
                <strong>How credits are counted.</strong> Geoapify: 1 credit per 20 places returned (an empty result is
                counted as 1); budget resets each calendar day. SerpApi: 1 per successful search; budget resets each
                calendar month (SerpApi's own month follows its plan renewal date, shown above). Failed requests count 0.
            </p>

            <h5 class="mb-2">Coverage</h5>
            <table class="table table-sm table-bordered mb-4" style="max-width:640px;">
                <tbody>
                    <tr><th style="width:60%;">Active media</th><td>{{ number_format($coverage['media']) }}</td></tr>
                    <tr><th>With nearby places fetched</th><td>{{ number_format($coverage['enriched']) }}</td></tr>
                    <tr><th>Due for a refresh (new, moved or expired)</th><td>{{ number_format($coverage['due']) }}</td></tr>
                    <tr><th>Saved locations (shared by nearby media)</th><td>{{ number_format($coverage['locations']) }}</td></tr>
                </tbody>
            </table>

            <h5 class="mb-2">Today by outcome</h5>
            <table class="table table-sm table-bordered mb-4" style="max-width:640px;">
                <thead>
                    <tr><th>Provider</th><th>Outcome</th><th>Requests</th><th>Credits</th></tr>
                </thead>
                <tbody>
                    @forelse ($today as $row)
                        <tr>
                            <td>{{ ucfirst($row->provider) }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($row->status)) }}</td>
                            <td>{{ $row->requests }}</td>
                            <td>{{ (int) $row->credits }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No requests today.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h5 class="mb-2">Request log</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Provider</th>
                            <th>Media ID</th>
                            <th>Source</th>
                            <th>Outcome</th>
                            <th>HTTP</th>
                            <th>Places</th>
                            <th>Credits</th>
                            <th>Time</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->requested_at?->format('d M Y H:i') }}</td>
                                <td>{{ ucfirst($log->provider) }}</td>
                                <td>
                                    @if ($log->media_id)
                                        <a href="{{ route('website.media-details', base64_encode($log->media_id)) }}" target="_blank">{{ $log->media_id }}</a>
                                    @endif
                                </td>
                                <td>{{ ucfirst($log->source) }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($log->status)) }}</td>
                                <td>{{ $log->http_status }}</td>
                                <td>{{ $log->result_count }}</td>
                                <td>{{ $log->credits_consumed }}</td>
                                <td>{{ $log->duration_ms !== null ? $log->duration_ms . ' ms' : '' }}</td>
                                <td class="small">{{ $log->error_message }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-muted">No API requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $logs->links() }}
        </div>
    </div>
@endsection
