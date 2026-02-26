@extends('superadm.layout.master')

@section('content')
    <style>
        .info-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .info-card-header {
            background: #00929c;
            color: #fff;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 16px;
        }

        .info-card-body {
            padding: 20px;
        }

        .info-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .info-col {
            width: 50%;
            padding: 6px 0;
        }

        .info-label {
            font-size: 13px;
            color: #777;
        }

        .info-value {
            font-size: 15px;
            font-weight: 500;
            color: #222;
        }

        @media(max-width:768px) {
            .info-col {
                width: 100%;
            }
        }

        .image-card img {
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            transition: 0.3s;
        }

        .image-card img:hover {
            transform: scale(1.05);
        }
    </style>

    <a href="{{ route('contact-us.list') }}" class="btn btn-outline-secondary mb-4">
        ← Back to List
    </a>

    <div class="info-card">
        <div class="info-card-header">Basic Details</div>
        <div class="info-card-body">

            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">Category</div>
                    <div class="info-value">{{ $contact->category_name ?? '-' }}</div>
                </div>

                <div class="info-col">
                    <div class="info-label">Vendor Name</div>
                    <div class="info-value">{{ $contact->vendor_name ?? '-' }}</div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">Full Name</div>
                    <div class="info-value">{{ $contact->full_name ?? '-' }}</div>
                </div>

                <div class="info-col">
                    <div class="info-label">Mobile</div>
                    <div class="info-value">{{ $contact->mobile_no ?? '-' }}</div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">Email</div>
                    <div class="info-value">{{ $contact->email ?? '-' }}</div>
                </div>

                <div class="info-col">
                    <div class="info-label">Budget</div>
                    <div class="info-value">{{ $contact->budget ?? '-' }}</div>
                </div>
            </div>

        </div>
    </div>

    <div class="info-card">
        <div class="info-card-header">Location Details</div>
        <div class="info-card-body">

            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">State</div>
                    <div class="info-value">{{ $contact->state_name ?? '-' }}</div>
                </div>

                <div class="info-col">
                    <div class="info-label">District</div>
                    <div class="info-value">{{ $contact->district_name ?? '-' }}</div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">City</div>
                    <div class="info-value">{{ $contact->city_name ?? '-' }}</div>
                </div>

                <div class="info-col">
                    <div class="info-label">Area</div>
                    <div class="info-value">{{ $contact->area_name ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="info-card">
        <div class="info-card-header">Dimensions</div>
        <div class="info-card-body">

            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">Width (ft)</div>
                    <div class="info-value">{{ $contact->width ?? '-' }}</div>
                </div>

                <div class="info-col">
                    <div class="info-label">Height (ft)</div>
                    <div class="info-value">{{ $contact->height ?? '-' }}</div>
                </div>
            </div>

        </div>
    </div>

    <div class="info-card">
        <div class="info-card-header">Requirements / Specifications</div>
        <div class="info-card-body">
            <p class="mb-0">{{ $contact->remark ?? '-' }}</p>
        </div>
    </div>

    <div class="info-card">
        <div class="info-card-header">Images</div>
        <div class="info-card-body">
            <div class="row">
                @forelse($contact->images ?? [] as $img)
                    <div class="col-md-3 col-sm-6 mb-3 image-card">
                        <img src="{{ config('fileConstants.IMAGE_VIEW') . $img }}" class="img-fluid">
                    </div>
                @empty
                    <p class="text-muted">No images available</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
