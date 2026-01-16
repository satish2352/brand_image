<!-- Right sidebar -->
<!-- ============================================================== -->
<!-- .right-sidebar -->
<div class="right-sidebar">
    <div class="slimscrollright">
        <div class="rpanel-title"> Service Panel <span><i class="ti-close right-side-toggle"></i></span> </div>
        <div class="r-panel-body">
            <ul id="themecolors" class="mt-3">
                <li><b>With Light sidebar</b></li>
                <li><a href="javascript:void(0)" data-theme="default" class="default-theme">1</a></li>
                <li><a href="javascript:void(0)" data-theme="green" class="green-theme">2</a></li>
                <li><a href="javascript:void(0)" data-theme="red" class="red-theme">3</a></li>
                <li><a href="javascript:void(0)" data-theme="blue" class="blue-theme working">4</a></li>
                <li><a href="javascript:void(0)" data-theme="purple" class="purple-theme">5</a></li>
                <li><a href="javascript:void(0)" data-theme="megna" class="megna-theme">6</a></li>
                <li class="d-block mt-4"><b>With Dark sidebar</b></li>
                <li><a href="javascript:void(0)" data-theme="default-dark" class="default-dark-theme">7</a></li>
                <li><a href="javascript:void(0)" data-theme="green-dark" class="green-dark-theme">8</a></li>
                <li><a href="javascript:void(0)" data-theme="red-dark" class="red-dark-theme">9</a></li>
                <li><a href="javascript:void(0)" data-theme="blue-dark" class="blue-dark-theme">10</a></li>
                <li><a href="javascript:void(0)" data-theme="purple-dark" class="purple-dark-theme">11</a></li>
                <li><a href="javascript:void(0)" data-theme="megna-dark" class="megna-dark-theme ">12</a></li>
            </ul>
            <ul class="mt-3 chatonline">
                <li><b>Chat option</b></li>
                <li>
                    <a href="javascript:void(0)"><img src="{{ asset('asset/images/users/1.jpg') }}" alt="user-img"
                            class="rounded-circle"> <span>Varun Dhavan <small
                                class="text-success">online</small></span></a>
                </li>
                <li>
                    <a href="javascript:void(0)"><img src="{{ asset('asset/images/users/2.jpg') }}" alt="user-img"
                            class="rounded-circle"> <span>Genelia Deshmukh <small
                                class="text-warning">Away</small></span></a>
                </li>
                <li>
                    <a href="javascript:void(0)"><img src="{{ asset('asset/images/users/3.jpg') }}" alt="user-img"
                            class="rounded-circle"> <span>Ritesh Deshmukh <small
                                class="text-danger">Busy</small></span></a>
                </li>
                <li>
                    <a href="javascript:void(0)"><img src="{{ asset('asset/images/users/4.jpg') }}" alt="user-img"
                            class="rounded-circle"> <span>Arijit Sinh <small
                                class="text-muted">Offline</small></span></a>
                </li>
                <li>
                    <a href="javascript:void(0)"><img src="{{ asset('asset/images/users/5.jpg') }}" alt="user-img"
                            class="rounded-circle"> <span>Govinda Star <small
                                class="text-success">online</small></span></a>
                </li>
                <li>
                    <a href="javascript:void(0)"><img src="{{ asset('asset/images/users/6.jpg') }}" alt="user-img"
                            class="rounded-circle"> <span>John Abraham<small
                                class="text-success">online</small></span></a>
                </li>
                <li>
                    <a href="javascript:void(0)"><img src="{{ asset('asset/images/users/7.jpg') }}" alt="user-img"
                            class="rounded-circle"> <span>Hritik Roshan<small
                                class="text-success">online</small></span></a>
                </li>
                <li>
                    <a href="javascript:void(0)"><img src="{{ asset('asset/images/users/8.jpg') }}" alt="user-img"
                            class="rounded-circle"> <span>Pwandeep rajan <small
                                class="text-success">online</small></span></a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- ============================================================== -->
