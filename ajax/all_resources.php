<?php

    require_once ('init.php');

    if (!isset($resources_per_page)) {
        $resources_per_page = isset($_GET['max']) ? $_GET['max'] : MAX_RESULTS;
    }
    if (!isset($current_page)) {
        $current_page = isset($_GET['page']) ? $_GET['page'] : 1;
    }

    $resources = get_resources_from_page ($current_page);
    $num_resources = get_num_distinct_resources();
    if ($resources) {
        echo do_alphabetic_pagination ($current_page, get_url ('resource').'?page=', 0);
        echo start_table (array ('Cover', 'Title', 'Author(s)', 'Publisher', 'Series', 'Subjects', 'Year'), 'all-resources', 'table-hover clear resources');
        foreach ($resources as $resource) {
            $class = $resource->do_i_have_it() ? 'owned' : 'not-owned';
            echo '<tr class="'.$class.'">';
            echo do_table_cell ("<a href=\"".$resource->get_link()."\">".$resource->get_cover(false, true)."</a>", $class);
            echo do_table_cell ($resource->get_title(true, true), $class);
            echo do_table_cell ($resource->get_authors_names_as_text_list(true, '<br/>'), $class);
            echo do_table_cell ($resource->get_publishers_as_text_list (true, '<br/>'), $class);
            echo do_table_cell ($resource->get_series_as_text_list(true, '<br/>'), $class);
            echo do_table_cell ($resource->get_subjects_as_text_list(true, '<br/>'), $class);
            echo do_table_cell ($resource->get_year(true), $class);
            echo '</tr>';
        }
        echo close_table ();
        echo do_alphabetic_pagination ($current_page, get_url ('resource').'?page=', 0);
    }

?>
