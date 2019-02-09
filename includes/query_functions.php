<?php
    function delete_all_resources_for_user ($user_id) {
        global $db;
        $db->query ("DELETE FROM resources_users WHERE user_id = :user_id", array (':user_id' => $user_id));
    }

    function delete_all_tags_for_user ($user_id) {
        global $db;
        $db->query ("DELETE FROM resources_users_tags WHERE user_id = :user_id", array (':user_id' => $user_id));
    }

    function insert_resource ($resource_id, $type, $title, $title_sort, $publication_date, $series, $languages, $publishers, $description, $authors, $subjects, $traits) {
        global $db;
        if (substr($resource_id, 0, 4) == 'PBB:') {
            $id = $title.(is_array($authors) && array_key_exists('Name', $authors[0]) ? " {$title}" : '');
            $resource_id = 'PBB:'.make_id($id);
        }
        $sql = "INSERT IGNORE INTO resources (resource_id, type, title, title_sort, publication_date, language, description) VALUES (:ResourceId, :Type, :Title, :TitleSortKey, :PublicationDate, :Languages, :Description)";
        $params = array (':ResourceId' => $resource_id, ':Type' => $type, ':Title' => $title, ':TitleSortKey' => $title_sort, ':PublicationDate' => $publication_date, ':Languages' => $languages, ':Description' => $description);
        $db->query ($sql, $params, array (':TitleSortKey' => PDO::PARAM_LOB));
        $params = array (':resource_id' => $resource_id);
        if ($authors) {
            foreach ($authors as $author) {
                $author_id = get_author_id ($author['Name'], $author['NameSortKey']);
                $params [':author_id'] = $author_id;
                $sql = "INSERT IGNORE INTO resources_authors (resource_id, author_id) VALUES (:resource_id, :author_id)";
                $db->query ($sql, $params);
            }
            unset ($params [':author_id']);
        }
        if ($subjects) {
            foreach ($subjects as $subject) {
                $subject_id = get_subject_id ($subject['Name'], $subject['NameSortKey']);
                $params [':subject_id'] = $subject_id;
                $sql = "INSERT IGNORE INTO resources_subjects (resource_id, subject_id) VALUES (:resource_id, :subject_id)";
                $db->query ($sql, $params);
            }
            unset ($params [':subject_id']);
        }
        if ($traits) {
            foreach ($traits as $trait) {
                $trait_id = get_trait_id ($trait);
                $params [':trait_id'] = $trait_id;
                $sql = "INSERT IGNORE INTO resources_traits (resource_id, trait_id) VALUES (:resource_id, :trait_id)";
                $db->query ($sql, $params);
            }
            unset ($params [':trait_id']);
        }
        if ($publishers) {
            foreach ($publishers as $publisher) {
                $publisher_id = get_publisher_id ($publisher);
                $params [':publisher_id'] = $publisher_id;
                $sql = "INSERT IGNORE INTO resources_publishers (resource_id, publisher_id) VALUES (:resource_id, :publisher_id)";
                $db->query ($sql, $params);
            }
            unset ($params [':publisher_id']);
        }
        if ($series) {
            foreach ($series as $s) {
                $series_id = get_series_id ($s);
                $params [':series_id'] = $series_id;
                $sql = "INSERT IGNORE INTO resources_series (resource_id, series_id) VALUES (:resource_id, :series_id)";
                $db->query ($sql, $params);
            }
            unset ($params [':series_id']);
        }
    }

    /**
    * Returns the author_id given the author's name
    *
    * Will create authors if they don't already exist
    *
    * @param string $author
    * @return int
    */
    function get_author_id ($author) {
        global $db;
        $params = array (':author' => $author);
        $author_id = $db->get_var ("SELECT author_id FROM authors WHERE author=:author", $params);
        if ($author_id) {
            return $author_id;
		} else {
            $db->query ("INSERT INTO authors (author) VALUES (:author)", $params);
            return $db->lastInsertId();
        }
    }

    /**
    * Returns the subject_id given the subject's name
    *
    * Will create subjects if they don't already exist
    *
    * @param string $subject
    * @return int
    */
    function get_subject_id ($subject) {
        global $db;
        $params = array (':subject' => $subject);
        $subject_id = $db->get_var ("SELECT subject_id FROM subjects WHERE subject=:subject", $params);
        if ($subject_id) {
            return $subject_id;
		} else {
            $db->query ("INSERT IGNORE INTO subjects (subject) VALUES (:subject)", $params);
            $insert_id = $db->lastInsertId();
            if ($insert_id) {
				return $insert_id;
            } else {
				return $db->get_var ("SELECT subject_id FROM subjects WHERE subject=:subject", $params);
            }
        }
    }

    /**
    * Returns the trait_id given the trait's name
    *
    * Will create traits if they don't already exist
    *
    * @param string $trait
    * @return int
    */
    function get_trait_id ($trait) {
        global $db;
        $params = array (':trait' => $trait);
        $trait_id = $db->get_var ("SELECT trait_id FROM traits WHERE trait=:trait", $params);
        if ($trait_id)
            return $trait_id;
        else {
            $db->query ("INSERT INTO traits (trait) VALUES (:trait)", $params);
            return $db->lastInsertId();
        }
    }

    /**
    * Returns the publisher_id given the publisher's name
    *
    * Will create publishers if they don't already exist
    *
    * @param string $publisher
    * @return int
    */
    function get_publisher_id ($publisher) {
        global $db;
        $params = array (':publisher' => $publisher);
        $publisher_id = $db->get_var ("SELECT publisher_id FROM publishers WHERE publisher=:publisher", $params);
        if ($publisher_id)
            return $publisher_id;
        else {
            $db->query ("INSERT INTO publishers (publisher) VALUES (:publisher)", $params);
            return $db->lastInsertId();
        }
    }

    /**
    * Returns the tag_id given the tag's name
    *
    * Will create tags if they don't already exist
    *
    * @param string $tag
    * @return int
    */
    function get_tag_id ($tag) {
        global $db;
        $params = array (':tag' => $tag);
        $tag_id = $db->get_var ("SELECT tag_id FROM user_tags WHERE tag=:tag", $params);
        if ($tag_id)
            return $tag_id;
        else {
            $db->query ("INSERT INTO user_tags (tag) VALUES (:tag)", $params);
            return $db->lastInsertId();
        }
    }

    function add_resource_to_user ($resource, $user_id, $tags) {
        global $db;
        if ($user_id == 1 && $resource['UserRating'] == 2)
            $resource['UserRating'] = null;
        $params = array (':resource_id' => $resource['ResourceId'], ':user_id' => $user_id, ':is_hidden' => $resource['IsHidden'], ':is_available' => $resource['IsAvailable'], ':user_title' => $resource['UserTitle'], ':user_series' => $resource['UserSeries'], ':user_rating' => $resource['UserRating']);
        $db->query ("INSERT INTO resources_users (resource_id, user_id, is_hidden, is_available, user_title, user_series, user_rating) VALUES (:resource_id, :user_id, :is_hidden, :is_available, :user_title, :user_series, :user_rating)", $params);
        if ($tags) {
            $params = array (':resource_id' => $resource['ResourceId'], ':user_id' => $user_id);
            foreach ($tags as $tag) {
                $params['tag_id'] = get_tag_id ($tag);
                $db->query ("INSERT INTO resources_users_tags (resource_id, user_id, tag_id) VALUES (:resource_id, :user_id, :tag_id)", $params);
            }
        }
    }

    function get_num_resources ($user_id='') {
        global $db;
        if ($user_id == '') {
            return $db->get_var ("SELECT COUNT(resource_id) AS num_resources FROM resources_users");
        } else {
            return $db->get_var ("SELECT COUNT(resource_id) AS num_resources FROM resources_users WHERE user_id=:user_id", array (':user_id' => $user_id));
        }
    }

    function get_num_distinct_resources ($user_id='') {
        global $db;
        if ($user_id == '') {
            return $db->get_var ("SELECT COUNT(DISTINCT resource_id) AS num_resources FROM resources");
        } else {
            return $db->get_var ("SELECT COUNT(DISTINCT resource_id) AS num_resources FROM resources_users WHERE user_id=:user_id", array (':user_id' => $user_id));
        }
    }

    function get_num_distinct_owned_resources () {
        global $db;
        return $db->get_var ("SELECT COUNT(DISTINCT resource_id) FROM resources_users");
    }

    function get_all_tags ($limit=50) {
        global $db;
        $limit = (int)$limit;
        return $db->get_results ("SELECT tag, SUM(count) AS num_tags FROM community_tags GROUP BY tag ORDER BY num_tags DESC LIMIT {$limit}");
    }

    function get_all_series ($user_id = '', $sort_by_count = false, $limit=999999) {
        global $db;
        $limit = (int)$limit;
        if ($sort_by_count) {
            $sort = 'num_resources DESC, series ASC';
        } else {
            $sort = 'series ASC';
        }
        if ($user_id == '') {
            return $db->get_results ("SELECT series, COUNT(resources_users.id) AS num_resources, COUNT(DISTINCT user_id) AS num_users FROM resources, resources_users WHERE series IS NOT NULL AND resources_users.resource_id = resources.resource_id GROUP BY series ORDER BY {$sort} LIMIT {$limit}");
        } else {
            return $db->get_results ("SELECT series, COUNT(resources_users.id) AS num_resources, COUNT(DISTINCT user_id) AS num_users FROM resources, resources_users WHERE series IS NOT NULL AND resources_users.resource_id = resources.resource_id AND user_id=:user_id GROUP BY series ORDER BY {$sort} LIMIT {$limit}", array (':user_id' => $user_id));
        }
    }

    function get_all_types ($user_id = '', $sort_by_count = false, $limit=999999) {
        global $db;
        $limit = (int)$limit;
        if ($sort_by_count) {
            $sort = 'num_resources DESC, type ASC';
        } else {
            $sort = 'resource_type ASC';
        }
        if ($user_id == '') {
            return $db->get_results ("SELECT type_id, types.type, COUNT(resources_users.id) AS num_resources, COUNT(DISTINCT user_id) AS num_users FROM resources, resources_users, types WHERE resources_users.resource_id = resources.resource_id AND types.type_id=resources.type  GROUP BY type ORDER BY {$sort} LIMIT {$limit}");
        } else {
            return $db->get_results ("SELECT types.name, resources.type, COUNT(resources_users.id) AS num_resources, COUNT(DISTINCT user_id) AS num_users FROM resources, resources_users, types WHERE resources_users.resource_id = resources.resource_id AND resources.type=types.type AND resources.type <> ''  AND user_id=:user_id GROUP BY resources.type ORDER BY {$sort} LIMIT {$limit}", array (':user_id' => $user_id));
        }
    }

    function get_all_traits ($user_id = '', $sort_by_count = false, $limit=999999) {
        global $db;
        $limit = (int)$limit;
        if ($sort_by_count) {
            $sort = 'num_resources DESC, traits.trait ASC';
        } else {
            $sort = 'traits.trait ASC';
        }
        if ($user_id == '') {
            return $db->get_results ("SELECT trait, traits.trait_id, COUNT(resources_users.id) AS num_resources, COUNT(DISTINCT user_id) AS num_users FROM resources_users, resources_traits, traits WHERE resources_users.resource_id = resources_traits.resource_id AND resources_traits.trait_id = traits.trait_id GROUP BY traits.trait_id ORDER BY {$sort} LIMIT {$limit}");
        } else {
            return $db->get_results ("SELECT trait, traits.trait_id, COUNT(resources_users.id) AS num_resources FROM resources_users, resources_traits, traits WHERE resources_users.resource_id = resources_traits.resource_id AND resources_traits.trait_id = traits.trait_id AND user_id=:user_id GROUP BY traits.trait_id ORDER BY {$sort} LIMIT {$limit}", array (':user_id' => $user_id));
        }
    }

    function get_all_languages ($user_id = '', $sort_by_count = false, $limit=999999) {
        global $db;
        $limit = (int)$limit;
        if ($sort_by_count) {
            $sort = 'num_resources DESC, languages.language ASC';
        } else {
            $sort = 'languages.name ASC';
        }
        if ($user_id == '') {
            return $db->get_results ("SELECT languages.language, languages.language_id, COUNT(resources_users.id) AS num_resources, COUNT(DISTINCT user_id) AS num_users FROM resources_users, resources_languages, languages WHERE resources_users.resource_id = resources_languages.resource_id AND languages.language_id = resources_languages.language_id GROUP BY resources_languages.language_id ORDER BY {$sort} LIMIT {$limit}");
        } else {
            return $db->get_results ("SELECT languages.name, languages.language, COUNT(resources_users.id) AS num_resources FROM resources_users, resources, languages WHERE resources_users.resource_id = resources.resource_id AND resources.language = languages.language AND user_id=:user_id GROUP BY resources.language ORDER BY {$sort} LIMIT {$limit}", array (':user_id' => $user_id));
        }
    }


    function get_num_starred ($rating, $include_halves = false) {
        global $db;
        if ($rating == 0) {
			return $db->get_var ("SELECT COUNT(*) AS num_starred FROM resources WHERE rating_value IS NULL");
        } else {
	        if ($include_halves) {
				$rating = round($rating*2,0);
				return $db->get_var ("SELECT SUM(rating_count) AS num_starred FROM resources WHERE round(rating_value*2)=:rating", array('rating' => $rating));
	        } else {
				return $db->get_var ("SELECT SUM(rating_count) AS num_starred FROM resources WHERE round(rating_value)=:rating", array('rating' => $rating));
	        }
		}
    }

    function get_favourite_resources ($user_id = '', $limit = 50) {
        global $db;
        $limit = (int)$limit;
        if ($user_id == '') {
            return $db->get_results ("SELECT resource_id, title, cover_image, rating_count, rating_value FROM resources ORDER BY POW(rating_value, 5)*rating_count DESC LIMIT {$limit}");
        } else {
            die('Can\'t do favorite resources');;
        }
    }

    function get_favourite_publishers ($user_id = '', $limit=10) {
        global $db;
        $limit = (int)$limit;
        if ($user_id == '') {
            return $db->get_results ("SELECT publishers.publisher, publishers.publisher_id, AVG(rating_value) AS user_rating, COUNT(resources.resource_id) AS num_resources FROM resources, publishers, resources_publishers WHERE resources.resource_id = resources_publishers.resource_id AND resources_publishers.publisher_id=publishers.publisher_id GROUP BY publisher HAVING num_resources > 2 AND user_rating > 0 ORDER BY user_rating DESC, num_resources DESC LIMIT {$limit}");
        } else {
            return $db->get_results ("SELECT publishers.publisher, publishers.publisher_id, AVG(rating_value) AS user_rating, COUNT(resources.resource_id) AS num_resources FROM resources, publishers, resources_publishers WHERE resources.resource_id = resources_publishers.resource_id AND resources_publishers.publisher_id=publishers.publisher_id AND user_id=:user_id GROUP BY publisher HAVING num_resources > 2 AND user_rating > 0 ORDER BY user_rating DESC, num_resources DESC LIMIT {$limit}", array (':user_id' => $user_id));
        }
    }

    function get_favourite_series ($user_id = '', $limit=10) {
        global $db;
        $limit = (int)$limit;
        if ($user_id == '') {
            return $db->get_results ("SELECT series, AVG(rating_value) AS user_rating, COUNT(resources_users.resource_id) AS num_resources FROM resources, resources_users WHERE resources_users.resource_id = resources.resource_id AND series IS NOT NULL GROUP BY series HAVING num_resources > 2 AND user_rating > 0 ORDER BY user_rating DESC, num_resources DESC LIMIT {$limit}");
        } else {
            return $db->get_results ("SELECT series, AVG(rating_value) AS user_rating, COUNT(resources_users.resource_id) AS num_resources FROM resources, resources_users WHERE resources_users.resource_id = resources.resource_id AND user_id=:user_id GROUP BY series HAVING num_resources > 2 AND user_rating > 0 ORDER BY user_rating DESC, num_resources DESC LIMIT {$limit}", array (':user_id' => $user_id));
        }
    }

    function get_num_users() {
        global $db;
        return $db->get_var ("SELECT COUNT(DISTINCT user_id) AS num_users FROM resources_users");
    }

    function is_in_library_catalog ($resource_id, $user_id) {
        global $db;
        return (boolean)$db->get_var ("SELECT COUNT(resource_id) AS in_catalog FROM resources_users WHERE resource_id=:resource_id AND user_id=:user_id", array (':resource_id' => $resource_id, ':user_id' => $user_id));
    }

    function is_hidden ($resource_id, $user_id) {
        global $db;
        return (boolean)$db->get_var ("SELECT COUNT(resource_id) AS in_catalog FROM resources_users WHERE resource_id=:resource_id AND user_id=:user_id AND hidden=1", array (':resource_id' => $resource_id, ':user_id' => $user_id));
    }

    function get_resources_from_page ($current_page) {
        global $db;
        if ($current_page != '0') {
            $resource_data = $db->get_results("SELECT * FROM resources WHERE LEFT(title,1) = :current_page ORDER BY title", array (':current_page' => $current_page));
        } else {
            $resource_data = $db->get_results("SELECT * FROM resources WHERE LEFT(title,1) IN ('0','1','2','3','4','5','6','7','8','9') ORDER BY title");
        }
        if ($resource_data) {
            $resources = array();
            foreach ($resource_data as $data) {
                $resources[] = new resource($data);
            }
            return $resources;
        }
        return false;
    }

    function get_resources_by_something ($type, $id) {
        global $db;
        $types = ($type == 'series') ? $type : "{$type}s";
        $resource_data = $db->get_results("SELECT resources.* FROM resources, resources_{$types} WHERE resources.resource_id=resources_{$types}.resource_id AND {$type}_id=:id ORDER BY title_sort", array (':id' => $id));
        if ($resource_data) {
            $resources = array();
            foreach ($resource_data as $data) {
                $resources[] = new resource($data);
            }
            return $resources;
        }
        return false;
    }

    function get_resources_by_author ($id) {
    	return get_resources_by_something ('author', $id);
    }

    function get_resources_by_publisher ($id) {
    	return get_resources_by_something ('publisher', $id);
    }

    function get_resources_by_subject ($id) {
    	return get_resources_by_something ('subject', $id);
    }

    function get_resources_by_trait ($id) {
    	return get_resources_by_something ('trait', $id);
    }

    function get_resources_by_product ($id) {
    	return get_resources_by_something ('product', $id);
    }

    function get_resources_by_series ($series) {
        global $db;
        $resource_data = $db->get_results("SELECT resources.* FROM resources WHERE series=:series ORDER BY title_sort", array (':series' => $series));
        if ($resource_data) {
            $resources = array();
            foreach ($resource_data as $data) {
                $resources[] = new resource($data);
            }
            return $resources;
        }
        return false;
    }

    function get_something ($types, $first_part, $limit) {
        global $db;
        $limit = (int)$limit;
        if ($types == 'series') {
            if ($first_part !== '0') {
                return $db->get_results ("SELECT resources.series, resources.series, COUNT(resources_users.id) AS num_copies, COUNT(DISTINCT resources.resource_id) AS num_resources, COUNT(DISTINCT user_id) AS num_users FROM resources LEFT JOIN resources_users ON resources_users.resource_id = resources.resource_id WHERE LEFT(series,1)=:first_part GROUP BY series ORDER BY series ASC LIMIT {$limit}", array (':first_part' => $first_part));
            } else {
                return $db->get_results ("SELECT resources.series, resources.series, COUNT(resources_users.id) AS num_copies, COUNT(DISTINCT resources.resource_id) AS num_resources, COUNT(DISTINCT user_id) AS num_users FROM resources LEFT JOIN resources_users ON resources_users.resource_id = resources.resource_id WHERE LEFT(series,1) IN ('0','1','2','3','4','5','6','7','8','9') GROUP BY series ORDER BY series ASC LIMIT {$limit}");
            }
        } else {
            $type = rtrim($types, 's');
            if ($first_part !== '0') {
                ${$type} = "{$first_part}%";
                return $db->get_results ("SELECT {$types}.{$type}_id, {$types}.{$type}, COUNT(resources_users.id) AS num_copies, COUNT(DISTINCT resources_{$types}.resource_id) AS num_resources, COUNT(DISTINCT user_id) AS num_users FROM {$types} LEFT JOIN resources_{$types} ON resources_{$types}.{$type}_id = {$types}.{$type}_id LEFT JOIN resources_users ON resources_users.resource_id = resources_{$types}.resource_id WHERE {$type} LIKE :{$type}2 GROUP BY {$type}_id ORDER BY {$type} ASC LIMIT {$limit}", array (":{$type}2"=>${$type}));
            } else {
                return $db->get_results ("SELECT {$types}.{$type}_id, {$types}.{$type}, COUNT(resources_users.id) AS num_copies, COUNT(DISTINCT resources_{$types}.resource_id) AS num_resources, COUNT(DISTINCT user_id) AS num_users FROM {$types} LEFT JOIN resources_{$types} ON resources_{$types}.{$type}_id = {$types}.{$type}_id LEFT JOIN resources_users ON resources_users.resource_id = resources_{$types}.resource_id WHERE LEFT({$type},1) IN ('0','1','2','3','4','5','6','7','8','9') GROUP BY {$type}_id ORDER BY {$type} ASC LIMIT {$limit}");
            }
        }
    }

    function get_authors ($first_part = '', $limit=999999) {
    	return get_something ('authors', $first_part, $limit);
    }

    function get_publishers ($first_part = '', $limit=999999) {
    	return get_something ('publishers', $first_part, $limit);
    }

    function get_subjects ($first_part = '', $limit=999999) {
    	return get_something ('subjects', $first_part, $limit);
    }

    function get_series ($first_part = '', $limit=999999) {
        return get_something ('series', $first_part, $limit);
    }

    function get_traits ($first_part = '', $limit=999999) {
        return get_something ('traits', $first_part, $limit);
    }

    function get_num_distinct_authors () {
        global $db;
        return $db->get_results ("SELECT COUNT(DISTINCT resources_authors.author_id) FROM resources_users, resources_authors WHERE resources_users.resource_id = resources_authors.resource_id");
    }

    function get_popular_something_as_array ($types, $user_id, $limit) {
        global $db;
        $limit = (int)$limit;
        $type = ($types == 'series') ? $types : rtrim($types, 's');
        if ($types == 'series') {
            $type = $types;
            if ($user_id == '') {
                $results = $db->get_results ("SELECT resources.series, resources.series, COUNT(DISTINCT resources.resource_id) AS num_resources, COUNT(resources_users.id) AS num_copies, COUNT(DISTINCT user_id) AS num_users FROM resources_users, resources WHERE resources_users.resource_id = resources.resource_id AND series IS NOT NULL GROUP BY series ORDER BY num_copies DESC, series ASC LIMIT {$limit}");
            } else {
                $results = $db->get_results ("SELECT resources.series, resources.series, COUNT(DISTINCT resources.resource_id) AS num_resources, COUNT(resources_users.id) AS num_copies FROM resources_users, resources WHERE resources_users.resource_id = resources.resource_id AND user_id=:user_id AND series IS NOT NULL GROUP BY series ORDER BY num_copies DESC, series ASC LIMIT {$limit}", array (':user_id' => $user_id));
            }
        } else {
            if ($user_id == '') {
                $results = $db->get_results ("SELECT {$types}.{$type}_id, {$types}.{$type}, COUNT(DISTINCT resources_{$types}.resource_id) AS num_resources, COUNT(resources_users.id) AS num_copies, COUNT(DISTINCT user_id) AS num_users FROM resources_users, resources_{$types}, {$types} WHERE resources_users.resource_id = resources_{$types}.resource_id AND resources_{$types}.{$type}_id = {$types}.{$type}_id GROUP BY {$type}_id ORDER BY num_copies DESC, {$types}.{$type} ASC LIMIT {$limit}");
            } else {
                $results = $db->get_results ("SELECT {$types}.{$type}_id, {$types}.{$type}, COUNT(DISTINCT resources_{$types}.resource_id) AS num_resources, COUNT(resources_users.id) AS num_copies FROM resources_users, resources_{$types}, {$types} WHERE resources_users.resource_id = resources_{$types}.resource_id AND resources_{$types}.{$type}_id = {$types}.{$type}_id AND user_id=:user_id GROUP BY {$type}_id ORDER BY num_copies DESC, {$types}.{$type} ASC LIMIT {$limit}", array (':user_id' => $user_id));
            }
        }
        if ($results) {
        	if ($type == 'trait') {
				$type = 'traits'; // Due to PHP naming conflict
        	}
            $objects = array();
            foreach ($results as $result) {
                $objects [] = new $type ($result);
            }
            return $objects;
        }
    }

    function get_popular_something_as_text_list ($types, $include_links, $include_counts, $limit, $separator, $user_id) {
        $results = get_popular_something_as_array ($types, $user_id, $limit);
        $output = array();
        foreach ($results as $result) {
            $num_copies = number_format($result->get_num_copies());
            $output [] = "<a href=\"{$result->get_url()}\">{$result->get_name()}</a> ({$num_copies})";
        }
        return implode (', ', $output);
    }

    function get_popular_authors_as_text_list ($include_links = false, $include_counts = true, $limit = 50, $separator = ', ', $user_id = '') {
    	return get_popular_something_as_text_list ('authors', $include_links, $include_counts, $limit, $separator, $user_id);
    }

    function get_popular_publishers_as_text_list ($include_links = false, $include_counts = true, $limit = 50, $separator = ', ', $user_id = '') {
    	return get_popular_something_as_text_list ('publishers', $include_links, $include_counts, $limit, $separator, $user_id);
    }

    function get_popular_subjects_as_text_list ($include_links = false, $include_counts = true, $limit = 50, $separator = ', ', $user_id = '') {
    	return get_popular_something_as_text_list ('subjects', $include_links, $include_counts, $limit, $separator, $user_id);
    }

    function get_popular_series_as_text_list ($include_links = false, $include_counts = true, $limit = 50, $separator = ', ', $user_id = '') {
        return get_popular_something_as_text_list ('series', $include_links, $include_counts, $limit, $separator, $user_id);
    }

    function get_popular_traits_as_text_list ($include_links = false, $include_counts = true, $limit = 50, $separator = ', ', $user_id = '') {
    	return get_popular_something_as_text_list ('traits', $include_links, $include_counts, $limit, $separator, $user_id);
    }
?>