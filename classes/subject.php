<?php
    class subject extends template {
        
        protected $valid, $subject_id, $subject, $num_resources, $num_copies, $num_users, $ratings_average, $ratings_count, $ratings_summary;
        
        function __construct ($subject) {
            global $db;
            $this->valid = false;
            if (is_array ($subject) && isset($subject['subject_id'])) {
                $this->valid=true;
                foreach ($subject as $k => $v) {
                    $this->$k = $v;
                }
                $subject = $subject['subject_id'];
            }
            if (!isset ($this->subject)) {
                $details = $db->get_row ('SELECT * FROM subjects WHERE subject_id=:subject_id', array (':subject_id' => $subject));
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
        
        function get_name($add_span = false) {
            if ($this->valid) {
				return format_subject($this->subject, $add_span);
            }
        }

        function get_id() {
            if ($this->valid)
                return $this->subject_id;
        }
        
        function get_num_resources($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset($this->num_resources)) {
                    $this->num_resources = $db->get_var ('SELECT COUNT(resource_id) AS num FROM resources_subjects WHERE subject_id=:subject_id',array(':subject_id'=>$this->subject_id));
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
                    $this->num_copies = $db->get_var ('SELECT COUNT(resources_users.id) FROM resources_subjects, resources_users WHERE resources_subjects.resource_id=resources_users.resource_id AND subject_id=:subject_id',array(':subject_id'=>$this->subject_id));
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
                return $db->get_var ('SELECT COUNT(resources_users.resource_id) FROM resources_subjects, resources_users WHERE resources_subjects.resource_id=resources_users.resource_id AND subject_id=:subject_id AND user_id=:user_id',array(':subject_id'=>$this->subject_id, ':user_id'=>$user_id));
            }
        }

        function get_num_users($singular = '', $plural='') {
            global $db;
            if ($this->valid) {
                if (!isset ($this->num_users)) {
                    $this->num_users = $db->get_var ('SELECT COUNT(DISTINCT user_id) FROM resources_subjects, resources_users WHERE resources_subjects.resource_id=resources_users.resource_id AND subject_id=:subject_id',array(':subject_id'=>$this->subject_id));
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
                    $this->ratings_summary = get_ratings_summary($db->get_results ('SELECT user_rating, COUNT(resources_users.id) as ratings_count FROM resources_subjects, resources_users WHERE resources_subjects.resource_id=resources_users.resource_id AND subject_id=:subject_id GROUP BY user_rating ORDER BY user_rating ASC',array(':subject_id'=>$this->subject_id)));
                    $this->ratings_count = $this->ratings_summary['average']['count'];
                    $this->ratings_average = $this->ratings_summary['average']['average'];
                } elseif ($user_id != '') {
                    return get_ratings_summary($db->get_results ('SELECT user_rating, COUNT(resources_users.id) as ratings_count FROM resources_subjects, resources_users WHERE resources_subjects.resource_id=resources_users.resource_id AND subject_id=:subject_id AND user_id=:user_id GROUP BY user_rating ORDER BY user_rating ASC',array(':subject_id'=>$this->subject_id, ':user_id' => $user_id)));
                }
                return $this->ratings_summary;
            }
        }
        
        function get_most_popular_work ($prioritise_works_i_own = false) {
            global $db;
            return new resource ($db->get_var ('SELECT resources_subjects.resource_id, resources.title FROM resources_subjects, resources_users WHERE resources_subjects.resource_id = resources_users.resource_id AND subject_id =:subject_id GROUP BY resources_subjects.resource_id ORDER BY COUNT( resources_users.resource_id ) DESC LIMIT 1', array('subject_id' => $this->subject_id)));
        }

        function get_by_label () {
			return 'with the subject: &ldquo;'.$this->get_name().'&rdquo;';
        }
        
        function do_header() {
			do_header($this->get_name(false));
        }
        
        function get_resources() {
        	if (!isset ($this->all_resources)) {
				$this->all_resources = get_resources_by_subject($this->get_id());
        	}
			return $this->all_resources;
        }
        
    }
?>
