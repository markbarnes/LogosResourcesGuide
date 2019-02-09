<?php
    require ('init.php');
    $user = get_signed_in_user();

    if (isset($_GET['subject'])) {
        $subject = new subject($_GET['subject']);
        $subject->do_main_page();
	} else {
        $user_id = get_signed_in_userid();
        do_header ('Subjects', 'all-records');
        $resources_per_page = MAX_RESULTS;
        $o = '<strong>Overall:</strong> ';
        $iyl = '<strong>In your library:</strong> ';
        $num_subjects = $db->get_var ('SELECT COUNT(*) FROM subjects');
        echo "<p>There are ".number_format($num_subjects)." subjects on this site.";
        echo "<h3>Most popular subjects</h3>";
        echo '<p>'.($user_id ? $o : '').get_popular_subjects_as_text_list (true, true, 20, ', ').'.</p>';
        if ($user_id) {
            echo "<p>{$iyl}".get_popular_subjects_as_text_list (true, true, 20, ', ', $user_id).'.</p>';
        }
        echo "<h3>All subjects</h3>";
        $current_page = (isset($_GET['page'])) ? $_GET['page'] : 'a';
        require ('ajax/all_subjects.php');
    }

    do_footer();
?>