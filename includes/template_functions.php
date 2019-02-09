<?php
    function do_header($page_title, $body_id = '', $img = '', $image_link = '') {
        if ($body_id == '')
            $body_id = make_id($page_title);
        require ('templates/header.php');
        require ('templates/navbar.php');
        echo "  <div class=\"container\">\r\n";
        echo "  <div class=\"page_header\">\r\n";
        if ($img != '') {
        	if ($image_link != '') {
				echo "<a href=\"{$image_link}\"><img src=\"{$img}\" /></a>";
        	} else {
				echo "<img src=\"{$img}\" />";
        	}
        }
        if ($page_title != '')
            echo "      <h1>{$page_title}</h1>\r\n";
        echo "  </div>\r\n";
    }

    function do_footer() {
        global $include_in_footer;
        require ('templates/footer.php');
    }

    function profile_table_row ($title, $content, $row_class='') {
        $id = make_id ($title);
        return "<tr id=\"{$id}\"".($row_class == '' ? '' : "class=\"{$row_class}\"")."><th span=\"row\">{$title}:</th><td>{$content}</td></tr>\r\n";
    }

    function make_id ($text) {
        $text = preg_replace("/[^0-9a-zA-Z ]/m", "", $text);
        return preg_replace("/ /", "-", strtolower($text));
    }

    function format_author ($author) {
        if (($pos = strpos($author, ',')) === FALSE)
            return $author;
        if ($pos == ($pos2 = strrpos($author, ',')))
            return substr($author, $pos+2).' '.substr($author, 0, $pos);
        return substr($author, $pos+2, $pos2-$pos-2).' '.substr($author, 0, $pos).' '.substr($author, $pos2+2, $pos+2);
    }

    function get_jumbotron ($contents, $heading = '', $heading_level = 1) {
        if ($heading != '')
            $heading = "<h{$heading_level}>{$heading}</h{$heading_level}>";
        return "<div class=\"jumbotron\">{$heading}<p>{$contents}</p></div>";
    }

    function get_profile_module ($module_name, $user_id = '') {
        global $db;
        //Number of resources
        if ($module_name == 'num_resources') {
            $total_resources = get_num_distinct_resources();
            if ($user_id) {
                $output = number_format($my_resources = get_num_resources ($user_id)).'<br/>';
                if ($total_resources > 0) {
                    $output .= 'You have '.number_format($my_resources/$total_resources*100, 2).'% of all known resources.';
                }
            } else {
                $output = number_format($total_resources).' distinct resources';
            }
            return profile_table_row ('Library size', $output);
        //Tags
        } elseif ($module_name == 'tags') {
            $tags = get_all_tags (50);
            if ($tags) {
                $output = array();
                foreach ($tags as $tag) {
                    $tag['num_tags'] = number_format($tag['num_tags']);
                    $output [] = "<a href=\"tag.php?tag=".$tag['tag']."\">{$tag['tag']}</a> ({$tag['num_tags']})";
                }
                return profile_table_row ('Community Tags', implode (', ', $output));
            }
        //Ratings
        } elseif ($module_name == 'ratings') {
            $output = array();
            for ($i = 10; $i >= 2; $i--) {
                $output [] = "<a href=\"rating.php?rating=".($i/2)."\">".do_stars ($i/2).'</a> ('.number_format(get_num_starred ($i/2, true)).')';
            }
            return profile_table_row ('Community Ratings', implode (', ', $output));
        //Authors
        } elseif ($module_name == 'popular_authors') {
            $output = '<strong>Most popular:</strong> '.get_popular_authors_as_text_list (true, true, 50, ', ', $user_id);
            return profile_table_row ('Authors', $output);
        //Publishers
        } elseif ($module_name == 'popular_publishers') {
            $output = '<strong>Most popular:</strong> '.get_popular_publishers_as_text_list (true, true, 50, ', ', $user_id);
            return profile_table_row ('Publishers', $output);
        //Series
        } elseif ($module_name == 'popular_series') {
            $series = get_all_series ($user_id, true, 50);
            $output = array();
            foreach ($series as $s) {
                    $s['num_resources'] = number_format($s['num_resources']);
                    $output [] = "<a href=\"series.php?series=".urlencode($s['series'])."\">{$s['series']}</a> ({$s['num_resources']})";
            }
            $output = '<strong>Most popular:</strong> '.implode (', ', $output);
            if ($series = get_favourite_series ($user_id, 10)) {
                $output2 = array();
                foreach ($series as $s) {
                    $s['num_resources'] = number_format($s['num_resources']);
                    $output2 [] = "<a href=\"series.php?series=".urlencode($s['series'])."\">{$s['series']}</a> (".number_format($s['user_rating'],2).")";
                }
                $output .= "<p class=\"highest-rated\">\r\n<strong>Highest rated:</strong> ".implode (', ', $output2)."</p>";
            };
            return profile_table_row ('Series', $output);
        //Types
        } elseif ($module_name == 'popular_types') {
            $types = get_all_types ($user_id, true, 50);
            $output = array();
            foreach ($types as $type) {
                    $type['num_resources'] = number_format($type['num_resources']);
                    $output [] = "<a href=\"types.php?type=".urlencode($type['type_id'])."\">{$type['type']}</a> ({$type['num_resources']})";
            }
            return profile_table_row ('Types', implode (', ', $output));
        //Favourite
        } elseif ($module_name == 'favourite_books') {
            $books = get_favourite_resources ($user_id, 15);
            $output = array();
            foreach ($books as $book) {
                    $src = str_replace ('/cover/', '/50x80/cover/', $book['cover_image']);
                    $output [] = "<a href=\"resource.php?resource=".urlencode($book['resource_id'])."\"><img src=\"{$src}\" width=50 height=80 title=\"{$book['title']} – {$book['rating_value']}/".number_format($book['rating_count'])."\"></a>";
            }
            return profile_table_row ('Favourite Books', implode (' ', $output), 'shelf-small');
        //Traits
        } elseif ($module_name == 'popular_traits') {
            $traits = get_all_traits ($user_id, true, 50);
            $output = array();
            foreach ($traits as $trait) {
                    $trait['num_resources'] = number_format($trait['num_resources']);
                    $output [] = "<a href=\"traits.php?trait={$trait['trait_id']}\">{$trait['trait']}</a> ({$trait['num_resources']})";
            }
            $output = implode (', ', $output);
            return profile_table_row ('Traits', $output);
        //Subjects
        } elseif ($module_name == 'popular_subjects') {
            $output = '<strong>Most popular:</strong> '.get_popular_subjects_as_text_list (true, true, 50, ', ', $user_id);
            return profile_table_row ('Subjects', $output);
        //Languages
        } elseif ($module_name == 'popular_languages') {
            $languages = get_all_languages ($user_id, true, 50);
            $output = array();
            foreach ($languages as $language) {
                    $language['num_resources'] = number_format($language['num_resources']);
                    $output [] = "{$language['language']} ({$language['num_resources']})";
            }
            $output = implode (', ', $output);
            return profile_table_row ('Languages', $output);
        //Summary
        } elseif ($module_name == 'library_summary') {
        	$summary = '<strong>Number of resources:</strong> '.number_format($num_distinct_resources = get_num_distinct_resources()).' (of which '.number_format($num_owned_resources = get_num_distinct_owned_resources()).' are owned by at least one user &mdash; '.number_format ($num_owned_resources/$num_distinct_resources*100,0).'%)<br/>';
            $summary .= '<strong>Total library size:</strong> '.number_format($users = get_num_users()).' users have '.number_format($num_resources = get_num_resources()).' resources<br/>';
            $summary .= '<strong>Average library size:</strong> '.number_format($num_resources/$users,0).' resources<br/>';
            return profile_table_row ('Summary', $summary);

        }
    }

    function do_pagination ($num_items, $items_per_page, $current_page, $url, $max_num_pages_shown = 9) {
        $num_pages = ceil($num_items/$items_per_page);
        $output = "<ul class=\"pagination\">";
        $half_max_num_pages_shown = floor($max_num_pages_shown/2);
        $start_page = $current_page > ($half_max_num_pages_shown+1) ? $current_page - $half_max_num_pages_shown : 1;
        $end_page = $current_page > ($num_pages-$half_max_num_pages_shown) ? $num_pages : $current_page+$half_max_num_pages_shown;
        $total_pages_shown = $end_page-$start_page+1;
        if ($total_pages_shown < $max_num_pages_shown) {
            if ($start_page == 1) {
                $end_page = min(array ($start_page+$max_num_pages_shown-1, $num_pages));
            } else {
                $start_page = $end_page-$max_num_pages_shown+1;
            }
        }
        $output .= "<li".($start_page == 1 ? ' class="disabled"': '')."><a href=\"".($start_page == 1 ? '#': $url.'1')."\">&laquo;</a></li>";
        for ($i=$start_page; $i<= $end_page; $i++) {
            $output .= "<li".($i == $current_page ? ' class="active"' : '')."><a href=\"{$url}{$i}\">{$i}</a></li>";
        }
        $output .= "<li".($end_page == $num_pages ? ' class="disabled"': '')."><a href=\"".($end_page == $num_pages ? '#' : $url.$num_pages)."\">&raquo;</a></li>";
        $output .= "</ul>";
        return $output;
    }

    function do_alphabetic_pagination ($current_page, $url) {
        $current_page = strtoupper($current_page);
        $page[1] = '0&hellip;9';
        for ($i = 65; $i<= 90; $i++) {
            $page[] = chr($i);
        }
        $current_page_num = array_search ($current_page, $page);
        $output = "<ul class=\"pagination\">";
        $max_num_pages_shown = count ($page);
        for ($i=1; $i<= $max_num_pages_shown; $i++) {
            $output .= "<li".(substr($page[$i],0,1) == substr($current_page,0,1) ? ' class="active"' : '')."><a href=\"{$url}".($i == 1 ? 0 : $page[$i])."\">{$page[$i]}</a></li>";
        }
        $output .= "</ul>";
        return $output;
    }

    function start_table ($columns, $id, $class = '') {
        $output = "<table id=\"{$id}\"".($class != '' ? " class=\"{$class}\"" : '').'>';
        $output .= '<thead><tr>';
        foreach ($columns as $column) {
        	$column = str_replace(' ', '&nbsp;', ucwords($column));
            $output .= "<th scope=\"col\">{$column}</th>";
        }
        $output .= "</tr></thead><tbody id=\"{$id}-body\">";
        return $output;
    }

    function close_table () {
        return '</tbody></table>';
    }

    function do_table_cell ($contents, $class = '') {
        return "<td ".($class != '' ? " class=\"{$class}\"" : '').">{$contents}</td>";
    }

    function tabs_header ($tabs, $active = 1, $class = '') {
        $output = "<ul class=\"nav nav-tabs {$class}\">";
        $i = 1;
        foreach ($tabs as $id => $text) {
            $output .= '<li'.($i == $active ? ' class="active"' : '')."><a id=\"tab-{$id}\"href=\"#{$id}\">{$text}</a></li>";
            $i++;
        }
        return $output.'</ul>';
    }

    /**
    * Returns a string of stars
    *
    * @param mixed $num_stars
    * @param mixed $class
    */
    function do_stars ($num_stars, $class='') {
        if ($class != '') {
            $class = ' '.trim($class);
        }
        if ((int)$num_stars == 0) {
            return false;
        }
        $output = str_repeat ("<span class=\"glyphicon glyphicon-star{$class}\"></span>", floor($num_stars));
        $remainder = fmod ($num_stars, 1);
        if ($remainder != 0) {
            $output .= "<span class=\"glyphicon glyphicon-star{$class}\" style=\"width:".number_format ($remainder*15,1).'px"></span>';
        }
        return $output;
    }

    /**
    * Formats a rating with the appropriate number of stars
    *
    * @param mixed $rating - can be an integer, an array with the keys 'average' and 'rating', or an array with the keys 0..5 and 'average
    * @param int $decimal_places
    * @param boolean $display_count_in_parentheses
    * @param string $separator
    * @param string $class
    */
    function format_ratings ($ratings, $decimal_places = 0, $display_count_in_parentheses = true, $separator = ', ', $class='') {
        $output = array();
        if ($ratings === FALSE) {
            return false;
        } elseif (is_array($ratings)) {
            //Complete set of ratings
            if (isset($ratings['average']) && is_array($ratings['average'])) {
                foreach ($ratings as $k => $v) {
                    if (!is_array($v)) {
                        if ($v !=0) {
                            $output [] = format_ratings (array ($k => $v), $decimal_places, $display_count_in_parentheses, $separator, $class);
                        }
                    }
                }
            // An average rating with a count
            } elseif (isset($ratings['average'])) {
                if ($ratings['average'] == 0 && $ratings['count'] < 5) {
					$output [] = '';
                } else if ($display_count_in_parentheses) {
                    $output [] = do_stars($ratings['average'], $class).' ('.$ratings['count'].')';
                } else {
                    $output [] = '<span title="Average '.number_format($ratings['average'],$decimal_places).' stars from '.$ratings['count'].' ratings">'.do_stars($ratings['average'], $class).'</span>';
                }
            //A single rating with a count
            } else {
                if ($display_count_in_parentheses) {
                    $output [] = do_stars(key($ratings), $class).' ('.current($ratings).')';
                } else {
                    $output [] = '<span title="From '.current($ratings).' ratings">'.do_stars(key($ratings), $class).'</span>';
                }
            }
        // A single rating without a count
        } else {
            if ($class == 'my-rating') {
                $output [] = '<span title="You rated this '.$ratings.' stars">'.do_stars($ratings, $class).'</span>';
            } else {
                $output [] = do_stars($ratings, $class);
            }
        }
        return implode ($separator, $output);
    }

    /**
    * Returns a string containing in average rating, and counts of how it is made up.
    *
    * @param array $ratings - An array from get_ratings_summary()
    * @return string
    */
    function format_all_ratings($ratings, $class='') {
        if (!isset($ratings['average']['average']) || $ratings['average']['average'] == 0) {
            return false;
        } else
            return format_ratings ($ratings ['average'], 1, false, '', $class).'&nbsp;&nbsp;&nbsp;<span class="small-star">'.format_ratings ($ratings, 1, true, ', ', $class).'</span>';
    }

    function format_subject ($full_subject, $add_span = true) {
        $subject = str_replace (array('Bible. N.T. ', 'Bible. O.T. '), array(''), $full_subject);
        $add_span = (boolean)($add_span && $subject != $full_subject);
        $subject = rtrim(str_replace('--', '&thinsp;&rArr;&thinsp;', $subject), '.');
        if ($add_span) {
            return "<span title=\"{$full_subject}\">{$subject}</span>";
        } else {
            return $subject;
        }
    }
?>