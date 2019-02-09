<?php
    require ('init.php');
    $user = get_signed_in_user();

    if (isset($_GET['resource'])) {
        $resource = new resource($_GET['resource']);
        if ($resource->valid) {
            do_header($resource->get_title(), 'resource-page', $resource->get_cover());
            echo "<table class=\"subhead\">";
            /*
            $num_copies = 'Owned by '.$resource->get_num_copies('user', 'users');
            if ($num_copies != 'Owned by 0 users') {
            	$lines['Copies'] = $num_copies;
			}
			*/
            $lines['Community Rating'] = $resource->get_average_rating(2);
            $lines['Community Tags'] = $resource->get_tags_as_text_list (true, true, ', ');
            if ($authors = $resource->get_authors_names_as_array(true)) {
                $lines['Author'.(count($authors) > 1 ? 's' : '')] = implode($authors, ', ');
            }
            $lines['Published by'] = $resource->get_publishers_as_text_list (true, ', ').' ('.$resource->get_year().')';
            $lines['ISBN'] = $resource->get_isbn();
            $lines['Type'] = $resource->get_type_name (true);
            $lines['Series'] = $resource->get_series_as_text_list(true, ', ');
            $lines['Language'] = $resource->get_languages_as_text_list (true, ', ');
            $lines['Subjects'] = $resource->get_subjects_as_text_list (true, ', ');
            $lines['Cost'] = $resource->get_product_name (true, true, true, true);
            $label = ($lines['Cost'] && strpos($lines['Cost'], 'Not available') === FALSE) ? 'Also included in' : 'Included in';
            $lines[$label] = $resource->get_parent_products_as_text_list ('<br/>', true, true, true, 5);
            $lines['Last updated'] = $resource->get_file_history_as_text_list ();
            $lines['Traits'] = $resource->get_traits_as_text_list (true, ', ', 3);
            $lines['Compatibility'] = $resource->get_platforms_as_text_list (true, ', ');
            $lines['Milestones'] = $resource->get_milestones_as_text_list();
            $lines['Open in'] = $resource->go_to();
            if ($user) {
            	if ($resource->have_i_hidden_it()) {
					$lines['Your library'] = '<p><span class="label label-warning">You have hidden this resource</span></p>';
            	} elseif ($resource->do_i_have_it()) {
                    $lines['Your library'] = '<p><span class="label label-success">You own this resource</span></p>';
                } else {
                    $lines['Your library'] = '<p><span class="label label-danger">You do not have this resource</span>';
                    if ($resource->has_product()) {
                        $lines['Your library'] .= '<br/>'.$resource->product->buy_now_button().'</p>';
                    }
                }
            }
            foreach ($lines as $heading => $text) {
                if ($text) {
                    echo '<tr><th scope="row">'.$heading.':</th><td>'.$text.'</td></tr>';
                }
            }
            echo '</table>';
            echo $resource->get_description();
            if ($resource->is_lls()) {
                echo "<hr class=\"clear\"/>";
                echo "<h2>Preview</h2>";
                echo "<iframe width=\"100%\" height=\"578\" src=\"//biblia.com/api/plugins/embeddedpreview?resourceName={$resource->get_id()}&amp;layout=minimal&amp;historybuttons=false&amp;navigationbox=false&amp;sharebutton=false\" scrolling=\"no\" frameborder=\"0\" allowtransparency=\"true\"></iframe>";
            } elseif (!$resource->do_i_have_it()) {
            	if ($resource->is_pbb()) {
                	echo "<p>This is a user-created personal book that is not available to purchase. If you&rsquo;re interested in the book, you could try <a target=\"_blank\" href=\"http://www.google.com/search?q=".urlencode("site:community.logos.com \"{$resource->get_title()}\"")."\">searching the Logos forums</a> to see whether the user who created it has made it available for others.</p>";
				} else {
					echo "<p>This is a dataset and can't be previewed.</p>";
				}
            } else {
                echo "<p>This is a personal book that you have compiled and is part of your library.</p>";
            }
        }
    } else {
        do_header ('All resources');
        $resources_per_page = MAX_RESULTS;
        $num_resources = get_num_distinct_resources();
        echo "<p>There are ".number_format($num_resources)." known resources on this site. That should include all resources currently available for sale at logos.com, as well as other resources in libraries uploaded by users. Most resources from ebooks.faithlife.com and ebooks.noet.com are missing, as those sites don't publish resource ids.";
        if ($user) {
            echo " The ".number_format(get_num_resources($user['user_id']))." resources that you have are highlighted in yellow.";
        }
        echo "</p>";
        $current_page = (isset($_GET['page'])) ? $_GET['page'] : 'A';
        require ('ajax/all_resources.php');
    }

    do_footer();
?>
