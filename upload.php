<?php
    require ('init.php');
    do_header('', 'upload');
    if (!get_signed_in_user()) {
        redirect_to_page (BASE_URL.'/login.php');
    } else {
        require ('templates/upload_library.php');
    }
    do_footer();  
?>
