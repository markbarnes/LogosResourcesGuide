<?php
	class product {

		protected $code, $name, $description, $price, $site, $parents, $num_resources, $num_owned_resources;

		function __construct ($product_id, $ignore_cache = false) {
			global $db;
			if (!$product_id) {
				return;
			} elseif ((!$ignore_cache) && $product = $db->get_row ('SELECT product_id, name, description, price, site, new_id FROM products WHERE products.product_id=:product_id', array(':product_id' => $product_id), true)) {
	            $this->code = $product['product_id'];
	            $this->name = $product['name'];
	            $this->description = $product['description'];
	            $this->price = $product['price'];
	            $this->site = $product['site'];
	            $this->replaced_by = $product['new_id'];
            } else { // Product info is not cached
	            download_product_details ($product_id);
	            $this->__construct($product_id);
                //Makes sure products where the metadata update failed are removed
                //$db->query ('DELETE products FROM products LEFT JOIN resources_products ON products.product_id = resources_products.product_id WHERE resources_products.product_id IS NULL AND description !=  \'-1\'');
			}
		}

		function get_id() {
			return $this->code;
		}

		function get_product_code() {
			return $this->code;
		}

        function get_site() {
            return $this->site;
        }

		function get_name($include_link = false, $include_icon = false, $external_link = false) {
			if ($this->name == '') {
				$this->name = 'Untitled';
			}
            $class = '';
            if ($include_link && $include_icon) {
                $site = $this->get_site();
                if ($site == 'Vyrso') {
                    $class="vyrso add_logo";
                } else {
                    $class="logos_com add_logo";
                }
            }
			if ($include_link) {
				if ($class != '') {
					$class = " class=\"{$class}\"";
				}
                if ($external_link) {
                    $link = $this->get_external_link();
                    $target = ' target="_blank"';
                } else {
                    $link = $this->get_link();
                    $target = '';
                }
				return "<a{$target} href=\"{$link}\"{$class}>{$this->name}</a>";
			} else {
				return $this->name;
			}
		}

        function get_single_resource() {
            global $db;
            return $db->get_var ('SELECT resource_id FROM resources_products WHERE product_id=:product_id AND included_in = 0', array (':product_id' => $this->get_id()));
        }

        function get_link() {
        	return BASE_URL."/product.php?product_id=".$this->get_id();
        }

        function get_external_link() {
            if ($this->get_site() == 'Vyrso') {
                return $this->get_vyrso_link();
            } else {
                return $this->get_logos_link();
            }
        }

        function get_logos_link() {
			return "https://www.logos.com/product/{$this->get_id()}/";
        }

        function get_vyrso_link() {
            return "https://www.vyrso.com/product/{$this->get_id()}";
        }

        function get_image_url() {
            return "https://www.logos.com/images/products/{$this->get_id()}.jpg";
        }

		function get_price() {
			return $this->price;
		}

		function get_product_site() {
			return $this->price;
		}

		function get_parents() {
			global $db;
			$parents = $db->get_col ('SELECT parent_id FROM products_relationships WHERE child_id=:child_id', array (':child_id' => $this->code));
			if ($parents) {
				$p = array();
				foreach ($parents as $parent) {
					if ($parent != -1) {
						$p[] = new product ($parent);
					}
				}
				if (empty($p)) {
					$this->parents = false;
				} else {
					$this->parents = $p;
				}
				return $this->parents;
			} elseif (count($parents) == 0) { // Nothing in the database, need to download
				download_product_parents($this->code);
				return $this->get_parents();
			}
		}

		function is_valid() {
			return !($this->name === NULL);
		}

		function get_image() {
			return  "https://www.logos.com/product/{$this->code}/image.jpg";
		}

		function get_description() {
			if (!$this->description) {
				$this->download_description();
			}
			if ($this->description != '-1') {
				return $this->description;
			}
		}

        function get_resources($update_cache_if_needed = true) {
        	global $db;
        	if (!isset ($this->all_resources)) {
        		$resource_ids = $db->get_col ('SELECT resources_products.resource_id FROM resources, resources_products WHERE resources.resource_id=resources_products.resource_id AND product_id=:id ORDER BY title_sort', array (':id' => $this->code));
        		if ($resource_ids) {
        			$r = array();
					foreach ($resource_ids as $id) {
						$r[] = new resource ($id, false);
					}
					$this->all_resources = $r;
        		} else {
					$this->all_resources = array();
        		}
        		$children = $this->get_children();
        		if ($children) {
        			foreach ($children as $child) {
						$this->all_resources = array_merge ($this->all_resources, $child->get_resources(false));
        			}
				}
				$this->all_resources = array_unique($this->all_resources, SORT_REGULAR);
        	}
        	if ($update_cache_if_needed && $this->all_resources) {
				$dated_resource_ids = array();
				$timestamp = time()-604800; // 1 week ago
				foreach ($this->all_resources as $r) {
					if (strtotime($r->last_updated) < $timestamp) {
						$dated_resource_ids[] = $r->get_id();
					}
				}
				if ($dated_resource_ids) {
					update_metadata($dated_resource_ids);
					unset($this->all_resources);
					return $this->get_resources(false);
				}
        	}
			return $this->all_resources;
        }

        function get_num_resources() {
			if (!isset($this->num_resources)) {
				$this->num_resouces = count ($this->get_resources());
			}
			return $this->num_resouces;
        }

        function get_num_owned_resources() {
            if (isset ($this->num_owned_resources)) {
                return $this->num_owned_resources;
            }
            if ($user_id = get_signed_in_userid()) {
                $resources = $this->get_resources();
                $this->num_owned_resources = 0;
                if ($resources) {
                    foreach ($resources as $resource) {
                        if ($resource->do_i_have_it()) {
                            $this->num_owned_resources++;
                        }
                    }
                }
                return $this->num_owned_resources;
            }
            return false;
        }

        function get_num_unowned_resources() {
            return $this->get_num_resources()-$this->get_num_owned_resources();
        }

        function get_children_ids($id) {
			global $db;
			$ids = $db->get_col ('SELECT child_id FROM products_relationships WHERE parent_id=:id', array (':id' => $id));
			if ($ids) {
				foreach ($ids as $id) {
					$new_array [$id] = $id;
				}
				return $new_array;
			}
        }

        function get_children() {
        	global $db;
        	if (!isset ($this->child_products)) {
				$product_ids = $this->get_children_ids($this->code);
        		if ($product_ids) {
        			$p = array();
					foreach ($product_ids as $id) {
						$p[] = new product ($id);
					}
					$this->child_products = $p;
        		} else {
					$this->child_products = false;
        		}
        	}
			return $this->child_products;
        }

        function get_products_as_list ($products) {
			if ($products) {
				$p = '';
				foreach ($products as $product) {
					$p .= "<li>{$product->get_name(true)} ";
					$price = number_format($product->get_price(), 2);
					if ($price != 0) {
						$p .= " (\${$price})";
					}
					$p .= "</li>\r\n";
				}
				return $p;
			}
        }

        function get_list_from_array($array, $id='') {
        	global $include_in_footer;
        	if ($id != '') {
				$id = " id=\"{$id}\"";
        	}
			$lines = array();
			foreach ($array as $k => $v) {
				$p = new product ($k);
				$price = $p->get_price();
				if ($price) {
					$this_line['price'] = $price;
					$this_line['output'] = '<li>'.$p->get_name(true)." (\${$price})";
					$this_line['id'] = $k;
					if (is_array ($v)) {
						$k = "{$k}-".md5(microtime(true));
						$sub_list = $this->get_list_from_array($v, "product-{$k}");
						if ($sub_list) {
                			$include_in_footer ['product_javascript'][] = $k;
							$this_line['output'] .= ' &nbsp;<span id="expand-'.$k.'" style="font-size:75%" class="glyphicon glyphicon-chevron-down"></span>'.$sub_list;
						}
					}
					$this_line['output'] .= '</li>';
					$lines[] = $this_line;
					update_product_details_if_needed ($k);
				}
			}
			if ($lines) {
				$output = "<ul{$id}>";
				uasort ($lines, array (__CLASS__, 'sort_product_list_by_price'));
				foreach ($lines as $line) {
					$output .= $line['output'];
				}
				$output .= '</ul>';
				return $output;
			}
        }

	    function sort_product_list_by_price ($a, $b) {
			if ((float)$a['price'] < (float)$b['price']) {
				return 1;
			} elseif ((float)$a['price'] > (float)$b['price']) {
				return -1;
			} else {
				if ($a['id'] < $b['id']) {
					return -1;
				} elseif ($a['id'] > $b['id']) {
					return 1;
				}
			}
	    }

        function consolidate_product_array ($array) {
			if ($array) {
				// Add children into second level of array
				foreach ($array as $k => &$array_value) {
					$children = $this->get_children_ids($k);
					if ($children) {
						$array_value = $children;
					}
				}
				// Now remove any of those children from the top level
				foreach ($array as $k => $a) {
					if ($k != $a && is_array ($a)) {
						foreach ($a as $b) {
							unset ($array[$b]);
						}
					}
				}
				if (is_array ($array) && $array) {
					$new_array = array();
					// Do another iteration for second-level values
					foreach ($array as $k => $a) {
						if ($k != $a && is_array ($a)) {
							$new_array [$k] = $this->consolidate_product_array ($a);
						} else {
							$new_array [$k] = $a;
						}
					}
					return $new_array;
				}
			}
        }

        function get_child_products_as_list ($get_descendents = false) {
			$all_descendents = $this->get_children_ids($this->get_id());
			if ($all_descendents) {
				$all_descendents = $this->consolidate_product_array($all_descendents);
				return $this->get_list_from_array($all_descendents);
			}
        }

        function get_parent_products_as_list () {
			return $this->get_products_as_list ($this->get_parents());
        }

		function do_detailed_view() {
			$resources = $this->get_resources ();
		    if ($resources) {
		    	$possible_columns = array ('cover', 'title', 'author', 'publisher');
		    	if ($index = array_search (get_called_class(), $possible_columns)) {
					unset ($possible_columns[$index]);
		    	}
		        echo start_table ($possible_columns, 'resources', 'table-hover clear resources');
		        foreach ($resources as $resource) {
		            $class = $resource->do_i_have_it() ? 'owned' : 'not-owned';
		            if (!$resource->is_lls()) {
						$class = 'dataset dataset-'.$class;
		            }
		            echo '<tr class="'.$class.'">';
		            foreach ($possible_columns as $column) {
		            	if ($column == 'cover') {
		            		if (substr($class, 0, 7) =='dataset') {
								echo do_table_cell('', $class);
		            		} else {
			            		echo do_table_cell ("<a href=\"".$resource->get_link()."\">".$resource->get_cover(false, true)."</a>", $class);
							}
						} elseif ($column == 'title')
			            	echo do_table_cell ($resource->get_title(true, true), $class);
			            elseif ($column == 'author')
			            	echo do_table_cell ($resource->get_authors_names_as_text_list (true, '<br/>'), $class);
			            elseif ($column == 'publisher')
			            	echo do_table_cell ($resource->get_publishers_as_text_list (true, '<br/>'), $class);
					}
		            echo "</tr>\r\n";
		        }
		        echo close_table ();
		    }
		}

		function download_description() {
			global $db;
			$html = file_get_contents("https://www.logos.com/comparison/resource/tooltip/{$this->get_id()}");
			if ($html && strpos ($html, '<div class="product-blurb">') !== FALSE) {
				$description = extract_text ($html, '<div class="product-blurb">', '</div>');
			}
			if (!isset($description) || !$description) {
				$description = '-1';
			}
			$this->description = $description;
			$db->query("UPDATE products SET description=:description WHERE product_id=:product_id", array (':description' => $description, ':product_id' => $this->get_id()));
		}

        function get_num_users_resources($user_id) {
            global $db;
            if ($user_id) {
	            $all_products = $this->get_children_ids($this->get_id());
	            $all_products[] = $this->get_id();
	            $all_products = implode (', ', $all_products);
	            $exclude_string = '"'.implode ('", "', get_dataset_resource_ids_as_array()).'"';
	            return $db->get_var ("SELECT COUNT(DISTINCT resources_users.resource_id) FROM resources_products, resources_users WHERE resources_products.resource_id=resources_users.resource_id AND resources_users.resource_id NOT IN (".$exclude_string.") AND product_id IN ({$all_products}) AND user_id=:user_id",array(':user_id'=>$user_id));
			}
        }

        function user_has_some_resources() {
			if ($user_id = get_signed_in_userid()) {
		        $resources = $this->get_resources();
                if ($resources) {
                    foreach ($resources as $resource) {
                        if ($resource->do_i_have_it()) {
                            return true;
                        }
                    }
                }
			}
            return false;
        }

        function has_datasets() {
        	if (!isset($this->has_datasets) || $this->has_datasets == NULL) {
				$resources = $this->get_resources();
				$this->has_datasets = false;
				if ($resources) {
					foreach ($resources as $resource) {
						if (!$resource->is_lls()) {
							$this->has_datasets = true;
							break;
						}
					}
				}
	        }
			return $this->has_datasets;
		}

        function buy_now_button() {
            $code = $this->get_id();
            $price = $this->get_price();
            if ($price) {
                $price = number_format($price, 2);
                return " <span title=\"Retail price is shown. Actual price may be lower.\" class=\"label label-primary\"><a target=\"_blank\" href=\"https://www.logos.com/buy/{$code}\">Buy Now for \${$price}</a></span>";
            } else {
                return " <span class=\"label label-default\">Not available individually</span>";
            }
        }
    }