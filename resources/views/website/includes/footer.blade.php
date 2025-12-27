<footer class="bg-dark text-white text-center py-3 mt-5">
    <p class="mb-0">© {{ date('Y') }} MyWebsite. All Rights Reserved.</p>
</footer>
<script>
    function setRedirect(url) {
        sessionStorage.setItem('redirect_after_login', url);
    }
</script>
