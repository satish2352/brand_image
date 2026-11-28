@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Search Sessions — {{ $user->name }}</h4>
                        <a href="{{ route('search-access.users') }}" class="btn btn-sm btn-secondary">Back</a>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                        </div>
                    @endif

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Started</th>
                                <th>Expires</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Granted By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sessions as $s)
                                <tr>
                                    <td>{{ $s->id }}</td>
                                    <td>
                                        @if ($s->type === \App\Models\SearchSession::TYPE_ADMIN_GRANTED)
                                            <span class="badge bg-warning">Admin granted</span>
                                        @else
                                            <span class="badge bg-info">Search trial</span>
                                        @endif
                                    </td>
                                    <td>{{ $s->started_at?->format('d M Y, h:i A') }}</td>
                                    <td>{{ $s->expires_at?->format('d M Y, h:i A') }}</td>
                                    <td>
                                        {{ $s->started_at && $s->expires_at
                                            ? round($s->started_at->diffInMinutes($s->expires_at)) . ' min'
                                            : '—' }}
                                    </td>
                                    <td>
                                        @if ($s->isActive())
                                            <span class="badge bg-success">Active</span>
                                        @elseif ($s->status === \App\Models\SearchSession::STATUS_REVOKED)
                                            <span class="badge bg-danger">Revoked</span>
                                        @else
                                            <span class="badge bg-secondary">Expired</span>
                                        @endif
                                    </td>
                                    {{-- Automatic trials have no grantor; that is the point of the column. --}}
                                    <td>{{ $s->created_by ? ($grantorNames[$s->created_by] ?? 'Admin #' . $s->created_by) : 'Automatic' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        This account has never opened a search session.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Grant Access</h5>
                    <p class="text-muted" style="font-size:13px;">
                        Opens a new window starting now, recorded against your account.
                        Any window already running is closed first, so the user never
                        holds two at once.
                    </p>
                    <form action="{{ route('search-access.grant') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <button class="btn btn-primary w-100">Grant Search Access</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Requirements Submitted</h5>
                    @forelse ($requirements as $r)
                        <div class="mb-2 pb-2 border-bottom">
                            <a href="{{ route('requirements.view', base64_encode($r->id)) }}">
                                {{ $r->campaign_name ?: 'Requirement #' . $r->id }}
                            </a>
                            <div class="text-muted" style="font-size:12px;">
                                {{ $r->created_at?->format('d M Y') }} · {{ $r->statusLabel() }}
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0" style="font-size:13px;">None yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
