<?php
    
    require_once ('init.php');

    if (!isset($current_page)) {
        $current_page = isset($_GET['page']) ? $_GET['page'] : 'a';
    }
    
    $results = get_series ($current_page);
    $num_matching = count($results);
    echo do_alphabetic_pagination ($current_page, get_url ('series').'?page=', 0);
    if ($results) {
        $num_columns = 2;
        $table = array_chunk($results, ceil($num_matching/$num_columns));
        echo start_table (array('', '', '',''), 'all-records');
        foreach ($table as $side => $results) {
            $output = '';
            foreach ($results as $p) {
                $series = new series($p);
                $output .= "<a href=\"{$series->get_url()}\">{$series->get_name()}</a> ({$series->get_num_resources()})<br/>";
            }
            echo do_table_cell ($output, "columns_{$num_columns}");
        }
        echo close_table ();
        echo do_alphabetic_pagination ($current_page, BASE_URL.'/series.php?page=', 0);
    } else {
        echo "<p>No series found.</p>";
    }
    
?>
