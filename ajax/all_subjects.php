<?php
    
    require_once ('init.php');

    if (!isset($current_page)) {
        $current_page = isset($_GET['page']) ? $_GET['page'] : 'a';
    }
    
    $results = get_subjects ($current_page);
    $num_matching = count($results);
    echo do_alphabetic_pagination ($current_page, get_url ('subject').'?page=', 0);
    if ($results) {
        $num_columns = 2;
        $table = array_chunk($results, ceil($num_matching/$num_columns));
        echo start_table (array('', '', '',''), 'all-records');
        foreach ($table as $side => $results) {
            $output = '';
            foreach ($results as $p) {
                $subject = new subject($p);
                $output .= "<a href=\"{$subject->get_url()}\">{$subject->get_name()}</a> ({$subject->get_num_resources()})<br/>";
            }
            echo do_table_cell ($output, "columns_{$num_columns}");
        }
        echo close_table ();
        echo do_alphabetic_pagination ($current_page, BASE_URL.'/subject.php?page=', 0);
    } else {
        echo "<p>No subjects found.</p>";
    }
    
?>
