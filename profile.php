<?php
    require ('init.php');
    require_login();
    $user = get_signed_in_user();

    if (!get_num_resources ($user['user_id'])) {
        do_header('', 'my-profile');
        require ('templates/upload_library.php');
    } else {
        do_header($user['user'], 'my-profile');
        echo "<table id=\"main-stats\">";
        echo get_profile_module ('num_resources', $user['user_id']);
        echo get_profile_module ('popular_authors', $user['user_id']);
        echo get_profile_module ('popular_publishers', $user['user_id']);
        echo get_profile_module ('popular_subjects', $user['user_id']);
        echo get_profile_module ('popular_series', $user['user_id']);
        echo "</table>";
    }
    do_footer();
?>
