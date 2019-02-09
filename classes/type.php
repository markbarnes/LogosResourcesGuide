<?php
    class type extends template {

        protected $valid, $type_id, $type, $num_resources, $num_copies, $num_users, $ratings_average, $ratings_count, $ratings_summary;

        function __construct ($type) {
            global $db;
            $this->valid = false;
            if (is_array ($type) && isset($type['type_id'])) {
                $this->valid=true;
                foreach ($type as $k => $v) {
                    $this->$k = $v;
                }
                $type = $type['type_id'];
            }
            if (!isset ($this->type)) {
                $details = $db->get_row ('SELECT * FROM types WHERE type=:type', array (':type' => $type));
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
                return $this->type;
        }

        function get_id() {
            if ($this->valid)
                return $this->type_id;
        }

        function get_num_resources($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset($this->num_resources)) {
                    $this->num_resources = $db->get_var ('SELECT COUNT(resource_id) AS num FROM resources, types WHERE types.type=resources.type AND types.type=:type',array(':type'=>$this->type_id));
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
                    $this->num_copies = $db->get_var ('SELECT COUNT(resources_users.id) FROM resources, types, resources_users WHERE resources_users.resource_id = resources.resource_id AND resources.type=types.type AND types.type=:type_id',array(':type_id'=>$this->type_id));
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
                return $db->get_var ('SELECT COUNT(resources_users.resource_id) FROM resources_types, resources_users WHERE resources_types.resource_id=resources_users.resource_id AND type_id=:type_id AND user_id=:user_id',array(':type_id'=>$this->type_id, ':user_id'=>$user_id));
            }
        }

        function get_num_users($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset ($this->num_users)) {
                    $this->num_users = $db->get_var ('SELECT COUNT(DISTINCT user_id) FROM resources_types, resources_users WHERE resources_types.resource_id=resources_users.resource_id AND type_id=:type_id',array(':type_id'=>$this->type_id));
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
                    $this->ratings_summary = get_ratings_summary($db->get_results ('SELECT user_rating, COUNT(resources_users.id) as ratings_count FROM resources_types, resources_users WHERE resources_types.resource_id=resources_users.resource_id AND type_id=:type_id GROUP BY user_rating ORDER BY user_rating ASC',array(':type_id'=>$this->type_id)));
                    $this->ratings_count = $this->ratings_summary['average']['count'];
                    $this->ratings_average = $this->ratings_summary['average']['average'];
                } elseif ($user_id != '') {
                    return get_ratings_summary($db->get_results ('SELECT user_rating, COUNT(resources_users.id) as ratings_count FROM resources_types, resources_users WHERE resources_types.resource_id=resources_users.resource_id AND type_id=:type_id AND user_id=:user_id GROUP BY user_rating ORDER BY user_rating ASC',array(':type_id'=>$this->type_id, ':user_id' => $user_id)));
                }
                return $this->ratings_summary;
            }
        }

        function get_most_popular_work ($prioritise_works_i_own = false) {
            global $db;
            return new resource ($db->get_var ('SELECT resources_types.resource_id, resources.title FROM resources_types, resources_users WHERE resources_types.resource_id = resources_users.resource_id AND type_id =:type_id GROUP BY resources_types.resource_id ORDER BY COUNT( resources_users.resource_id ) DESC LIMIT 1', array('type_id' => $this->type_id)));
        }

    }
?>
