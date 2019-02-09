<?php
    class template {

    	protected $ratings;

    	function supports_users() {
			return true;
    	}

        function is_valid() {
            return $this->valid;
        }

        function get_name() {
            if ($this->valid)
                return format_author($this->author);
        }

        function get_url() {
            if ($this->valid)
                return get_url(get_called_class(), $this->get_id());
        }

        /**
        * Gets a list of data associated with the current type, and returns it as an array
        *
        * @param string $type - the name of the datatype being requested
        * @param int $user_id - an optional user_id if you want to restrict the data to that of only one user (only relevant for tags)
        * @param int limit - the maximum number of items returned
        * @return string
        */
        function get_as_array($type, $user_id = '', $limit = 99999) {
            global $db;
            if ($this->valid) {
                $class = get_class($this);
                if ($class != 'author' && $class == substr($type, 0, strlen($class))) {
                    return false;
                }
                $singular_type = ($type == 'series') ? $type : rtrim ($type, 's');
                $plural_class =  ($class == 'series' || $class= 'traits') ? $class : "{$class}s";
                //Type is in two look up tables
                $use_resources_table = true;
                if ($type == 'publishers' || $type == 'subjects' || $type == 'traits' || $type == 'languages' || $type == 'users' || $type == 'authors' || $type == 'platforms') {
                	$sql_select = "SELECT {$singular_type} AS name, resources_{$type}.{$singular_type}_id AS id, COUNT(*) AS num FROM resources_{$type}, {$type}";
                	$sql_where = "WHERE {$type}.{$singular_type}_id=resources_{$type}.{$singular_type}_id";
                	$group_by = "resources_{$type}.{$singular_type}_id";
                	$use_resources_table = false;
                //Type is in one look up table
				} elseif ($type == 'owners') {
                	$sql_select = "SELECT {$singular_type} AS name, {$singular_type} AS id, COUNT(*) AS num FROM resources, resources_{$type}";
                	$sql_where = "WHERE resources.resource_id=resources_{$type}.resource_id";
                	$group_by = $singular_type;
                //Type is in resources table, but has a friendly name elsewhere
				} elseif ($type == 'types') {
                	$sql_select = "SELECT {$type}.{$singular_type} AS name, {$singular_type}_id AS id, COUNT(*) AS num FROM resources, {$type}";
                	$sql_where = "WHERE resources.{$singular_type}={$type}.{$singular_type}_id";
                	$group_by = "{$type}.{$singular_type}";
                //Type is in resources table
				} elseif ($type == 'series') {
                	$sql_select = "SELECT {$singular_type} AS name, {$singular_type} AS id, COUNT(*) AS num FROM resources";
                	$sql_where = "WHERE {$singular_type} IS NOT NULL";
                	$group_by = $singular_type;
				//Specials
				} elseif ($type == 'tags') {
                	$sql_select = "SELECT {$singular_type} AS name, {$singular_type} AS id, SUM(count) AS num FROM resources, community_tags";
                	$sql_where = "WHERE resources.resource_id=community_tags.resource_id";
                	$group_by = $singular_type;
				} else {
					throw new Exception ('Invalid type in get_as_array');
				}
				if ($type == 'users') {
					$sql_where .= ' AND private=false';
				}
				//Class is in lookup table
				if ($class == 'author' || $class == 'publisher' || $class == 'subject' || $class = 'trait') {
					$sql_select .= ", resources_{$plural_class} AS lookup";
					$sql_where .= $use_resources_table ? " AND resources.resource_id=lookup.resource_id" : " AND resources_{$type}.resource_id=lookup.resource_id";
                //Class is in resources table
                } elseif ($class == 'series') {
                    $sql_select .= $use_resources_table ? '' : ", resources";
                    $sql_where .= $use_resources_table ? ' AND resources.series=:current_id' : " AND resources_{$type}.resource_id=resources.resource_id AND resources.series=:current_id";
				} else {
					throw new Exception ("Invalid class '{$class}' in get_as_array");
				}
				//All classes
				if ($type == 'authors' && $class == 'author') {
					$sql_where .= " AND lookup.author_id = :current_id AND authors.author_id != :current_id";
				} elseif ($class != 'series') {
					$sql_where .= " AND {$class}_id=:current_id";
				}
                $sql = "{$sql_select} {$sql_where} GROUP BY {$group_by} ORDER BY num DESC";
				return $db->get_results($sql, array (':current_id' => $this->get_id()));
            }
        }

        /**
        * Gets a list of data associated with the current type, and formats it ready for display
        *
        * @param string $type - the name of the datatype being requested
        * @param int limit - the maximum number of items returned
        * @param int $user_id - an optional user_id if you want to restrict the data to that of only one user (only relevant for tags)
        * @param string $separator - the string that should separate each item
        * @param boolean $include_links - should the output by hyperlinked?
        * @param boolean $include_counts - should counts be included in parentheses?
        * @return string
        */
        function get_as_text_list ($type, $limit = 50, $user_id = '', $separator = ', ', $include_links = true, $include_counts = true) {
            if ($this->valid) {
                $results = $this->get_as_array($type, $user_id);
                if ($results) {
                    $output = array();
                    $use = &$output;
                    $i = 1;
                    foreach ($results as $result) {
                        if ($type == 'authors') {
                            $result ['name'] = format_author($result ['name']);
                        } elseif ($type == 'subjects') {
                            $result ['name'] = format_subject($result ['name']);
                        }
                        if ($include_links) {
                        	if ($type == 'platforms') {
								$class = " class=\"add_logo logo_{$result['id']}\"";
                        	} else {
								$class = '';
                        	}
                            $use[] = '<a href="'.get_url($type, $result['id'])."\"{$class}>{$result['name']}</a>".($include_counts ? "&nbsp;({$result['num']})" : '');
                        } else {
                            $use[] = $result['name'].($include_counts ? " ({$result['num']})" : '');
                        }
                        if ($i == $limit) {
                            $output2 = array();
                            $use = &$output2;
                        }
                        $i++;
                    }
                    $output = implode($output, $separator);
                    $total_results = count ($results);
                    if (isset($output2) && $output2) {
                        $output .= "<span id=\"{$type}{$user_id}-show-more\" class=\"show-more\"> and <strong><a id=\"{$type}{$user_id}-more\" href=\"#\">".($total_results-$limit).' more</strong></a></span>';
                        $output .= "<span id=\"{$type}{$user_id}-extras\" class=\"extras\">{$separator}".implode($output2, $separator).'</span>';
                        global $include_in_footer;
                        $include_in_footer ['subhead_javascript']["{$type}{$user_id}"] = true;
                    }
                    return $output;
                }
            }
        }

        function user_has_some_resources() {
			if ($user_id = get_signed_in_userid()) {
		        $num_resources = $this->get_num_users_resources($user_id);
		        if ($num_resources != 0 && $num_resources < $this->get_num_resources()) {
		        	return true;
				} else {
		        	return false;
				}
			}
        }

        function do_header() {
    		do_header($this->get_name());
        }

	    function do_subhead_table() {
		    echo "<table class=\"subhead\">";
		    $lines ['Resources'] = $this->get_num_resources('resource', 'resources');
		    /*
		    if ($this->supports_users()) {
		    	$lines ['Copies'] = $this->get_num_users('user holds', 'users hold').' '.$this->get_num_copies('copy', 'copies');
			}
			*/
		    if (get_class($this) == 'author') {
		    	$lines ['Co-authors'] = $this->get_as_text_list('authors', 15);
			} else {
		    	$lines ['Authors'] = $this->get_as_text_list('authors', 15);
			}
		    $lines ['Publishers'] = $this->get_as_text_list ('publishers', 10);
		    $lines ['Series'] = $this->get_as_text_list ('series', 10);
		    $lines ['Subjects'] = $this->get_as_text_list ('subjects', 10);
		    $lines ['Languages'] = $this->get_as_text_list ('languages');
		    $lines ['Types'] = $this->get_as_text_list ('types', 10, '', ', ', false);
		    $lines ['Compatibility'] = $this->get_as_text_list ('platforms', 7);
		    //$lines ['Owners'] = $this->get_as_text_list ('owners', 7, '', ', ', false);
		    foreach ($lines as $heading => $text) {
		        if ($text) {
		            echo '<tr><th scope="row">'.$heading.':</th><td>'.$text.'</td></tr>';
		        }
		    }
		    echo '</table>';
	    }

	    function do_ownership_badge() {
		    $current_user_id = get_signed_in_userid();
		    if ($current_user_id) {
		        $total_resources = $this->get_num_resources();
		        $num_resources = $this->get_num_users_resources($current_user_id);
		        if ($total_resources == 1) {
			        if ($num_resources == 1) {
			            echo '<p><span class="label label-success">You have the only known resource '.$this->get_by_label().'</span></p>';
			        } elseif ($num_resources == 0) {
			            echo '<p><span class="label label-danger">You don&rsquo;t have the only known resource '.$this->get_by_label().'</span></p>';
			        }
		        } else {
			        if ($num_resources == $total_resources) {
			            echo '<p><span class="label label-success">You have all '.$num_resources.' known resources '.$this->get_by_label().'</span></p>';
			        } elseif ($num_resources == 0) {
			            echo '<p><span class="label label-danger">You don&rsquo;t have any resources '.$this->get_by_label().'</span></p>';
			        } else {
			            echo '<p><span class="label label-warning">You have '.$num_resources.' out of '.$total_resources.' known resources '.$this->get_by_label().'</span></p>';
			        }
				}
		    }
		}

		function do_detailed_view() {
			$resources = $this->get_resources ();
		    if ($resources) {
		    	$possible_columns = array ('cover', 'title', 'author', 'publisher', 'series', 'subject', 'year');
		    	if ($index = array_search (get_called_class(), $possible_columns)) {
					unset ($possible_columns[$index]);
		    	}
		        echo start_table ($possible_columns, 'resources', 'table-hover clear resources');
		        $i=0;
		        foreach ($resources as $resource) {
		        	$i++;
		            $class = $resource->do_i_have_it() ? 'owned' : 'not-owned';
		            echo '<tr class="'.$class.'">';
		            foreach ($possible_columns as $column) {
		            	if ($column == 'cover')
			            	echo do_table_cell ("<a href=\"".$resource->get_link()."\">".$resource->get_cover(false, true)."</a>", $class);
			            elseif ($column == 'title')
			            	echo do_table_cell ($resource->get_title(true, true), $class);
			            elseif ($column == 'author')
			            	echo do_table_cell ($resource->get_authors_names_as_text_list (true, '<br/>'), $class);
			            elseif ($column == 'publisher')
			            	echo do_table_cell ($resource->get_publishers_as_text_list (true, '<br/>'), $class);
			            elseif ($column == 'series')
			            	echo do_table_cell ($resource->get_series_as_text_list(true, '<br/>'), $class);
                        elseif ($column == 'subject')
                            echo do_table_cell ($resource->get_subjects_as_text_list(true, '<br/>'), $class);
			            elseif ($column == 'rating')
			            	echo do_table_cell ($resource->get_average_rating(1), $class.' rating');
			            elseif ($column == 'year')
			            	echo do_table_cell ($resource->get_year(true), $class);
					}
		            echo "</tr>\r\n";
		            if ($i >= 750) {
						echo "<tr><td colspan=\"".count($possible_columns)."\">This grid is limited to the first 750 resources (out of ".number_format (count($resources))."). All the resources are shown in the bookshelf above.</td></tr>.";
						break;
		            }
		        }
		        echo close_table ();
		    }
		}

        function do_main_page() {
	        if ($this->is_valid()) {
	            do_header($name = $this->get_name(), 'author-page');
	            $this->do_subhead_table();
	            $this->do_ownership_badge();
	            echo "<h3>{$this->get_name()}&rsquo;s Shelf</h3>";
	            $resources = $this->get_resources ();
	            if ($resources) {
	                $num_resources = count($resources);
	                echo '<table class="shelf-small"><tr>';
	                $shelf='';
	                foreach ($resources as $resource) {
	                        $src = "http://covers.logoscdn.com/".str_replace (':', '_', strtolower($resource->get_id()))."/50x80/cover.jpg";
	                        $shelf .= "<a href=\"resource.php?resource=".urlencode($resource->get_id())."\"><img src=\"{$src}\" width=50 height=80 title=\"{$resource->get_title()}\"></a>";
	                }
	                echo do_table_cell ($shelf);
	                echo '</tr></table>';
	                echo "<h3>Detailed view</h3>";
	                if ($this->user_has_some_resources()) {
	                    echo tabs_header (array ('all-resources' => 'All resources', 'my-resources' => 'My resources', 'not-my-resources' => 'Not my resources'), 1, '');
	                    global $include_in_footer;
	                    $include_in_footer['show-my-resources'] = true;
	                }
	                $this->do_detailed_view();
	            }
	        }
        }
    }