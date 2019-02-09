<?php
    require ('init.php');
    $user = get_signed_in_user();

    if (isset($_GET['series'])) {
        $series = new series($_GET['series']);
        $series->do_main_page();
    } else {
        $user_id = get_signed_in_userid();
        do_header ('Series', 'all-records');
        $resources_per_page = MAX_RESULTS;
        $o = '<strong>Overall:</strong> ';
        $iyl = '<strong>In your library:</strong> ';
        $num_series = $db->get_var ('SELECT COUNT(DISTINCT series) FROM resources');
        echo "<p>There are ".number_format($num_series)." series on this site.";
        echo "<h3>Most popular series</h3>";
        echo '<p>'.($user_id ? $o : '').get_popular_series_as_text_list (true, true, 20, ', ').'.</p>';
        if ($user_id) {
            echo "<p>{$iyl}".get_popular_series_as_text_list (true, true, 20, ', ', $user_id).'.</p>';
        }
        echo "<h3>All series</h3>";
        $current_page = (isset($_GET['page'])) ? $_GET['page'] : 'a';
        require ('ajax/all_series.php');
    }

    do_footer();
?>
