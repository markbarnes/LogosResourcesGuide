<?php
    
    require_once ('init.php');

    if (!isset($current_page)) {
        $current_page = isset($_GET['page']) ? $_GET['page'] : 'a';
    }
    
    $authors = get_authors ($current_page);
    $num_matching_authors = count($authors);
    echo do_alphabetic_pagination ($current_page, get_url ('author').'?page=', 0);
    if ($authors) {
        $num_columns = min(max(2,ceil($num_matching_authors/100)), 4);
        $table = array_chunk($authors, ceil($num_matching_authors/$num_columns));
        echo start_table (array('', '', '',''), 'all-records');
        foreach ($table as $side => $authors) {
            $output = '';
            foreach ($authors as $a) {
                $author = new author($a);
                $output .= "<a href=\"{$author->get_url()}\">{$author->get_name()}</a> ({$author->get_num_resources()})<br/>";
            }
            echo do_table_cell ($output, "columns_{$num_columns}");
        }
        echo close_table ();
        echo do_alphabetic_pagination ($current_page, BASE_URL.'/author.php?page=', 0);
    } else {
        echo "<p>No authors found.</p>";
    }
    
?>
