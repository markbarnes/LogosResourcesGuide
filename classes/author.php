<?php
    class author extends template {
        
        protected $valid, $id, $author_id, $author, $num_resources, $num_copies, $num_users, $ratings, $ratings_average, $ratings_count, $ratings_full, $ratings_half;
        
        function __construct ($author) {
            global $db;
            $this->valid = false;
            if (is_array ($author) && isset($author['author_id'])) {
                $this->valid=true;
                foreach ($author as $k => $v) {
                    $this->$k = $v;
                }
                $author = $author['author_id'];
            }
            if (!isset ($this->author)) {
                $details = $db->get_row ('SELECT * FROM authors WHERE author_id=:author_id', array (':author_id' => $author));
                if ($details) {
                    $this->valid = true;
                    foreach ($details as $column => $value) {
                        $this->$column = $value;
                    }
                } else {
                    $this->valid = false;
                }
            }
        }
        
        function get_name() {
            if ($this->valid)
                return format_author($this->author);
        }

        function get_id() {
            if ($this->valid)
                return $this->author_id;
        }
        
        function get_num_resources($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset($this->num_resources)) {
                    $this->num_resources = $db->get_var ('SELECT COUNT(resource_id) AS num FROM resources_authors WHERE author_id=:author_id',array(':author_id'=>$this->author_id));
                }
                if ($singular == '') {
                    return $this->num_resources;
                } elseif ($this->num_resources == 1) {
                    return number_format($this->num_resources)." {$singular}";
                } else {
                    return number_format($this->num_resources)." {$plural}";
                }
            }
        }

        function get_num_copies($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset($this->num_copies)) {
                    $this->num_copies = $db->get_var ('SELECT COUNT(resources_users.id) FROM resources_authors, resources_users WHERE resources_authors.resource_id=resources_users.resource_id AND author_id=:author_id',array(':author_id'=>$this->author_id));
                }
                if ($singular == '') {
                    return $this->num_copies;
                } elseif ($this->num_copies == 1) {
                    return "{$this->num_copies} {$singular}";
                } else {
                    return "{$this->num_copies} {$plural}";
                }
            }
        }

        function get_num_users_resources($user_id) {
            global $db;
            if ($this->valid) {
                return $db->get_var ('SELECT COUNT(resources_users.resource_id) FROM resources_authors, resources_users WHERE resources_authors.resource_id=resources_users.resource_id AND author_id=:author_id AND user_id=:user_id',array(':author_id'=>$this->author_id, ':user_id'=>$user_id));
            }
        }

        function get_num_users($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset ($this->num_users)) {
                    $this->num_users = $db->get_var ('SELECT COUNT(DISTINCT user_id) FROM resources_authors, resources_users WHERE resources_authors.resource_id=resources_users.resource_id AND author_id=:author_id',array(':author_id'=>$this->author_id));
                }
                if ($singular == '') {
                    return $this->num_users;
                } elseif ($this->num_users == 1) {
                    return number_format($this->num_users)." {$singular}";
                } else {
                    return number_format($this->num_users)." {$plural}";
                }
            }
        }
        
        function get_most_popular_work ($prioritise_works_i_own = false) {
            global $db;
            return new resource ($db->get_var ('SELECT resources_authors.resource_id, resources.title FROM resources_authors, resources_users WHERE resources_authors.resource_id = resources_users.resource_id AND author_id =:author_id GROUP BY resources_authors.resource_id ORDER BY COUNT( resources_users.resource_id ) DESC LIMIT 1', array('author_id' => $this->author_id)));
        }
        
        function get_by_label () {
			return 'written by '.$this->get_name();
        }
        
        function get_resources() {
        	if (!isset ($this->all_resources)) {
				$this->all_resources = get_resources_by_author($this->get_id());
        	}
			return $this->all_resources;
        }

    }
?>