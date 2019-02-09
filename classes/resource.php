<?php
    class resource {

        private $resource_id, $type, $title, $publication_date, $description, $alternate_resource_id, $edition, $traits, $languages, $num_copies;
        public $product;

        function __construct ($resource, $update_cache_if_needed = true) {
            global $db;
            $update_product = false;
            if (is_array($resource)) {
            	$details = $resource;
            	$this->resource_id = $resource['resource_id'];
			} else {
				$this->resource_id = $resource;
            	$details = $db->get_row ('SELECT * FROM resources WHERE resource_id=:resource_id', array (':resource_id' => $this->resource_id));
                if ($update_cache_if_needed && (time()-strtotime($details['last_updated'])) > 604800) { // 1 week
                    $this->update_metadata();
					$update_product = true;
                    $details = $db->get_row ('SELECT * FROM resources WHERE resource_id=:resource_id', array (':resource_id' => $this->resource_id));
                }
			}
            if ($details) {
                $this->valid = true;
                foreach ($details as $column => $value) {
                    $this->$column = $value;
                }
                $this->raw_id = substr($this->resource_id,4);
                $authors = $db->get_results('SELECT a.* FROM authors AS a, resources_authors AS ra WHERE a.author_id=ra.author_id AND resource_id=:resource_id', array (':resource_id' => $this->resource_id));
                if ($authors) {
                    $this->authors = array();
                    foreach ($authors as $author) {
                        $this->authors[] = new author ($author);
                    }
                } else {
                    $this->authors = false;
                }
                $this->get_product($update_product);
            } else {
                $this->valid = false;
            }
        }

        function get_id() {
            if ($this->valid) {
                return $this->resource_id;
            }
        }

        function get_alternate_id() {
            if ($this->alternate_resource_id) {
                return $this->alternate_resource_id;
            } else {
                return $this->resource_id;
            }
        }

        function get_title($include_link = false, $include_icon = false) {
            if ($this->valid) {
                $title = $this->title;
                /*
                $file_history = $this->get_file_history_as_array();
                if (empty($file_history)) {
                    $title = "*{$title}";
                }
                */
                if ($include_link) {
                    $link_class = $this->is_vyrso_edition() ? 'vyrso' : 'logos_com';
                    $link_class = $include_icon ? " class=\"add_logo {$link_class}\"" : '';
                    return "<a{$link_class} href=\"{$this->get_link()}\">{$title}</a>";
                } else {
                    return $title;
                }
            }
        }

        function get_link() {
            if ($this->valid)
                return get_url('resource', $this->resource_id);
        }

        function get_edition() {
            if ($this->valid) {
                if (!isset($this->edition)) {
                    if (strtolower(substr($this->resource_id, 0, 3)) == 'pbb') {
                        $this->edition = 'pbb';
                    } elseif ($this->check_trait('tradebook')) {
                        $this->edition = 'vyrso';
                    } else {
                        $this->edition = 'logos';
                    }
                }
                return $this->edition;
            }
        }

        function is_logos_edition() {
            return ($this->get_edition() == 'logos');
        }

        function is_vyrso_edition() {
            return ($this->get_edition() == 'vyrso');
        }

        function is_pbb() {
            return (substr($this->resource_id, 0, 4) == 'PBB:');
        }

        function is_lls() {
        	$resource_id = $this->get_id();
        	$datasets = get_dataset_resource_ids_as_array();
            if (in_array ($resource_id, $datasets) || ($four = substr($resource_id, 0, 4)) == 'RVI:' || $four == ':PBB' || ($three = substr($resource_id, 0, 3) == 'DB:')) {
				return false;
			} else {
            	return true;
			}
        }

        function get_traits() {
            global $db;
            if ($this->valid) {
                if (!isset($this->traits)) {
                    $this->traits = $db->get_col('SELECT traits.trait FROM traits, resources_traits WHERE traits.trait_id=resources_traits.trait_id AND resource_id=:resource_id', array (':resource_id' => $this->resource_id));
                }
            }
            return $this->traits;
        }

        function check_trait ($trait) {
            $traits = $this->get_traits();
            if ($traits) {
                return (in_array($trait, $traits));
            }
        }

        function get_type_name($include_link) {
            $include_link = false; // Type page not build yet.
        	global $db;
        	if (!isset($this->type_name)) {
				$this->type_name = $db->get_var('SELECT type FROM types WHERE type_id=:type', array (':type' => $this->type));
        	}
        	if ($include_link) {
				return "<a href=\"".get_url('type', $this->type)."\">{$this->type_name}</a>";
        	} else {
				return $this->type_name;
			}
        }

        function get_languages_as_text_list($include_link) {
        	global $db;
        	if (!$this->languages) {
				$this->languages = $db->get_results('SELECT languages.language_id, languages.language FROM languages, resources_languages WHERE languages.language_id=resources_languages.language_id AND resources_languages.resource_id = :resource_id', array (':resource_id' => $this->resource_id));
        	}
        	if ($include_link) {
        		$link = array();
        		foreach ($this->languages as $language) {
        			$link[] = "<a href=\"".get_url('language', $language['language_id'])."\">{$language['language']}</a>";
				}
				return implode (', ', $link);
        	} else {
				return $this->languages;
			}
        }

        function get_series_as_array() {
            global $db;
            if ($this->valid) {
                return $db->get_results('SELECT series FROM resources WHERE resource_id=:resource_id AND series IS NOT NULL', array(':resource_id' => $this->resource_id));
            }
        }

        function get_series_as_text_list ($include_links = false, $separator = ', ') {
            if ($this->valid) {
                $series = $this->get_series_as_array();
                $output = array();
                if ($series) {
                    foreach ($series as $s) {
                        if ($include_links) {
                            $output[] = '<a href="'.get_url('series', $s['series'])."\">{$s['series']}</a>";
                        } else {
                            $output[] = $s['series'];
                        }
                    }
                }
                if ($output) {
                    return implode($output, $separator);
                }
            }
        }

        function published_by($include_links = false) {
            if ($this->valid) {
                $output = '';
                if ($this->publisher) {
                    $output .= $this->get_publisher($include_links);
                }
                if ($this->publication_date) {
                    $output .= " in ".$this->get_year($include_links);
                }
                return trim($output);
            }
        }

        function get_year ($include_link = false) {
            $include_link = false;
            if ($this->valid && $this->publication_date) {
                if ($include_link) {
                    return "<a href=\"".get_url('year',$this->publication_date)."\">{$this->publication_date}</a>";
                } else {
                    return $this->publication_date;
                }
            }
        }

        function get_authors_names_as_array($include_links = false) {
            if ($this->valid && $this->authors) {
                $output = array();
                foreach ($this->authors as $author) {
                    if ($include_links) {
                        $output[] = '<a href="'.$author->get_url().'">'.$author->get_name().'</a>';
                    } else {
                        $output[] = $author->get_name();
                    }
                }
                return $output;
            }
        }

        function get_authors_names_as_text_list ($include_links = false, $separator = ', ') {
            if ($this->valid) {
                $authors = $this->get_authors_names_as_array($include_links);
                if ($authors) {
                    return implode($authors, $separator);
                }
            }
        }

        function get_publishers_as_array() {
            global $db;
            if ($this->valid) {
                return $db->get_results('SELECT publisher, publishers.publisher_id FROM publishers, resources_publishers WHERE publishers.publisher_id=resources_publishers.publisher_id AND resource_id=:resource_id ORDER BY publisher ASC', array(':resource_id' => $this->resource_id));
            }
        }

        function get_publishers_as_text_list ($include_links = false, $separator = ', ') {
            if ($this->valid) {
                $publishers = $this->get_publishers_as_array();
                if ($publishers) {
                    $output = array();
                    foreach ($publishers as $publisher) {
                        if ($include_links) {
                            $output[] = '<a href="'.get_url('publisher', $publisher['publisher_id'])."\">{$publisher['publisher']}</a>";
                        } else {
                            $output[] = $publisher['publisher'];
                        }
                    }
                    if ($output) {
                        return implode($output, $separator);
                    }
                }
            }
        }

        function get_subjects_as_array() {
            global $db;
            if ($this->valid) {
                return $db->get_results('SELECT subject, subjects.subject_id FROM subjects, resources_subjects WHERE subjects.subject_id=resources_subjects.subject_id AND resource_id=:resource_id ORDER BY subject ASC', array(':resource_id' => $this->resource_id));
            }
        }

        function get_subjects_as_text_list ($include_links = false, $separator = ', ') {
            if ($this->valid) {
                $subjects = $this->get_subjects_as_array();
                if ($subjects) {
                    $output = array();
                    foreach ($subjects as $subject) {
                        if ($include_links) {
                            $output[] = '<a href="'.get_url('subject',$subject['subject_id'])."\">".format_subject($subject['subject'])."</a>";
                        } else {
                            $output[] = $subject['subject'];
                        }
                    }
                    if ($output) {
                        return implode($output, $separator);
                    }
                }
            }
        }

        function get_traits_as_array() {
            global $db;
            if ($this->valid) {
                return $db->get_results('SELECT trait, traits.trait_id FROM traits, resources_traits WHERE traits.trait_id=resources_traits.trait_id AND resource_id=:resource_id ORDER BY trait ASC', array(':resource_id' => $this->resource_id));
            }
        }

        function get_traits_as_text_list ($include_links = false, $separator = ', ', $limit = 3) {
            if ($this->valid) {
                $traits = $this->get_traits_as_array();
                if ($traits) {
                    $output = array();
                    foreach ($traits as $trait) {
                        if ($include_links) {
                        	$t ['name'] = '<a href="'.get_url('trait',$trait['trait_id'])."\">".urldecode($trait['trait'])."</a>";
                        } else {
                            $t ['name'] = urldecode($trait['trait']);
                        }
                        $output[] = $t;
                    }
                    if ($output) {
                    	return $this->get_as_text_list ($output, 'traits', $separator, false, $limit);
                    }
                }
            }
        }

        function get_platforms_as_array() {
            global $db;
            if ($this->valid) {
                return $db->get_results('SELECT platform, platforms.platform_id FROM platforms, resources_platforms WHERE platforms.platform_id=resources_platforms.platform_id AND resource_id=:resource_id ORDER BY platform ASC', array(':resource_id' => $this->resource_id));
            }
        }

        function get_platforms_as_text_list ($include_links = false, $separator = ', ', $limit = 99) {
            if ($this->valid) {
                $platforms = $this->get_platforms_as_array();
                if ($platforms) {
                    $output = array();
                    foreach ($platforms as $platform) {
                        if ($include_links) {
							$class = " class=\"add_logo logo_{$platform['platform_id']}\"";
                        	$t ['name'] = '<a href="'.get_url('platform',$platform['platform_id'])."\"{$class}>".$platform['platform']."</a>";
                        } else {
                            $t ['name'] = $platform['platform'];
                        }
                        $output[] = $t;
                    }
                    if ($output) {
                    	return $this->get_as_text_list ($output, 'platforms', $separator, false, $limit);
                    }
                }
            }
        }

        function get_tags_as_array($user_id) {
            global $db;
            if ($this->valid) {
                $sql = 'SELECT tag, SUM(count) AS num FROM community_tags WHERE resource_id=:resource_id '.($user_id ? 'AND user_id=:user_id ' : '').'GROUP BY tag ORDER BY num DESC, tag ASC';
                if ($user_id) {
                    return $db->get_results($sql, array(':resource_id' => $this->resource_id, ':user_id' => $user_id));
                } else {
                    return $db->get_results($sql, array(':resource_id' => $this->resource_id));
                }
            }
        }

        function get_tags_as_text_list ($include_links = false, $include_counts = true, $separator = ', ', $user_id = '') {
            if ($this->valid) {
                $tags = $this->get_tags_as_array($user_id);
                $output = array();
                if ($tags) {
                    foreach ($tags as $tag) {
                        if ($include_links) {
                            $output[] = '<a href="'.get_url('tag',$tag['tag'])."\">{$tag['tag']}</a>".($include_counts ? "&nbsp;({$tag['num']})" : '');
                        } else {
                            $output[] = $s['tag'].($include_counts ? " ({$tag['tag']})" : '');
                        }
                    }
                }
                if ($output) {
                    return implode($output, $separator);
                }
            }
        }

        function get_rating_value() {
            if (isset($this->rating_value)) {
                return $this->rating_value;
            } else {
                return 0;
            }
        }

        function get_rating_count() {
            if (isset($this->rating_count)) {
                return $this->rating_count;
            } else {
                return 0;
            }
        }

        function get_average_rating ($decimal_places = 0) {
            return format_ratings (array ('average' => $this->get_rating_value(), 'count' => $this->get_rating_count()), $decimal_places, false);
        }

        function get_num_ratings () {
            global $db;
            return $db->get_var ('SELECT COUNT(user_rating) FROM resources_users WHERE resource_id=:resource_id', array(':resource_id' => $this->resource_id));
        }

        function get_num_copies ($singular = '', $plural = '') {
            global $db;
            if ($this->num_copies === NULL) {
				$this->num_copies = $db->get_var ('SELECT COUNT(resource_id) FROM resources_users WHERE resource_id=:resource_id', array(':resource_id' => $this->resource_id));
            }
            if ($singular == '') {
                return $this->num_copies;
            } elseif ($this->num_copies == 1) {
                return "{$this->num_copies} {$singular}";
            } else {
                return "{$this->num_copies} {$plural}";
            }
        }

        private function xml_to_text ($input) {
            $string = '';
            foreach ($input as $k => $v) {
                if (is_array($v) || is_object($v)) {
                    if ($k == 'Paragraph') {
                        $string .= '<p>'.trim($this->xml_to_text ($v)).'</p>';
                    } else {
                        $string .= $this->xml_to_text ($v);
                    }
                } else {
                    $string .= (string)$v;
                }
            }
            if ($input->attributes() && isset($input->attributes()->Text))
                $string .= (string)$input->attributes()->Text;
            return $string;
        }

        function get_description() {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($this->description);
            if (!$xml) {
                return '<p><em>No description available.</em></p>';
            }
            return $this->xml_to_text($xml);
        }

        function get_cover ($raw = true, $small=false, $classes = '') {
            if ($this->is_pbb()) {
                return false;
            }
            $url_size = $small ? '/50x80' : '';
            $src = "https://covers.logoscdn.com/".str_replace (':', '_', strtolower($this->resource_id))."{$url_size}/cover.jpg";
            if ($raw) {
                return $src;
            } else {
                $img_size = $small ? ' width="50" height="80"' : ' width="171" height="256"';
                if ($classes) {
                    $classes .= " class=\"{$classes}\"";
                }
                return "<img src=\"{$src}\"{$classes}{$img_size}>";
            }
        }

        function read_it () {
            $output = '<strong>Read it</strong> ';
            $output .= '<a href="logosres:'.$this->raw_id.'" class="read-in-logos">in Logos</a>, ';
            $output .= '<a href="http://biblia.com/books/'.$this->raw_id.'" class="read-on-biblia">on Biblia.com</a>, ';
            $output .= '<a href="http://bible.faithlife.com/books/'.$this->raw_id.'" class="read-on-faithlife">on Faithlife</a>.';
            return $output;
        }

        function do_i_have_it() {
            if ($user_id = get_signed_in_userid()) {
                return is_in_library_catalog ($this->resource_id, $user_id);
            }
        }

        function have_i_hidden_it() {
			if ($user_id = get_signed_in_userid()) {
				return is_hidden ($this->resource_id, $user_id);
			}
        }

        function get_logos_desktop_link() {
            return "logosres:".urlencode(str_ireplace('lls:', '', $this->get_id()));
        }

        function get_vyrso_link() {
            return "https://www.logos.com/resource/".urlencode(str_replace(':', '$', $this->resource_id));
        }

        function get_logos_com_link() {
            return "https://www.logos.com/resources/".urlencode(str_replace(':', '$', $this->resource_id));
        }

        function get_faithlife_link() {
            return "https://bible.faithlife.com/books/".urlencode(str_ireplace('lls:', '', $this->get_alternate_id()));
        }

        function get_app_link() {
            return "https://app.logos.com/books/".urlencode(str_ireplace('lls:', '', $this->get_alternate_id()));
        }

        function get_biblia_link() {
            return "https://biblia.com/books/".urlencode(str_ireplace('lls:', '', $this->resource_id));
        }

        function go_to() {
            $edition = $this->get_edition();
            $links['logos_desktop'] = array ('url' => $this->get_logos_desktop_link(), 'name' => 'Logos Desktop');
            if (!get_signed_in_userid() || $this->do_i_have_it()) {
                if (!$this->is_pbb()) {
                    $links['biblia'] = array ('url' => $this->get_biblia_link(), 'name' => 'Biblia');
                    $links['faithlife'] = array ('url' => $this->get_faithlife_link(), 'name' => 'Faithlife Bible');
                    $links['app'] = array ('url' => $this->get_app_link(), 'name' => 'app.logos.com');
                }
            }
            if ($this->is_logos_edition()) {
                $links['logos_com'] = array ('url' => $this->get_logos_com_link(), 'name' => 'logos.com');
            } elseif ($this->is_vyrso_edition()) {
                $links['vyrso'] = array ('url' => $this->get_vyrso_link(), 'name' => 'Vyrso');
            }
            $output = array();
            foreach ($links as $link_class => $link) {
                $target = $link_class == 'logos_desktop' ? '' : "target=\"_blank\" ";
                $output[] = "<a {$target}class=\"add_logo {$link_class}\" href=\"{$link['url']}\">{$link['name']}</a>";
            }
            return implode(', ', $output);
        }

        function add_product_to_resource($product_code, $other_resources_also_in_product = FALSE) {
            global $db;
            if (is_null($product_code)) {
				throw new Exception ('Cannot add NULL product id');
            }
            $db->query ('INSERT IGNORE INTO resources_products (product_id, resource_id, included_in) VALUES (:product_id, :resource_id, :included_in)', array (':product_id' => $product_code, ':resource_id' => $this->resource_id, ':included_in' => $other_resources_also_in_product));
        }

        function get_as_text_list ($results, $type, $separator = ', ', $include_extra_info = true, $limit = 7) {
            $output = array();
            $use = &$output;
            $i = 1;
            foreach ($results as $result) {
            	$use[] = $result['name'].(($include_extra_info && isset($result['extra']) && $result['extra']) ? " ({$result['extra']})" : '');
                if ($i == $limit) {
                    $output2 = array();
                    $use = &$output2;
                }
                $i++;
            }
            $output = implode($output, $separator);
            $total_results = count ($results);
            if (isset($output2) && $output2) {
                $output .= "<span id=\"{$type}-show-more\" class=\"show-more\"> and <strong><a id=\"{$type}-more\" href=\"#\">".($total_results-$limit).' more</strong></a></span>';
                $output .= "<span id=\"{$type}-extras\" class=\"extras\">{$separator}".implode($output2, $separator).'</span>';
                global $include_in_footer;
                $include_in_footer ['subhead_javascript']["{$type}"] = true;
            }
            return $output;
        }

        function get_file_history_as_array() {
			global $db;
			return $db->get_results('SELECT filename, filesize, minimum_version, platform, version, date_published FROM file_history WHERE resource_id=:resource_id ORDER BY version DESC', array (':resource_id' => $this->resource_id));
        }

        function get_file_history_as_text_list($separator = '<br/>') {
        	$file_history = $this->get_file_history_as_array();
        	if ($file_history) {
        		$output = array();
				foreach ($file_history as $file) {
					$t['name'] = date('j M Y', strtotime($file['version']));
					$t['extra'] = 'requires '.return_logos_version($file['minimum_version'], $file['platform']);
					$output [] = $t;
				}
				return $this->get_as_text_list ($output, '', ', ', true, 1);
        	}

        }

        function get_isbn() {
			return $this->isbn;
        }

        function get_milestones_as_text_list() {
        	$error_level = error_reporting (E_ALL ^ E_NOTICE);
        	restore_error_handler();
			$milestones = unserialize($this->milestone_indexes);
			if ($milestones === FALSE) {
				//Probably caused by a previous database error
				require ('includes/metadata_functions.php');
				update_metadata ((array)$this->resource_id);
				$this->__construct($this->resource_id);
				$milestones = unserialize($this->milestone_indexes);
			}
        	error_reporting ($error_level);
			if (DEBUG) {
			    set_error_handler ('handle_error');
			}
			$supports_page = false;
			$output = array();
			if (is_object($milestones) && isset($milestones->MilestoneIndex)) {
				if (!is_array($milestones->MilestoneIndex)) {
					$mi = array();
					$mi[0] = $milestones->MilestoneIndex;
					$milestones->MilestoneIndex = $mi;
				}
				foreach ($milestones->MilestoneIndex as $m) {
					$output[] = $m->DataType;
					if (in_array ($m->DataType, array ('page', 'vp'))) {
						$supports_page = true;
					}
				}
			}
			return trim(implode($output, ', ').(!$supports_page ? ' <strong>(no page numbers)</strong>' : ''));
        }

        function get_product($download_new_data_if_needed = false) {
        	global $db;
            if ($download_new_data_if_needed) {
                $this->download_product_link();
                $product = new product ($this->get_product_code(), true);
                return $this->get_product();
            }
        	if (!$this->valid || substr($this->resource_id, 0, 4) == 'PBB:') {
				return new product(null);
        	}
            if (is_a($this->product, 'product')) {
				return $this->product;
            } else {
	            if ($product_data = $db->get_row ('SELECT product_id, included_in FROM resources_products WHERE included_in != 1 AND resource_id=:resource_id', array(':resource_id' => $this->resource_id))) {
            		if ($product_data['product_id'] == -1) {
						$this->product = new product(null);
						return $this->product;
            		} else {
			            $this->product = new product ($product_data['product_id']);
			            return $this->product;
					}
				}
            }
        }

        function download_product_link() {
            global $db;
            if ($this->is_vyrso_edition()) {
                $response = download_url ($this->get_vyrso_link(), true);
                if (isset($response['header'])) {
                    $location = get_final_http_location($response['header']);
                    if ($location) {
                        $this->add_product_to_resource($location['id']);
                    }
                }
            } elseif ($this->is_logos_edition()) {
                $html = download_url ($this->get_logos_com_link());
                if (strpos ($html, 'Buy It</a>') !== FALSE) {
                    $product = extract_text ($html, '<div class="rating-stars">', 'Buy It</a></div>');
                    $product_id = extract_text ($product, '<a href="/product/', '/');
                    $this->add_product_to_resource($product_id);
                }
                if (strpos ($html, '<li id="also-available-in">') !== FALSE) {
                    $also_in = extract_text ($html, '<h3>This title is included as a part of the following collections.', '<h3>');
                    $also_in = explode ('clearfix">', $also_in);
                    unset ($also_in[0]);
                    foreach ($also_in as $a) {
                        $product_id = extract_text ($a, '<a href="/product/', '/');
                        $this->add_product_to_resource($product_id, true);
                    }
                }
                if (strpos ($html, '<h3>This title is also included as a part of the following collections</h3>') !== FALSE) {
                    $also_in = extract_text ($html, '<ol class ="collection-reviews">', '</ol>');
                    $also_in = explode ('<div class="collection-title">', $also_in);
                    unset ($also_in[0]);
                    foreach ($also_in as $a) {
                        $product_id = extract_text ($a, '<a href="/product/', '/');
                        $this->add_product_to_resource($product_id, true);
                    }
                }
            }
        }

        function get_product_code() {
            global $db;
        	if ($this->is_pbb() || $this->product === FALSE) {
				return null;
        	} elseif (is_a($this->product, 'product')) {
				return $this->product->get_product_code();
            } else {
				$product = $this->get_product();
                if (!is_a($product, 'product')) {
                    return -1;
                } else {
				    return $this->get_product_code();
                }
            }
        }

        function get_included_in_products() {
			global $db;
			if ($this->product !== FALSE && !is_a($this->product, 'product')) {
				$this->get_product();
			}
			$parents = $db->get_col ('SELECT product_id FROM resources_products WHERE resource_id=:resource_id AND included_in=TRUE', array (':resource_id' => $this->resource_id));
			if ($parents) {
				$p = array();
				foreach ($parents as $parent) {
					$prod = new product ($parent);
					if (!$prod->replaced_by) {
						$p[] = $prod;
					}
				}
				return $p;
			}
        }
        function update_metadata() {
            update_metadata ((array)$this->resource_id);
        }

        function get_product_name($include_link = false, $include_price = false, $include_icon = true) {
            if (!is_a($this->product, 'product')) {
                $this->get_product();
                if (is_a($this->product, 'product')) {
                    return $this->get_product_name($include_link, $include_price, $include_icon);
                }
            } else {
                if (!$this->product->is_valid()) {
                    return false;
                }
                if ($include_price) {
                    if ($price = $this->product->get_price()) {
                        $extra = ' ($'.number_format($price,2).')';
                    } else {
                        $extra = ' (n/a)';
                    }
                } else {
                    $extra = '';
                }
                return $this->product->get_name($include_link, $include_icon).$extra;
            }
        }

        function get_parent_products_as_text_list($separator = ', ', $include_links = true, $include_price = true, $include_icon = true, $limit = 7) {
            $other_parents = $this->get_included_in_products();
            $main_product = $this->get_product();
            if ($main_product === NULL && $other_parents === NULL) {
                return false;
            }
            if (is_a($main_product, 'product')  && $main_product->get_id()) {
                $parents = $main_product->get_parents();
            }
            if (isset($parents) && $parents && $other_parents) {
                array_merge($parents, $other_parents);
            } elseif ($other_parents) {
                $parents = $other_parents;
            }
            if (isset($parents) && $parents) {
                $products = array();
                if ($include_icon && !$this->is_pbb()) {
                    $edition = $this->get_edition();
                    if ($edition == 'logos') {
                        $class="logos_com add_logo";
                    } elseif ($edition == 'vyrso') {
                        $class="vyrso add_logo";
                    }
                } else {
                    $class = '';
                }
                if ($parents) {
                	usort ($parents, 'sort_products');
                    foreach ($parents as $parent) {
                        $line ['name'] = $parent->get_name (true, $class);
                        if ($include_price && ($price = $parent->get_price()) && $price != 0) {
                            $line ['extra'] = '$'.number_format($price,2);
                        } else {
							$line ['extra'] = '';
                        }
                        $products[] = $line;
                    }
                    return $this->get_as_text_list ($products, 'products', $separator, $include_price, $limit);
                }
            }
        }

        function has_product() {
            return !is_null($this->product);
        }

    }
?>