@php
    // $cart = session()->get('cart', []);
@endphp

<div class="container my-5">
    <div class="row">

        @foreach ($mediaList as $media)
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4 media-card">

                <div class="media-card">

                    <img src="{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}" class="img-fluid rounded"
                        style="height:150px;width:100%;object-fit:cover">

                    <div class="media-body">

                        <h6>{{ $media->area_name }}, {{ $media->city_name }}</h6>

                        <small class="text-muted d-block">
                            {{ $media->media_title ?? $media->category_name }}
                        </small>

                        <p class="mt-2">
                            ₹ {{ number_format($media->per_day_price, 2) }}
                            <small class="text-muted">/ day</small>
                        </p>

                        <div class="media-actions mt-2 text-end">

                            @if ($media->category_name === 'Hoardings/Billboards')
                                @if ($media->is_booked)
                                    <span class="badge bg-danger w-100">Booked</span>
                                @else
                                    @auth('website')
                                        <a href="{{ route('cart.add', base64_encode($media->id)) }}"
                                            class="btn btn-sm btn-success">
                                            Add to Cart
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                                            data-bs-target="#authModal">
                                            Add to Cart
                                        </button>
                                    @endauth
                                @endif
                            @else
                                <a href="{{ route('contact.create') }}#contact-form" class="btn btn-sm btn-warning">
                                    Contact Us
                                </a>
                            @endif

                        </div>
                    </div>
                </div>

            </div>
        @endforeach


    </div>
</div>

<div class="modal fade" id="dateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="addToCartForm">
            @csrf
            <input type="hidden" name="media_id" id="media_id">

            <div class="modal-content">
                <div class="modal-header">
                    <h5>Select Media Dates</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div id="modal-error" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        Add to Cart
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openDateModal(mediaId) {
        document.getElementById('media_id').value = mediaId;
        const modal = new bootstrap.Modal(document.getElementById('dateModal'));
        modal.show();
    }

    document.getElementById('addToCartForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const errorBox = document.getElementById('modal-error');
        errorBox.classList.add('d-none');
        errorBox.innerText = '';

        fetch("{{ route('cart.add.with.date') }}", {
                method: 'POST',
                credentials: 'same-origin', //  IMPORTANT FIX
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
                    'Accept': 'application/json'
                },
                body: new FormData(form)
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) throw data;
                window.location.href = "{{ route('cart.index') }}";
            })
            .catch(err => {
                errorBox.classList.remove('d-none');
                errorBox.innerText = err.message || 'Something went wrong';
            });
    });
</script>
