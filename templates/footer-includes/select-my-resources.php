    <!-- Additional javascript for to select the "Not my resources" tab -->
    <script>
    $(function () {
        $('.not-owned').show();
        $('.owned').hide();
        $('.dataset').hide();
        $('.nav-tabs').find('.active').removeClass('active');
        $('#tab-not-my-resources').parent().addClass('active');
    });
    </script>