<?php
    require ('init.php');
    $user = get_signed_in_user();

    if (isset($_GET['publisher'])) {
        $publisher = new publisher($_GET['publisher']);
        $publisher->do_main_page();
    } else {
        $user_id = get_signed_in_userid();
        do_header ('Publishers', 'all-records');
        $resources_per_page = MAX_RESULTS;
        $o = '<strong>Overall:</strong> ';
        $iyl = '<strong>In your library:</strong> ';
        $num_publishers = $db->get_var ('SELECT COUNT(*) FROM publishers');
        echo "<p>There are ".number_format($num_publishers)." publishers on this site.";
        echo "<h3>Most popular publishers</h3>";
        echo '<p>'.($user_id ? $o : '').get_popular_publishers_as_text_list (true, true, 20, ', ').'.</p>';
        if ($user_id) {
            echo "<p>{$iyl}".get_popular_publishers_as_text_list (true, true, 20, ', ', $user_id).'.</p>';
        }
        echo "<h3>All publishers</h3>";
        $current_page = (isset($_GET['page'])) ? $_GET['page'] : 'a';
        require ('ajax/all_publishers.php');
    }

    do_footer();
?>