<!-- End Right sidebar -->
<!-- ============================================================== -->
</div>
<!-- ============================================================== -->
<!-- End Container fluid  -->
<!-- ============================================================== -->
<!-- ============================================================== -->
<!-- footer -->
<!-- ============================================================== -->
<footer class="footer">
    © {{ date('Y') }} Sumago Infotech Pvt Ltd. All rights reserved.
</footer>
<!-- ============================================================== -->
<!-- End footer -->
<!-- ============================================================== -->
</div>
<!-- ============================================================== -->
<!-- End Page wrapper  -->
<!-- ============================================================== -->
</div>
<!-- ============================================================== -->
<!-- End Wrapper -->
<!-- ============================================================== -->
<!-- ============================================================== -->
<!-- All Jquery -->
<!-- ============================================================== -->


<!-- <script src="{{ asset('asset/plugins/jquery/jquery.min.js') }}"></script> -->
<!-- Bootstrap tether Core JavaScript -->


<script>
    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('.datatables')) {
            $('.datatables').DataTable().destroy();
        }

        var table = $('.datatables').DataTable({
            responsive: false, // we are using scrollX for wide tables
            scrollX: true,
            autoWidth: false,
            pageLength: 10,
            ordering: true,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ rows",
                paginate: {
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // ✅ Adjust header widths on resize / zoom
        function adjustTable() {
            table.columns.adjust().draw(false);
        }

        let resizeTimer;
        $(window).on('resize orientationchange', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(adjustTable, 300);
        });

        // ✅ Fix header disappearing issue (wait until DOM is ready)
        setTimeout(adjustTable, 500);

        // ✅ Detect zoom changes
        let oldZoom = window.devicePixelRatio;
        setInterval(function() {
            if (window.devicePixelRatio !== oldZoom) {
                oldZoom = window.devicePixelRatio;
                adjustTable();
            }
        }, 600);
    });
    // SweetAlert Delete Confirmation
    $(document).ready(function() {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                let form = this.closest('form');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
<script>
    setTimeout(function() {
        let alert = document.getElementById('success-alert');
        if (alert) {
            // Bootstrap 5 dismiss
            let bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 3000); // 3000ms = 3 seconds
</script>

<script>
    $(document).ready(function() {
        // Hide success alert after 5 seconds
        setTimeout(function() {
            $('#success-alert').fadeOut('slow');
        }, 5000);

        // Hide error alert after 5 seconds
        setTimeout(function() {
            $('#error-alert').fadeOut('slow');
        }, 5000);
    });
</script>

<script>
$(document).ready(function () {

    if ($('.alert-dismissible').length) {

        setTimeout(function () {
            $('.alert-dismissible').fadeOut(500, function () {
                $(this).remove();
            });
        }, 5000); // 5 seconds
    }

});
</script>

<script>
$(document).ready(function () {

    if ($('.alert-success').length) {

        setTimeout(function () {
            $('.alert-success').fadeOut(500, function () {
                $(this).remove();
            });
        }, 5000); // 5 seconds
    }

});
</script>

{{-- for brand image --}}





<script src="{{ asset('asset/plugins/popper/popper.min.js') }}"></script>
<script src="{{ asset('asset/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
<!-- slimscrollbar scrollbar JavaScript -->
<script src="{{ asset('asset/js/jquery.slimscroll.js') }}"></script>
<!--Wave Effects -->
<script src="{{ asset('asset/js/waves.js') }}"></script>
<!--Menu sidebar -->
<script src="{{ asset('asset/js/sidebarmenu.js') }}"></script>
<!--stickey kit -->
<script src="{{ asset('asset/plugins/sticky-kit-master/dist/sticky-kit.min.js') }}"></script>
<script src="{{ asset('asset/plugins/sparkline/jquery.sparkline.min.js') }}"></script>
<!--Custom JavaScript -->
<script src="{{ asset('asset/js/custom.min.js') }}"></script>
<!-- ============================================================== -->
<!-- Style switcher -->
<!-- ============================================================== -->
<script src="{{ asset('asset/plugins/styleswitcher/jQuery.style.switcher.js') }}"></script>
<script src="{{ asset('asset/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>

<script src="{{ asset('asset/plugins/sweetalert/sweetalert2@11.js') }}"></script>
@yield('scripts')
</body>

</html>
