<?php
    class tag extends template {
        
        protected $valid, $tag, $num_resources, $num_copies, $ratings_average, $ratings_count, $ratings_summary;
        
        function __construct ($tag) {
            global $db;
            $this->valid = false;
            $resources = $db->get_results ('SELECT resource_id, count FROM community_tags WHERE tag=:tag_id', array (':tag_id' => $tag));
            if ($resources) {
				$this->valid = true;
				$this->resources = $resources;
				$this->tag = $tag;
				$this->num_resources = count ($resources);
				$this->num_copies = $db->get_var ('SELECT SUM(count) FROM community_tags WHERE tag=:tag_id', array (':tag_id' => $tag));
            }
        }
        
        function supports_users() {
			return false;
        }
        
        function get_name() {
            if ($this->valid)
                return $this->tag;
        }

        function get_num_resources($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
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
                    $this->num_copies = $db->get_var ('SELECT COUNT(resources_users.id) FROM resources_users_tags, resources_users WHERE resources_users_tags.resource_id=resources_users.resource_id AND tag_id=:tag_id',array(':tag_id'=>$this->tag_id));
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
                return $db->get_var ('SELECT COUNT(resources_users.resource_id) FROM resources_users_tags, resources_users WHERE resources_users_tags.resource_id=resources_users.resource_id AND tag_id=:tag_id AND resources_users.user_id=:user_id',array(':tag_id'=>$this->tag_id, ':user_id'=>$user_id));
            }
        }

        function get_num_users($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset ($this->num_users)) {
                    $this->num_users = $db->get_var ('SELECT COUNT(DISTINCT resources_users.user_id) FROM resources_users_tags, resources_users WHERE resources_users_tags.resource_id=resources_users.resource_id AND tag_id=:tag_id',array(':tag_id'=>$this->tag_id));
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
                    $this->ratings_summary = get_ratings_summary($db->get_results ('SELECT user_rating, COUNT(resources_users.id) as ratings_count FROM resources_users_tags, resources_users WHERE resources_users_tags.resource_id=resources_users.resource_id AND tag_id=:tag_id GROUP BY user_rating ORDER BY user_rating ASC',array(':tag_id'=>$this->tag_id)));
                    $this->ratings_count = $this->ratings_summary['average']['count'];
                    $this->ratings_average = $this->ratings_summary['average']['average'];
                } elseif ($user_id != '') {
                    return get_ratings_summary($db->get_results ('SELECT user_rating, COUNT(resources_users.id) as ratings_count FROM resources_users_tags, resources_users WHERE resources_users_tags.resource_id=resources_users.resource_id AND tag_id=:tag_id AND resources_users_tags.user_id=:user_id GROUP BY user_rating ORDER BY user_rating ASC',array(':tag_id'=>$this->tag_id, ':user_id' => $user_id)));
                }
                return $this->ratings_summary;
            }
        }
        
        function get_most_popular_work ($prioritise_works_i_own = false) {
            global $db;
            return new resource ($db->get_var ('SELECT resources_users_tags.resource_id, resources.title FROM resources_users_tags, resources_users WHERE resources_users_tags.resource_id = resources_users.resource_id AND tag_id =:tag_id GROUP BY resources_users_tags.resource_id ORDER BY COUNT( resources_users.resource_id ) DESC LIMIT 1', array('tag_id' => $this->tag_id)));
        }

    }
?>
