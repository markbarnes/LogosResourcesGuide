    <!-- Additional javascript for resources tab-switcher -->
    <script>
    $(function () {
        $('#tab-all-resources').on('click', function (e) {
            $('.not-owned').show();
            $('.owned').show();
            $('.dataset').hide();
            $('.nav-tabs').find('.active').removeClass('active');
            $(this).parent().addClass('active');
            return false;
        })
        $('#tab-my-resources').on('click', function (e) {
            $('.not-owned').hide();
            $('.owned').show();
            $('.dataset').hide();
            $('.nav-tabs').find('.active').removeClass('active');
            $(this).parent().addClass('active');
            return false;
        })
        $('#tab-not-my-resources').on('click', function (e) {
            $('.not-owned').show();
            $('.owned').hide();
            $('.dataset').hide();
            $('.nav-tabs').find('.active').removeClass('active');
            $(this).parent().addClass('active');
            return false;
        })
        $('#tab-datasets').on('click', function (e) {
            $('.not-owned').hide();
            $('.owned').hide();
            $('.dataset').show();
            $('.nav-tabs').find('.active').removeClass('active');
            $(this).parent().addClass('active');
            return false;
        })
    });
    </script>