@extends('superadm.layout.master')

@section('content')
    @php
        // Ordered the way the team reads it: who, then what, then the extras.
        $rows = [
            'Received'               => $requirement->created_at?->format('d M Y, h:i A'),
            'Source'                 => $requirement->source === 'search_expired' ? 'Search session ended' : 'Direct',
            'Registered User'        => $requirement->user?->name ?? 'Guest (not signed in)',
            'Full Name'              => $requirement->full_name,
            'Mobile'                 => $requirement->mobile_no,
            'Email'                  => $requirement->email,
            'Campaign Name'          => $requirement->campaign_name,
            'City'                   => $requirement->city,
            'Area / Location'        => $requirement->area_location,
            'Media Type'             => $requirement->media_type,
            'Campaign Start'         => $requirement->campaign_start_date?->format('d-m-Y'),
            'Campaign End'           => $requirement->campaign_end_date?->format('d-m-Y'),
            'Campaign Duration'      => $requirement->campaign_duration,
            'Required No. of Media'  => $requirement->required_media_count,
            'Approximate Budget'     => $requirement->approx_budget,
            'Target Audience'        => $requirement->target_audience,
            'Preferred Location'     => $requirement->preferred_location,
            'Preferred Media Size'   => $requirement->preferred_media_size,
            'Additional Comments'    => $requirement->additional_comments,
        ];
    @endphp

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Requirement #{{ $requirement->id }}</h4>
                        <a href="{{ route('requirements.list') }}" class="btn btn-sm btn-secondary">Back</a>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <table class="table table-bordered mb-0">
                        <tbody>
                            @foreach ($rows as $label => $value)
                                <tr>
                                    <th style="width:220px;">{{ $label }}</th>
                                    <td>{{ $value ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Working Status</h5>

                    <form method="POST" action="{{ route('requirements.status') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $requirement->id }}">

                        <select name="status" class="form-select mb-3">
                            <option value="new" {{ $requirement->status === 'new' ? 'selected' : '' }}>New</option>
                            <option value="in_progress" {{ $requirement->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="closed" {{ $requirement->status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>

                        <button class="btn btn-primary w-100">Update Status</button>
                    </form>

                    @if ($requirement->mobile_no)
                        <hr>
                        <a href="tel:{{ $requirement->mobile_no }}" class="btn btn-outline-secondary w-100 mb-2">
                            Call {{ $requirement->mobile_no }}
                        </a>
                    @endif
                    @if ($requirement->email)
                        <a href="mailto:{{ $requirement->email }}" class="btn btn-outline-secondary w-100">
                            Email {{ $requirement->email }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
