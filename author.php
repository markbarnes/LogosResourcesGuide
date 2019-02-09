<?php
    require ('init.php');
    $user = get_signed_in_user();

    if (isset($_GET['author'])) {
        $author = new author($_GET['author']);
        $author->do_main_page();
    } else {
        $user_id = get_signed_in_userid();
        do_header ('Authors', 'all-records');
        $resources_per_page = MAX_RESULTS;
        $o = '<strong>Overall:</strong> ';
        $iyl = '<strong>In your library:</strong> ';
        $num_authors = $db->get_var ('SELECT COUNT(*) FROM authors');
        echo "<p>There are ".number_format($num_authors)." authors on this site.";
        echo "<h3>Most popular authors</h3>";
        echo '<p>'.($user_id ? $o : '').get_popular_authors_as_text_list (true, true, 20, ', ').'.</p>';
        if ($user_id) {
            echo "<p>{$iyl}".get_popular_authors_as_text_list (true, true, 20, ', ', $user_id).'.</p>';
        }
        echo "<h3>All authors</h3>";
        $current_page = (isset($_GET['page'])) ? $_GET['page'] : 'a';
        require ('ajax/all_authors.php');
    }

    do_footer();
?>
