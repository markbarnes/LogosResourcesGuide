<?php
    
    require_once ('init.php');

    if (!isset($current_page)) {
        $current_page = isset($_GET['page']) ? $_GET['page'] : 'a';
    }
    
    $results = get_publishers ($current_page);
    $num_matching = count($results);
    echo do_alphabetic_pagination ($current_page, get_url ('publisher').'?page=', 0);
    if ($results) {
        $num_columns = min(max(2,ceil($num_matching/100)), 4);
        $table = array_chunk($results, ceil($num_matching/$num_columns));
        echo start_table (array('', '', '',''), 'all-records');
        foreach ($table as $side => $results) {
            $output = '';
            foreach ($results as $p) {
                $publisher = new publisher($p);
                $output .= "<a href=\"{$publisher->get_url()}\">{$publisher->get_name()}</a> ({$publisher->get_num_resources()})<br/>";
            }
            echo do_table_cell ($output, "columns_{$num_columns}");
        }
        echo close_table ();
        echo do_alphabetic_pagination ($current_page, BASE_URL.'/publisher.php?page=', 0);
    } else {
        echo "<p>No publishers found.</p>";
    }
    
?>
