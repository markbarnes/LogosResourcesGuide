<?php

    require ('user_functions.php');
    require ('query_functions.php');
    require ('template_functions.php');

    spl_autoload_register('autoload_classes');

    function autoload_classes ($class) {
        require ("classes/{$class}.php");
    }

    function detect_os() {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], "Macintosh") !== FALSE)
                return 'mac';
            elseif (strpos($_SERVER['HTTP_USER_AGENT'], "Windows") !== FALSE)
                return 'windows';
        }
        return 'unknown';
    }

    function redirect_to_page ($location, $code = 302) {
        header("Location: {$location}", true, $code);
        die();
    }

    function get_url ($type, $item = '') {
        if ($type != 'series' && substr($type, -1) == 's') {
            $type = substr($type, 0, -1);
        }
        return BASE_URL."/{$type}.php".($item != '' ? "?{$type}=".$item : '');
    }

    function extract_text ($text, $before, $after) {
        $start = stripos ($text, $before)+strlen($before);
    	if (is_array ($after)) {
    		$preferred_length = 9999999999;
			foreach ($after as $a) {
        		$length = stripos ($text, $a, $start)-$start;
        		if ($length < $preferred_length && $length != 0) {
					$preferred_length = $length;
        		}
			}
			$length = $preferred_length;
    	} else {
    		if ($start > strlen($text)) {
				return;
    		}
			$length = stripos ($text, $after, $start)-$start;
    	}
        if ($start && ($length > 0) && $length != 9999999999) {
            return trim(substr($text, $start, $length), " \r\n\t");
        }
    }

    function get_option ($name) {
		global $db;
		return $db->get_var ('SELECT option_value FROM options WHERE option_name=:name', array (':name' => $name));
    }

    function set_option ($name, $value) {
		global $db;
		return $db->query ('INSERT INTO options (option_name, option_value) VALUES (:option_name, :option_value) ON DUPLICATE KEY UPDATE option_value=:option_value', array (':option_name' => $name, ':option_value' => $value));
    }

	function get_http_locations ($headers) {
		if (is_array($headers)) {
			$location = array();
			foreach ($headers as $header) {
				if (substr($header, 0, 9) == 'Location:') {
					$name = ucwords(str_replace('-', ' ', substr($header, strrpos($header, '/')+1)));
					$id = extract_text ($header, 'product/', '/');
					$location[] = array ('id' => $id, 'name' => $name);
				}
			}
			return $location;
		}
	}

    function get_final_http_location ($headers) {
        $locations = get_http_locations($headers);
        if (is_array($locations)) {
            return array_pop ($locations);
        }
    }

	function download_product_details ($product_id) {
		global $db;
		$timer = microtime(true);
		// Add a placeholder so parallel tasks don't also download the same page
		$db->query ("REPLACE INTO products (product_id, site) VALUES (:product_id, :site)", array (':product_id' => $product_id, ':site' => 'Downloading'));
	    $db->query ('DELETE FROM products_relationships WHERE child_id=:product_id', array(':product_id' => $product_id));
	    if (DEBUG && is_cli()) {
			echo "Downloading product {$product_id}... ";
	    }
		$url = "https://www.logos.com/product/";
		$download = download_url ($url.$product_id, true);
		$html = html_entity_decode($download['body'], ENT_QUOTES, 'UTF-8');
        $http_response_header = $download['header'];
		if ($html) {
			$title = trim(extract_text ($html, '<title>', " - Logos Bible Software\r\n\t</title>"));
		    if (DEBUG && is_cli()) {
				echo $title;
		    }
			$locations = get_http_locations($http_response_header);
			if (($c = count($locations)) > 1 || (isset($locations[0]['id']) && ($locations[0]['id'] != $product_id))) {
				if ($locations[$c-1]['id'] == FALSE) {
					$db->query ("REPLACE INTO products (product_id, name, site) VALUES (:product_id, :name, :site)", array (':product_id' => $product_id, ':name' => $locations[0]['name'], ':site' => 'Logos'));
				} else {
					$new_id = (int)$locations[$c-1]['id'];
					$db->query ("REPLACE INTO products (product_id, name, new_id, site) VALUES (:product_id, :name, :new_id, :site)", array (':product_id' => $product_id, ':name' => $locations[0]['name'], ':new_id' => $new_id, ':site' => 'Logos'));
					if ($new_id) {
						update_product_details_if_needed ($new_id);
					}
				}
			} elseif ($title == 'Product not found' || $title == 'Products') {
				$db->query ("REPLACE INTO products (product_id, name, site) VALUES (:product_id, NULL, :site)", array (':product_id' => $product_id, ':site' => 'Logos'));
				//return download_product_details_from_vyrso ($product_id);
			} elseif ($title == 'Product no longer sold') {
				if (strpos($html, '<p>We do not sell that product individually. However, it is available in any of the collections below.</p>')) {
					$product_name = ucwords(str_replace('-', ' ', substr($http_response_header[3], strrpos($http_response_header[3], '/')+1)));
					//Add parents
					$products = extract_text ($html, '<li id="also-available-in">', array('<li id="featured-products">', '<li id="staff-picks">'));
					$products = explode ('clearfix">', $products);
					unset($products[0]);
					if ($products) {
						$db->query ("REPLACE INTO products (product_id, name, site) VALUES (:product_id, :name, :site)", array (':product_id' => $product_id, ':name' => $product_name, ':site' => 'Logos'));
						foreach ($products as $product) {
							$parent_id = extract_text ($product, '<a href="/product/', '/');
							$db->query ("REPLACE INTO products_relationships (parent_id, child_id) VALUES (:parent_id, :child_id)", array (':parent_id' => $parent_id, ':child_id' => $product_id));
						}
					} else {
						throw new Exception ('Unable to parse "We do not sell that product individually." page ');
					}
				} elseif ($product_name = extract_text ($html, "<p>That is not a product we currently sell.</p>\r\n\t<p>", "</p>")) {
					if ($product_name = extract_text ($product_name, "\">", "</a>") && strlen($product_name) <= 255) {
						$db->query ("REPLACE INTO products (product_id, name, site) VALUES (:product_id, :name, :site)", array (':product_id' => $product_id, ':name' => $product_name, ':site' => 'Logos'));
					} else {
						$db->query ("REPLACE INTO products (product_id, site) VALUES (:product_id, :site)", array (':product_id' => $product_id, ':site' => 'Logos'));
					}
				} else {
					throw new Exception ("Couldn't recognize Logos 'Product not Found' page for product {$product_id}");
				}
			} else {
				if (strpos($html, '<div class="overview">') !== FALSE) {
					$description = extract_text ($html, '<div class="overview">', '</div>');
					$description = extract_text ($description, '<p>', '</p>');
				} elseif (strpos($html, '<div id="product-description">') !== FALSE) {
					$description = extract_text ($html, '<div id="product-description">', '</div>');
					$description = extract_text ($description, '<p>', '</p>');
				} else {
					$description = '-1';
				}
                $html_no_tabs = str_replace(array("\t","\r","\n"), '', $html);
                $price = null;
				if (strpos($html, '<div class="retail-price">')) { // On Sale
					$price = extract_text ($html, 'Reg.: <del><span class="money">$', '</span>');
				} elseif (strpos ($html_no_tabs, "<div class=\"price-and-discount-sticker\"><div class=\"price\"><span class=\"money\">\$")) {
					$price = extract_text ($html_no_tabs, "<div class=\"price-and-discount-sticker\"><div class=\"price\"><span class=\"money\">\$", '</span>');
				}
				$price = (real)str_replace (',','',$price);
				$db->query ("REPLACE INTO products (product_id, name, description, price, site) VALUES (:product_id, :name, :description, :price, :site)", array (':product_id' => $product_id, ':name' => $title, ':description' => $description, ':price' => $price, ':site' => 'Logos'));
				if (strpos($html, '<h2>More details about this resource</h2>') || strpos($html, '<h2>More details about these resources</h2>')) {
					$resources = extract_text ($html, '<ul class="collection-resource-links">', '</ul>');
					$resources = explode ('<a href="/resources/', $resources);
					unset ($resources[0]);
					$resource_ids = array();
					foreach ($resources as $resource) {
                        $resource = substr($resource, 0, strpos($resource, '/'));
                        $pos = strpos ($resource, '_');
                        if ($pos !== false) {
                            $resource = substr_replace ($resource, ':', $pos, strlen('_'));
                        }
						$resource_ids[] = $resource;
					}
					if ($resource_ids) {
						$only_included_in = (bool)strpos($html, '<h2>More details about these resources</h2>');
                        $missing_resource_ids = array();
                        foreach ($resource_ids as $resource_id) {
                            $already_exists = $db->get_var('SELECT resource_id FROM resources WHERE resource_id=:resource_id', array (':resource_id' => $resource_id));
                            if (!$already_exists) {
                                $missing_resource_ids[] = urldecode($resource_id);
                            }
                        }
						foreach ($resource_ids as $resource_id) {
							$db->query ("REPLACE INTO resources_products (resource_id, product_id, included_in) VALUES (:resource_id, :product_id, :included_in)", array (':resource_id' => $resource_id, ':product_id' => $product_id, ':included_in' => (int)$only_included_in));
						}
                        if ($missing_resource_ids) {
                            update_metadata($missing_resource_ids);
                        }
					}
				}
		        $product = new product ($product_id);
	            if ($product->get_site() == 'Logos' && $product->get_name() != NULL) {
				    if (DEBUG && is_cli()) {
						echo " and parents...";
				    }
	                download_product_parents ($product_id);
	            }
			}
		}
		if (DEBUG && is_cli()) {
			echo " (".number_format(microtime(true)-$timer,2)."s)\r\n";
		}
	}

	function download_product_details_from_vyrso ($product_id) {
		return;
		global $db;
		$url = "https://vyrso.com/product/";
        $download = download_url ($url.$product_id, true);
        $html = html_entity_decode($download['body'], ENT_QUOTES, 'UTF-8');
        $http_response_header = $download['header'];
		if ($html) {
			$title = trim(extract_text ($html, '<title>', " - Vyrso\r\n\t</title>"));
			$locations = get_http_locations($http_response_header);
			if (($c = count($locations)) > 1) {
				if ($locations[$c-1]['id'] == FALSE) {
					$db->query ("REPLACE INTO products (product_id, name, site) VALUES (:product_id, :name, :site)", array (':product_id' => $product_id, ':name' => $locations[0]['name'], ':site' => 'Vyrso'));
				} else {
					$db->query ("REPLACE INTO products (product_id, name, new_id, site) VALUES (:product_id, :name, :new_id, :site)", array (':product_id' => $product_id, ':name' => $locations[0]['name'], ':new_id' => $locations[$c-1]['id'], ':site' => 'Vyrso'));
				}
				return true;
			} elseif ($title == 'Product not found' || $title == 'Products' || $http_response_header[0] == 'HTTP/1.1 500 Internal Server Error') {
				$db->query ("REPLACE INTO products (product_id) VALUES (:product_id)", array (':product_id' => $product_id));
				return false;
			} elseif ($title == 'Product not available on this site') {
				$product_name = extract_text ($html, "<h1>We’re sorry!</h1>\r\n<p>", ' is not available on this site.</p>');
				$db->query ("REPLACE INTO products (product_id, name) VALUES (:product_id, :name)", array (':product_id' => $product_id, ':name' => $product_name));
				return false;
			} elseif ($title == 'Product no longer sold') {
				if (strpos($html, '<p>We do not sell that product individually. However, it is available in any of the collections below.</p>')) {
					$product_name = ucwords(str_replace('-', ' ', substr($http_response_header[3], strrpos($http_response_header[3], '/')+1)));
					$db->query ("REPLACE INTO products (product_id, name) VALUES (:product_id, :name)", array (':product_id' => $product_id, ':name' => $product_name));
				} elseif ($product_name = extract_text ($html, "<p>Try searching for ", ", or check out these related products:")) {
					if ($product_name = extract_text ($product_name, "'>", "</a>")) {
						$db->query ("REPLACE INTO products (product_id, name, site) VALUES (:product_id, :name, :site)", array (':product_id' => $product_id, ':name' => $product_name, ':site' => 'Vyrso'));
					} else {
						$db->query ("REPLACE INTO products (product_id) VALUES (:product_id)", array (':product_id' => $product_id));
					}
				} else {
					throw new Exception ("Couldn't recognize Vyrso 'Product no longer sold' page for product {$product_id}");
				}
			} else {
				if (strpos($html, '<div class="long-description">') !== FALSE) {
					$description = extract_text ($html, '<div class="long-description">', '</div>');
					$description = extract_text ($description, '<p>', '</p>');
				} elseif (strpos($html, '<div id="product-description">') !== FALSE) {
					$description = extract_text ($html, '<div id="product-description">', '</div>');
					$description = extract_text ($description, '<p>', '</p>');
				} else {
					$description = '-1';
				}
				if (strpos($html, '<span class="retail-price">')) {
					$price = extract_text ($html, '<span class="retail-price"><span class="money">$', '</span></span>');
				} elseif (strpos ($html, '<span class="current-price sale-price ">')) {
					$price = extract_text ($html, '<span class="current-price sale-price "><span class="money">$', '</span></span>');
				}
				if (isset($price) && $price) {
					$price = str_replace (',','',$price);
					$db->query ("REPLACE INTO products (product_id, name, price, site) VALUES (:product_id, :name, :price, :site)", array (':product_id' => $product_id, ':name' => $title, ':price' => $price, ':site' => 'Vyrso'));
				} else {
					throw new Exception ("Couldn't recognize Vyrso product page for product {$product_id}");
				}
			}
		}
	}

	function download_product_parents ($product_id) {
		global $db;
		$html = download_url ("https://www.logos.com/products/{$product_id}/RecommendedProducts?recommendationSkuId={$product_id}", false);
		if (strpos ($html, '<li id="also-available-in">') !== FALSE) {
			$also_in = extract_text ($html, '<h3>This title is included as a part of the following collections.', '<h3>');
			$also_in = explode ('clearfix">', $also_in);
			unset ($also_in[0]);
			foreach ($also_in as $a) {
				$parent_id = extract_text ($a, '<a href="/product/', '/');
				$db->query ('INSERT INTO products_relationships (parent_id, child_id) VALUES (:parent_id, :child_id)', array (':parent_id' => $parent_id, ':child_id' => $product_id));
			}
		} else {
			// No parent products
			$db->query ('INSERT INTO products_relationships (parent_id, child_id) VALUES (-1, :child_id)', array (':child_id' => $product_id));
		}
	}

	function get_between_safe ($text, $start_string, $end_string, $check_for = '', $return_bookends = true) {
		$check_for = rtrim($check_for, '>');
		$start_pos = strpos ($text, $start_string);
		$end_pos = strpos($text, $end_string, $start_pos+strlen($start_string));
		$mid = substr ($text, $start_pos, $end_pos-$start_pos+strlen($end_string));
		if ($check_for) {
			while (substr_count ($mid, $check_for) > substr_count ($mid, $end_string)) {
				$end_pos = strpos ($text, $end_string, $end_pos+1);
				$mid = substr ($text, $start_pos, $end_pos-$start_pos+strlen($end_string));
			}
		}
		if (!$return_bookends) {
			$mid = substr ($mid, strlen($start_string), strlen($mid)-strlen($start_string)-strlen($end_string));
		}
		return $mid;
	}

	function return_logos_version ($version_id, $platform = '') {
		if ($platform === NULL) {
			return 'Libronix';
		} elseif ($version_id === NULL) {
			return 'Logos 4';
		} else {
			$version_id = explode ('.', $version_id);
			$version_id[1] = str_pad ($version_id[1], 2, 0, STR_PAD_LEFT);
			$version = "Logos {$version_id[0]}.".substr($version_id[1], 0, 1);
			$version .= (substr($version_id[1], 1, 1) == 0) ? '' : chr(substr($version_id[1], 1, 1)+96);
			return $version;
		}
	}

	function flush_cache() {
		$dh = opendir ('cache');
		while ($file = readdir ($dh)) {
			if (!is_dir($file)) {
				unlink ("cache/{$file}");
			}
		}
		closedir ($dh);
	}

	function request_url_but_dont_wait ($url) {
		$options = array (CURLOPT_URL => $url,
						  CURLOPT_RETURNTRANSFER => true,
						  CURLOPT_NOSIGNAL => 1,
						  CURLOPT_TIMEOUT => 1,
						 );
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		return curl_exec($ch);
	}

    function download_url ($url, $with_headers = false) {
        $options = array (CURLOPT_URL => $url,
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_VERBOSE => false,
                          CURLOPT_HEADER => $with_headers,
                          CURLOPT_SSL_VERIFYHOST => 0,
                          CURLOPT_SSL_VERIFYPEER => false,
                          CURLOPT_FOLLOWLOCATION => true,
                          CURLOPT_AUTOREFERER => true,
                          CURLOPT_CONNECTTIMEOUT => 10,
                          CURLOPT_TIMEOUT => 30,
                         );
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        if ($with_headers) {
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$headers = substr($response, 0, $header_size);
			$headers = explode("\r\n", $headers);
            return array('header' => $headers, 'body' => substr($response, $header_size));
        } else {
            return $response;
        }
    }

	function update_product_details_if_needed ($product_id, $async=true) {
		global $db;
		$product_check = $db->get_var ('SELECT product_id FROM products WHERE products.product_id=:product_id AND last_checked > DATE_SUB(NOW(), INTERVAL 7 DAY)', array(':product_id' => $product_id));
		if (!$product_check) {
			if ($async) {
				request_update_for_product_details ($product_id);
			} else {
				download_product_details ($product_id);
			}
		}
	}

	function request_update_for_product_details ($product_id) {
		$GLOBALS['old_cache']['product'][] = $product_id;
		request_url_but_dont_wait (BASE_URL.'/build_products.php?product_id='.$product_id);
	}

	function get_dataset_resource_ids_as_array() {
		return array ('GreekLemmasErasmian', 'GreekLemmasModern', '1.0.338', 'NRSVNTRI', '{BDD1F99E-9360-452E-822E-8CAFB760E7A0}', '{8062ECD0-5B4C-484D-86BF-E460B1EEA999}', 'LLS:ESVOTREVINT', 'LLS:1.0.338');
	}

    function update_old_metadata() {
        global $db;
        while ($resource_ids = $db->get_col ('SELECT resource_id FROM  resources WHERE TIMESTAMPDIFF(DAY , last_updated, NOW()) > 6 ORDER BY  last_updated ASC LIMIT 0, 100')) {
            update_metadata($resource_ids);
        }
    }

    function update_metadata ($resource_ids) {
        global $db;
        require_once ('includes/build_functions.php');
        $wsdl = "https://services.logos.com/resource/v3/MetadataService.svc?wsdl";
        $metadata_service = new LogosCookielessSoapClient($wsdl);
        $resource_id_chunks = array_chunk((array)$resource_ids, 100);
        foreach ($resource_id_chunks as $resource_ids) {
            $metadata = $metadata_service->GetMetadata(array ('resourceIds' => $resource_ids));
            $columns = array ('resource_id', 'title', 'title_sort', 'abbreviated_title', 'description', 'isbn', 'isbn_type', 'publication_date', 'epub_date', 'internal_name', 'copyright', 'cover_image', 'status', 'type', 'series', 'version', 'milestone_indexes');
            $sql_string1 = $sql_string2 = array();
            foreach ($columns as $column) {
                $sql_string1[] = "{$column}=:{$column}";
                $sql_string2[] = "{$column}=:{$column}2";
            }
            $sql_string2[] = 'last_updated=NOW()';
            $sql_string = 'INSERT INTO resources SET '.implode (', ', $sql_string1).' ON DUPLICATE KEY UPDATE '.implode (', ', $sql_string2);
            if (isset($metadata->GetMetadataResult->ResourceMetadata)) {
                if (is_object($metadata->GetMetadataResult->ResourceMetadata)) {
                    $temp = $metadata->GetMetadataResult->ResourceMetadata;
                    unset ($metadata->GetMetadataResult->ResourceMetadata);
                    $metadata->GetMetadataResult->ResourceMetadata[0] = $temp;
                }
                if (is_array($metadata->GetMetadataResult->ResourceMetadata)) {
                    foreach ($metadata->GetMetadataResult->ResourceMetadata as $resource) {
                        $vars = array();
                        $vars['resource_id'] = $resource->ResourceId;
                        if (is_array ($resource->Titles->Title)) {
                            $vars['title'] = $resource->Titles->Title[0]->Value;
                            $vars['title_sort'] = $resource->Titles->Title[1]->Value;
                        } else {
                            $vars['title'] = $vars['title_sort'] = $resource->Titles->Title->Value;
                        }
                        if (isset ($resource->AbbreviatedTitles->Title->Value)) {
                            $vars['abbreviated_title'] = $resource->AbbreviatedTitles->Title->Value ;
                        } elseif (isset($resource->AbbreviatedTitles->Title) && is_array($resource->AbbreviatedTitles->Title)) {
                            $vars['abbreviated_title'] = $resource->AbbreviatedTitles->Title[0]->Value;
                        } else {
                            $vars['abbreviated_title'] = json_encode($resource->AbbreviatedTitles);
                        }
                        $vars['description'] = isset($resource->Descriptions->Description->Content->any) ? $resource->Descriptions->Description->Content->any : json_encode($resource->Descriptions);
                        $vars['isbn'] = $resource->PrimaryIsbn->Isbn;
                        $vars['isbn_type'] = $resource->PrimaryIsbn->IsbnType;
                        $vars['publication_date'] = (int)$resource->PublicationDate;
                        $vars['epub_date'] = (int)$resource->ElectronicPublicationDate;
                        $vars['internal_name'] = $resource->InternalName;
                        $vars['copyright'] = $resource->Copyright;
                        $vars['cover_image'] = $resource->CoverImages->ImageLink->Url;
                        $vars['status'] = $resource->PublicationStatus;
                        $vars['type'] = $resource->ResourceType;
                        $vars['series'] = $resource->Series;
                        $vars['version'] = date('Y-m-d H:i:s', strtotime($resource->Version));
                        $vars['milestone_indexes'] = serialize ($resource->MilestoneIndexes);
                        foreach ($vars as $k => $v) {
                            $vars["{$k}2"] = $v;
                        }
                        $db->query($sql_string, $vars);
                        //Authors
                        $db->query ('DELETE FROM resources_authors WHERE resource_id=:resource_id', array (':resource_id' => $resource->ResourceId));
                        if (is_object ($resource->Authors) && get_object_vars($resource->Authors) && $resource->Authors->Name) {
                            foreach ((array)$resource->Authors->Name as $author) {
                                add_author_to_resource ($resource->ResourceId, $author);
                            }
                        }
                        //Publishers
                        $db->query ('DELETE FROM resources_publishers WHERE resource_id=:resource_id', array (':resource_id' => $resource->ResourceId));
                        if (is_object ($resource->Publishers) && get_object_vars($resource->Publishers) && $resource->Publishers->Name) {
                            foreach ((array)$resource->Publishers->Name as $publisher) {
                                add_publisher_to_resource ($resource->ResourceId, $publisher);
                            }
                        }
                        //Subjects
                        $db->query ('DELETE FROM resources_subjects WHERE resource_id=:resource_id', array (':resource_id' => $resource->ResourceId));
                        if (is_object ($resource->Subjects) && get_object_vars($resource->Subjects)) {
                            if (is_array ($resource->Subjects->Subject)) {
                                foreach ($resource->Subjects->Subject as $subject) {
                                    add_subject_to_resource ($resource->ResourceId, $subject->Value);
                                }
                            } else {
                                foreach ($resource->Subjects as $subject) {
                                    add_subject_to_resource ($resource->ResourceId, $subject->Value);
                                }
                            }
                        }
                        //Languages
                        $db->query ('DELETE FROM resources_languages WHERE resource_id=:resource_id', array (':resource_id' => $resource->ResourceId));
                        if (is_object ($resource->Languages) && get_object_vars($resource->Languages)) {
                            foreach ((array)$resource->Languages->Language as $language) {
                                add_language_to_resource ($resource->ResourceId, $language);
                            }
                        }
                        //Platforms
                        $db->query ('DELETE FROM resources_platforms WHERE resource_id=:resource_id', array (':resource_id' => $resource->ResourceId));
                        if (isset ($resource->SupportedPlatforms->Name)) {
                            foreach ((array)$resource->SupportedPlatforms->Name as $platform) {
                                add_platform_to_resource ($resource->ResourceId, $platform);
                            }
                        }
                        //Owners
                        $db->query ('DELETE FROM resources_owners WHERE resource_id=:resource_id', array (':resource_id' => $resource->ResourceId));
                        if (isset ($resource->Owners->Name)) {
                            foreach ((array)$resource->Owners->Name as $owner) {
                                add_owner_to_resource ($resource->ResourceId, $owner);
                            }
                        }
                        //Traits
                        $db->query ('DELETE FROM resources_traits WHERE resource_id=:resource_id', array (':resource_id' => $resource->ResourceId));
                        if (isset ($resource->Traits->Trait)) {
                            foreach ((array)$resource->Traits->Trait as $trait) {
                                add_trait_to_resource ($resource->ResourceId, $trait);
                            }
                        }
                        //File history
                        $db->query ('DELETE FROM file_history WHERE resource_id=:resource_id', array (':resource_id' => $resource->ResourceId));
                        if (isset ($resource->ResourceFiles->ResourceFileMetadata)) {
                            if (!is_array($resource->ResourceFiles->ResourceFileMetadata)) {
                                $t = $resource->ResourceFiles->ResourceFileMetadata;
                                unset($resource->ResourceFiles->ResourceFileMetadata);
                                $resource->ResourceFiles->ResourceFileMetadata[0] = $t;
                            }
                            foreach ($resource->ResourceFiles->ResourceFileMetadata as $m) {
                                $m->DatePublished = date( 'Y-m-d H:i:s', strtotime($m->DatePublished));
                                $m->Version = date( 'Y-m-d H:i:s', strtotime($m->Version));
                                $db->query ('INSERT INTO file_history (date_published, filename, filesize, minimum_version, platform, resource_id, version) VALUES (:date_published, :filename, :filesize, :minimum_version, :platform, :resource_id, :version)', array ('date_published' => $m->DatePublished, 'filename' => $m->FileName, 'filesize' => $m->FileSize, 'minimum_version' => $m->MinimumSoftwareVersion, 'platform' => $m->Platform, 'resource_id' => $m->ResourceId, 'version' => $m->Version));
                            }
                        }
                        //Types
                        if ($resource->ResourceType != '') {
                            $db->query ('INSERT IGNORE INTO types (type_id, type) VALUES (:type_id, :type_id)', array (':type_id' => $resource->ResourceType));
                        }
                    }
                }
            }
        }
    }

    function sort_products ($a, $b) {
    	if ($a->get_price() == $b->get_price()) {
			return strcasecmp ($a->get_name(), $b->get_name());
    	} elseif ($a->get_price() == 0) {
			return 1;
    	} elseif ($b->get_price() == 0) {
			return -1;
		} elseif ($a->get_price() < $b->get_price()) {
			return -1;
		} elseif ($a->get_price() > $b->get_price()) {
			return 1;
		}
    }

    function is_cli() {
    	return (php_sapi_name() === 'cli');
	}
?>