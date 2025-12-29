@extends('website.layout')

@section('title', 'Contact Us')

@section('content')

<style>
    /* CONTACT FORM MODERN LOOK */
/* .contact-form {
    background: #ffffff;
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
} */

.contact-form .form-control {
    height: 52px;
    padding: 12px 16px;
    font-size: 15px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background-color: #fafafa;
    transition: all 0.3s ease;
}

.contact-form textarea.form-control {
    height: auto;
    resize: none;
}

/* Focus Effect */
.contact-form .form-control:focus {
    background-color: #fff;
    border-color: #f28123;
    box-shadow: 0 0 0 4px rgba(242, 129, 35, 0.15);
}

/* Error Text */
.contact-form small.text-danger {
    display: block;
    margin-top: 6px;
    font-size: 13px;
}

/* Submit Button */
.contact-form .boxed-btn {
    background: linear-gradient(135deg, #f28123, #ff9f45);
    border: none;
    color: #fff;
    padding: 14px 36px;
    font-size: 15px;
    font-weight: 600;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.contact-form .boxed-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(242, 129, 35, 0.35);
}

/* Title polish */
.form-title h2 {
    font-weight: 700;
    margin-bottom: 10px;
}

.form-title p {
    color: #6b7280;
    font-size: 15px;
}

</style>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p>Get 24/7 Support</p>
						<h1>Contact us</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

<!-- contact form -->
<div class="contact-from-section mt-150 mb-150">
    <div class="container">
        <div class="row">

            <div class="col-lg-8 mb-5 mb-lg-0">
                <div class="form-title">
                    <h2>Have you any question?</h2>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</p>
                </div>

                {{-- Success Message --}}
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="contact-form">
                    <form method="POST" action="{{ route('contact.store') }}">
                        @csrf

                        <!-- ROW 1 : NAME + EMAIL -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <input type="text"
                                       class="form-control"
                                       placeholder="Name"
                                       name="full_name"
                                       value="{{ old('full_name') }}">
                                @error('full_name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <input type="email"
                                       class="form-control"
                                       placeholder="Email"
                                       name="email"
                                       value="{{ old('email') }}">
                                @error('email')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- ROW 2 : MOBILE + ADDRESS -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <input type="tel"
                                       class="form-control"
                                       placeholder="Mobile"
                                       name="mobile_no"
                                       value="{{ old('mobile_no') }}">
                                @error('mobile_no')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <textarea name="address"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Address">{{ old('address') }}</textarea>
                                @error('address')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- MESSAGE -->
                        <div class="row">
                            <div class="col-12 mb-3">
                                <textarea name="remark"
                                          class="form-control"
                                          rows="6"
                                          placeholder="Message">{{ old('remark') }}</textarea>
                                @error('remark')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- SUBMIT -->
                        <div class="row">
                            <div class="col-12">
                                <input type="submit" value="Submit" class="boxed-btn">
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            <!-- RIGHT SIDE (UNCHANGED) -->
            <div class="col-lg-4">
                <div class="contact-form-wrap">
                    <div class="contact-form-box">
                        <h4><i class="fas fa-map"></i> Shop Address</h4>
                        <p>34/8, East Hukupara <br> Gifirtok, Sadan. <br> Country Name</p>
                    </div>

                    <div class="contact-form-box">
                        <h4><i class="far fa-clock"></i> Shop Hours</h4>
                        <p>MON - FRIDAY: 8 to 9 PM <br> SAT - SUN: 10 to 8 PM</p>
                    </div>

                    <div class="contact-form-box">
                        <h4><i class="fas fa-address-book"></i> Contact</h4>
                        <p>Phone: +00 111 222 3333 <br> Email: support@fruitkha.com</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- end contact form -->


    <!-- find our location -->
	<div class="find-location blue-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-12 text-center">
					<p> <i class="fas fa-map-marker-alt"></i> Find Our Location</p>
				</div>
			</div>
		</div>
	</div>
	<!-- end find our location -->

	<!-- google map section -->
	<div class="embed-responsive embed-responsive-21by9">
		{{-- <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d26432.42324808999!2d-118.34398767954286!3d34.09378509738966!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80c2bf07045279bf%3A0xf67a9a6797bdfae4!2sHollywood%2C%20Los%20Angeles%2C%20CA%2C%20USA!5e0!3m2!1sen!2sbd!4v1576846473265!5m2!1sen!2sbd" width="100%" height="450" frameborder="0" style="border:0;" allowfullscreen="" class="embed-responsive-item"></iframe> --}}
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3749.6197830459505!2d73.77288857500191!3d19.982486081417772!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bddeb2094f5d9ff%3A0x57bf9c97dbf22492!2sBrand%20Image%20Media%20Pvt%20Ltd%20%7C%20Outdoor%20Advertising%20Agency!5e0!3m2!1sen!2sin!4v1766986988381!5m2!1sen!2sin" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="embed-responsive-item"></iframe>
	</div>
	<!-- end google map section -->


{{-- <div class="container my-5">
    <h3 class="mb-4">Contact Us</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('contact.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" >
            @error('full_name') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Mobile No *</label>
            <input type="text" name="mobile_no" class="form-control" value="{{ old('mobile_no') }}" >
            @error('mobile_no') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" >
            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Address *</label>
            <textarea name="address" class="form-control" >{{ old('address') }}</textarea>
            @error('address') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Remark *</label>
            <textarea name="remark" class="form-control" >{{ old('remark') }}</textarea>
            @error('remark') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <button type="submit" class="btn btn-primary">
            Submit
        </button>
    </form>
</div> --}}


@endsection
