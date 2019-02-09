<?php
    class traits extends template {

        protected $valid, $trait_id, $trait, $num_resources, $num_copies, $num_users, $ratings_average, $ratings_count, $ratings_summary;

        function __construct ($trait) {
            global $db;
            $this->valid = false;
            if (is_array ($trait) && isset($trait['trait_id'])) {
                $this->valid=true;
                foreach ($trait as $k => $v) {
                    $this->$k = $v;
                }
                $trait = $trait['trait_id'];
            }
            if (!isset ($this->trait)) {
                $details = $db->get_row ('SELECT * FROM traits WHERE trait_id=:trait_id', array (':trait_id' => $trait));
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
                return $this->trait;
        }

        function get_id() {
            if ($this->valid)
                return $this->trait_id;
        }

        function get_num_resources($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset($this->num_resources)) {
                    $this->num_resources = $db->get_var ('SELECT COUNT(resource_id) AS num FROM resources_traits WHERE trait_id=:trait_id',array(':trait_id'=>$this->trait_id));
                }
                if ($singular == '') {
                    return $this->num_resources;
                } elseif ($this->num_resources == 1) {
                    return "{$this->num_resources} {$singular}";
                } else {
                    return "{$this->num_resources} {$plural}";
                }
            }
        }

        function get_num_copies($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset($this->num_copies)) {
                    $this->num_copies = $db->get_var ('SELECT COUNT(resources_users.id) FROM resources_traits, resources_users WHERE resources_traits.resource_id=resources_users.resource_id AND trait_id=:trait_id',array(':trait_id'=>$this->trait_id));
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
                return $db->get_var ('SELECT COUNT(resources_users.resource_id) FROM resources_traits, resources_users WHERE resources_traits.resource_id=resources_users.resource_id AND trait_id=:trait_id AND user_id=:user_id',array(':trait_id'=>$this->trait_id, ':user_id'=>$user_id));
            }
        }

        function get_num_users($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset ($this->num_users)) {
                    $this->num_users = $db->get_var ('SELECT COUNT(DISTINCT user_id) FROM resources_traits, resources_users WHERE resources_traits.resource_id=resources_users.resource_id AND trait_id=:trait_id',array(':trait_id'=>$this->trait_id));
                }
                if ($singular == '') {
                    return $this->num_users;
                } elseif ($this->num_users == 1) {
                    return "{$this->num_users} {$singular}";
                } else {
                    return "{$this->num_users} {$plural}";
                }
            }
        }

        function get_ratings_summary($user_id = '') {
            global $db;
            if ($this->valid) {
                if (!isset ($this->ratings_summary) && $user_id == '') {
                    $this->ratings_summary = get_ratings_summary($db->get_results ('SELECT user_rating, COUNT(resources_users.id) as ratings_count FROM resources_traits, resources_users WHERE resources_traits.resource_id=resources_users.resource_id AND trait_id=:trait_id GROUP BY user_rating ORDER BY user_rating ASC',array(':trait_id'=>$this->trait_id)));
                    $this->ratings_count = $this->ratings_summary['average']['count'];
                    $this->ratings_average = $this->ratings_summary['average']['average'];
                } elseif ($user_id != '') {
                    return get_ratings_summary($db->get_results ('SELECT user_rating, COUNT(resources_users.id) as ratings_count FROM resources_traits, resources_users WHERE resources_traits.resource_id=resources_users.resource_id AND trait_id=:trait_id AND user_id=:user_id GROUP BY user_rating ORDER BY user_rating ASC',array(':trait_id'=>$this->trait_id, ':user_id' => $user_id)));
                }
                return $this->ratings_summary;
            }
        }

        function get_most_popular_work ($prioritise_works_i_own = false) {
            global $db;
            return new resource ($db->get_var ('SELECT resources_traits.resource_id, resources.title FROM resources_traits, resources_users WHERE resources_traits.resource_id = resources_users.resource_id AND trait_id =:trait_id GROUP BY resources_traits.resource_id ORDER BY COUNT( resources_users.resource_id ) DESC LIMIT 1', array('trait_id' => $this->trait_id)));
        }

        function get_by_label () {
			return 'with the trait: &ldquo;'.$this->get_name().'&rdquo;';
        }

        function get_resources() {
        	if (!isset ($this->all_resources)) {
				$this->all_resources = get_resources_by_trait($this->get_id());
        	}
			return $this->all_resources;
        }

    }
?>