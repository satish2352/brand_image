@extends('website.layout')

@section('title', 'My Cart')

@section('content')

<!-- Campaign Modal -->
<div class="modal fade" id="campaignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="{{ route('campaign.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Create Campaign</h5>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Campaign Name</label>
                        <input type="text"
                               name="campaign_name"
                               class="form-control"
                               placeholder="Enter campaign name"
                               required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            class="btn btn-success">
                        Save Campaign
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection
