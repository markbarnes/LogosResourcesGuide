<?php
    class series extends template {
        
        protected $valid, $series, $num_resources, $num_copies, $num_users, $ratings_average, $ratings_count, $ratings_summary;
        
        function __construct ($series) {
            global $db;
            $this->valid = false;
            if (is_array ($series) && isset($series['series'])) {
                $this->valid=true;
                foreach ($series as $k => $v) {
                    $this->$k = $v;
                }
                $series = $series['series'];
            }
            if (!isset ($this->series)) {
                $details = $db->get_row ('SELECT DISTINCT series FROM resources WHERE series=:series', array (':series' => $series));
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
                return $this->series;
        }

        function get_id() {
            if ($this->valid)
                return $this->series;
        }

        function get_num_resources($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset($this->num_resources)) {
                    $this->num_resources = $db->get_var ('SELECT COUNT(resource_id) AS num FROM resources WHERE series=:series',array(':series'=>$this->series));
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
                    $this->num_copies = $db->get_var ('SELECT COUNT(resources_users.id) FROM resources, resources_users WHERE resources.resource_id=resources_users.resource_id AND series=:series',array(':series'=>$this->series));
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
                return $db->get_var ('SELECT COUNT(resources_users.resource_id) FROM resources, resources_users WHERE resources.resource_id=resources_users.resource_id AND series=:series AND user_id=:user_id',array(':series'=>$this->series, ':user_id'=>$user_id));
            }
        }

        function get_num_users($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset ($this->num_users)) {
                    $this->num_users = $db->get_var ('SELECT COUNT(DISTINCT user_id) FROM resources, resources_users WHERE resources.resource_id=resources_users.resource_id AND series=:series',array(':series'=>$this->series));
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
                    $this->ratings_summary = get_ratings_summary($db->get_results ('SELECT user_rating, COUNT(resources_users.id) as ratings_count FROM resources_series, resources_users WHERE resources_series.resource_id=resources_users.resource_id AND series_id=:series_id GROUP BY user_rating ORDER BY user_rating ASC',array(':series_id'=>$this->series_id)));
                    $this->ratings_count = $this->ratings_summary['average']['count'];
                    $this->ratings_average = $this->ratings_summary['average']['average'];
                } elseif ($user_id != '') {
                    return get_ratings_summary($db->get_results ('SELECT user_rating, COUNT(resources_users.id) as ratings_count FROM resources_series, resources_users WHERE resources_series.resource_id=resources_users.resource_id AND series_id=:series_id AND user_id=:user_id GROUP BY user_rating ORDER BY user_rating ASC',array(':series_id'=>$this->series_id, ':user_id' => $user_id)));
                }
                return $this->ratings_summary;
            }
        }
        
        function get_most_popular_work ($prioritise_works_i_own = false) {
            global $db;
            return new resource ($db->get_var ('SELECT resources_series.resource_id, resources.title FROM resources_series, resources_users WHERE resources_series.resource_id = resources_users.resource_id AND series_id =:series_id GROUP BY resources_series.resource_id ORDER BY COUNT( resources_users.resource_id ) DESC LIMIT 1', array('series_id' => $this->series_id)));
        }

        function get_by_label () {
			return 'in the series: &ldquo;'.$this->get_name().'&rdquo;';
        }

        function get_resources() {
        	if (!isset ($this->all_resources)) {
				$this->all_resources = get_resources_by_series($this->get_id());
        	}
			return $this->all_resources;
        }
        
    }
?>
