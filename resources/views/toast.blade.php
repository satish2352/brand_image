<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.2/js/toastr.min.js"></script>
<script>
    @if (count($errors->all()) > 0)
        <?php for($i=0;$i<sizeof($errors->all());$i++) { ?>
        toastr.error('{{ $errors->all()[$i] }}');

        <?php }?>
    @endif

    @if (session('success'))
        toastr.success('{{ session('success') }}');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}');
    @endif
</script>
