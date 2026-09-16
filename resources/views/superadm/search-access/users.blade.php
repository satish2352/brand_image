@extends('superadm.layout.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h4 class="mb-1">User Search Access</h4>
                            <p class="text-muted mb-0" style="font-size:13px;">
                                The {{ $searchMinutes }}-minute trial is consumed once per account and does
                                not reopen on its own — not the next day, not from another browser.
                                Granting is the only way to give somebody another window.
                            </p>
                        </div>
                        <a href="{{ route('settings.portal-access') }}" class="btn btn-sm btn-secondary">
                            Access Settings
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="GET" class="row g-2 mb-3">
                        <div class="col-md-4">
                            <input type="text" name="q" class="form-control" value="{{ $search }}"
                                placeholder="Search name, email or mobile">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary">Search</button>
                            @if ($search !== '')
                                <a href="{{ route('search-access.users') }}" class="btn btn-light">Clear</a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Sr.No.</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Search Trial</th>
                                    <th>Sessions</th>
                                    <th>Requirements</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $key => $user)
                                    @php
                                        $active = $activeByUser[$user->id] ?? null;
                                        $used = $trialUsed->has($user->id);
                                    @endphp
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->mobile_number }}</td>
                                        <td>
                                            @if ($active)
                                                <span class="badge bg-success">Searching now</span>
                                            @elseif ($used)
                                                <span class="badge bg-secondary">Used</span>
                                            @else
                                                <span class="badge bg-info">Not used</span>
                                            @endif
                                        </td>
                                        <td>{{ $sessionCounts[$user->id] ?? 0 }}</td>
                                        <td>{{ $requirementCounts[$user->id] ?? 0 }}</td>
                                        <td>
                                            <a href="{{ route('search-access.history', base64_encode($user->id)) }}"
                                                class="btn btn-sm btn-info mb-2" title="Session history">
                                                <i class="fa fa-eye"></i>
                                            </a>

                                            @if ($active)
                                                <form action="{{ route('search-access.revoke') }}" method="POST"
                                                    class="d-inline-block">
                                                    @csrf
                                                    <input type="hidden" name="session_id" value="{{ $active->id }}">
                                                    <button class="btn btn-sm btn-outline-danger mb-2">
                                                        Revoke
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('search-access.grant') }}" method="POST"
                                                    class="d-inline-block">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                    <button class="btn btn-sm btn-primary mb-2">
                                                        Grant {{ $searchMinutes }}-Minute Search Access
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No users found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($users->count() >= 200)
                        <p class="text-muted mb-0" style="font-size:12px;">
                            Showing the 200 most recent accounts. Use the search box to find others.
                        </p>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
