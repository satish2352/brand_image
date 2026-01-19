  <style>
      /* CARD */
      .media-card {
          background: #fff;
          border-radius: 12px;
          overflow: hidden;
          box-shadow: 0 8px 20px rgba(0, 0, 0, .08);
          transition: all .3s ease;
      }

      .media-card:hover {
          transform: translateY(-6px);
          box-shadow: 0 14px 28px rgba(0, 0, 0, .15);
      }

      /* IMAGE */
      .media-img-wrap {
          position: relative;
          height: 180px;
      }

      .media-img-wrap img {
          width: 100%;
          height: 100%;
          object-fit: cover;
      }

      /* STATUS BADGE */
      .status-badge {
          position: absolute;
          top: 12px;
          left: 12px;
          padding: 5px 12px;
          border-radius: 20px;
          font-size: 12px;
          font-weight: 600;
          color: #fff;
      }

      .status-badge.available {
          background: #28a745;
      }

      .status-badge.booked {
          background: #dc3545;
      }

      /* BODY */
      .media-body {
          padding: 16px;
          display: flex;
          flex-direction: column;
          height: calc(100% - 180px);
      }

      /* TITLE */
      .media-title {
          font-size: 18px;
          font-weight: 600;
          margin-bottom: 8px;
      }



      /* META */
      .media-meta {
          font-size: 14px;
          color: #555;
          margin-bottom: 10px;
      }

      /* PRICE */
      .media-price {
          font-size: 20px;
          font-weight: 700;
          color: #28a745;
          margin-bottom: 6px;
      }

      .media-price span {
          font-size: 13px;
          font-weight: 400;
          color: #999;
      }

      /* DATE */
      .media-date {
          font-size: 13px;
          color: #dc3545;
          margin-bottom: auto;
      }

      /* ACTION */
      .media-actions {
          margin-top: 12px;
          text-align: right;
      }
  </style>
  @foreach ($mediaList as $media)
      @php
          $width = (float) $media->width;
          $height = (float) $media->height;
          $sqft = $width * $height;
      @endphp
      @php $isBooked = (int) ($media->is_booked ?? 0); @endphp

      {{-- <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
          <div class="card h-100 shadow-sm">

              <img src="{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}" class="card-img-top"
                  style="height:180px; object-fit:cover;">

              <div class="card-body">
                  <h4 class="fw-bold">{{ $media->area_name }} {{ $media->facing }}</h4>
                  <div class=" mb-2 d-flex">
                      <strong>Size : </strong>
                      {{ number_format($media->width, 2) }} x {{ number_format($media->height, 2) }} ft

                  </div>
                  <div class=" mb-2 d-flex">
                      <strong>Area : </strong>
                      {{ number_format($sqft, 2) }} SQFT
                  </div>


                  <p class="fw-bold">₹ {{ number_format($media->price, 2) }}</p>

                  @if ($media->from_date && $media->to_date)
                      <h6 class="fw-bold text-danger">
                          {{ \Carbon\Carbon::parse($media->from_date)->format('d M Y') }}
                          -
                          {{ \Carbon\Carbon::parse($media->to_date)->format('d M Y') }}
                      </h6>
                  @else
                      <h6 class="fw-bold text-primary">Available</h6>
                  @endif


                  <div class="d-flex justify-content-between">


                      @php
                          $isBillboard = $media->category_id == 1;
                      @endphp
                      @if ($isBillboard)
                          @if ($isBooked === 0)
                              <a href="{{ route('admin-booking.admin-media-details', base64_encode($media->id)) }}"
                                  class="btn btn-sm btn-outline-primary">View</a>
                          @else
                              <a href="{{ route('admin-booking.admin-media-details', base64_encode($media->id)) }}"
                                  class="btn btn-sm btn-outline-primary">View</a>



                              <span class="badge bg-danger text-white px-2 py-2 rounded">Booked</span>
                          @endif
                      @else
                          <a href="{{ route('contact.create', ['media' => base64_encode($media->id)]) }}"
                              class="card-btn contact">
                              Contact Us
                          </a>
                      @endif

                  </div>
              </div>

          </div>
      </div> --}}

      <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
          <div class="media-card h-100">

              {{-- IMAGE --}}
              <div class="media-img-wrap">
                  <img src="{{ config('fileConstants.IMAGE_VIEW') . $media->first_image }}" alt="">

                  @if ($media->from_date && $media->to_date)
                      <span class="status-badge booked">Booked</span>
                  @else
                      <span class="status-badge available">Available</span>
                  @endif
              </div>

              {{-- BODY --}}
              <div class="media-body">

                  <h5 class="media-title">
                      {{ $media->area_name }} {{ $media->facing }}

                  </h5>

                  <div class="media-meta">
                      <div><strong>Size:</strong> {{ number_format($media->width, 2) }} ×
                          {{ number_format($media->height, 2) }} ft</div>
                      <div><strong>Area:</strong> {{ number_format($sqft, 2) }} SQFT</div>
                  </div>

                  <div class="media-price">
                      ₹ {{ number_format($media->price, 2) }}
                      <span>/ month</span>
                  </div>

                  @if ($media->from_date && $media->to_date)
                      <div class="media-date">
                          {{ \Carbon\Carbon::parse($media->from_date)->format('d M Y') }}
                          –
                          {{ \Carbon\Carbon::parse($media->to_date)->format('d M Y') }}
                      </div>
                  @endif

                  <div class="media-actions">
                      <a href="{{ route('admin-booking.admin-media-details', base64_encode($media->id)) }}"
                          class=" btn-sm btn btn-success">
                          View Details
                      </a>
                  </div>

              </div>
          </div>
      </div>
  @endforeach
