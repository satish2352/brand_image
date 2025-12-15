<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('asset/theamoriginalalf/images/logo_bg1.ico') }}">
         <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

   <title> ALF </title>
   
   <link id="pagestyle" href="{{ asset('asset/theamoriginalalf/css/soft-ui-dashboard.css?v=1.0.3')}}" rel="stylesheet" />
 
   <!-- DataTables-->        
        <style>
.input-group input.form-control {
    padding-right: 40px; /* space for the icon */
}
.input-group-text {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 2;
}
.input-group:not(.has-validation)> :not(:last-child):not(.dropdown-toggle):not(.dropdown-menu){
    border-top-right-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
}

select.form-control {
    font-size: 1.05rem; /* Adjust size as needed */
}
#plant_id {
    font-size: 1.05rem; /* or 18px */
}
</style>

    </head>
    <body style="background-image: url('{{ asset('asset/theamoriginalalf/images/bg_color2.jpg') }}'); 
             background-size: cover; 
             background-repeat: no-repeat; 
             background-position: center center;">

        <div class="container position-sticky z-index-sticky top-0">
            <div class="row">
                <div class="col-12">                 

                </div>
            </div>
        </div>
        <main class="main-content  mt-0">
            <section>
                <div class="page-header min-vh-75" style="padding-bottom: 40px; margin: 0px;">
                    <div class="container">

                            <div class="row">
                            <div class="col-sm-8">
                                <style>
                                  .reflected-text {
                                    display: inline-block;
                                    position: relative;
                                    color: #333; /* Change the color of the text */
                                    font-family: Arial, sans-serif; /* Set the font family */
                                  }
                                  .reflected-text:after {
                                    content: attr(data-text);
                                    position: absolute;
                                    top: 100%;
                                    left: 0;
                                    transform: scaleY(-1);
                                    color: #999; /* Change the color of the reflection */
                                    margin-top: -30px; /* Adjust this value to remove the space */
                                    opacity: 0.1; /* Set the opacity for a transparent reflection */
                                  }
                                </style>
                                 <div class="row">
                                    <div class="col-sm-12"> 

                                         <div class="reflected-text" style="font-size: 58px;width: 100%;color: white;margin-top: 42%;" data-text="ALF ENGINEERING PVT LTD">ALF ENGINEERING PVT LTD</div>

                                    </div>
                                    <div class="col-sm-12"> 
                                    </div>
                                </div>
                                <div class="row">
                                    
                                </div>
                            </div>
                            <div class="col-sm-4 d-flex flex-column mx-auto">
                                <div class="card card-plain mt-2">
                                    <div class="card-header pb-0 text-left bg-transparent text-center">
                                         
                                        <img src="{{ asset('asset/theamoriginalalf/images/logo_bg1.ico') }}" style="height: 80px;width:30%;">

                                       
                                    </div>
                                 
                                    <div class="card-body">

                                       @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                                                                                
                    <form class="form-horizontal form-material" method="POST" id="loginform" action="{{ route('emp.login.submit') }}">
                        @csrf

                        <label style="color:#fff">User name</label>
                        <div class="mb-3">
                            <input type="text" id="superemail" name="superemail" class="form-control" placeholder="User" value="{{ old('superemail') }}">
                                @error('superemail')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                        </div>

                        <label style="color:#fff">Password</label>
                        <div class="mb-3">
                            <div class="input-group input-group-outline">
                                <input type="password" id="superpassword" name="superpassword" class="form-control" placeholder="Password" value="{{ old('superpassword') }}">
                                <span class="input-group-text" id="togglePassword" style="cursor: pointer; background: transparent; border: none;">
                                    <i class="fas fa-eye" style="color: #999;"></i>
                                </span>
                            </div>
                            @error('superpassword')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <label style="color:#fff">Select Plant</label>
                        <div class="mb-3">
                            <select id="plant_id" name="plant_id" class="form-control">
                                <option value="">-- Select Plant --</option>
                            </select>
                            @error('plant_id')
                                <span class="text-danger" style="font-size: 14px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <label style="color:#fff">Select Financial Year</label>
                        <div class="mb-3">
                            <select id="financial_year_id" name="financial_year_id" class="form-control">
                                <option value="">-- Select Year --</option>
                                @foreach($financialYears as $fy)
                                    <option value="{{ $fy->id }}">{{ $fy->year }}</option>
                                @endforeach
                            </select>
                            @error('financial_year_id')
                                <span class="text-danger" style="font-size: 14px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
                            @error('g-recaptcha-response')
                                <span class="text-danger" style="font-size: 14px;">{{ $message }}</span>
                            @enderror
                        </div> 

                        <div class="text-center">
                            <button type="submit" class="btn bg-gradient-info w-100 mt-4 mb-0">Sign in</button>
                        </div>
                    </form>

                                    </div>
                                </div>
                            </div>

                        </div>
                    
                    </div>
                </div>
            </section>
        </main>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
             <script>
  const togglePassword = document.querySelector('#togglePassword');
const password = document.querySelector('#superpassword');

togglePassword.addEventListener('click', function () {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);

    // toggle the eye icon
    this.querySelector('i').classList.toggle('fa-eye');
    this.querySelector('i').classList.toggle('fa-eye-slash');
});

</script>


<script>
document.getElementById('superemail').addEventListener('blur', function () {
    let email = this.value;

    if (!email) return;

    fetch('{{ url("/get-plants-by-email") }}?email=' + email)
        .then(res => res.json())
        .then(data => {
            let select = document.getElementById('plant_id');
            select.innerHTML = `<option value="">-- Select Plant --</option>`;

            data.forEach(p => {
                select.innerHTML += `<option value="${p.id}">${p.plant_code} - ${p.plant_name}</option>`;
            });
        });
});
</script>

   
    </body>
</html>