<?php
    class pdo_sqlite extends pdo_custom {

        function __construct ($filename) {
            try {
                parent::__construct("sqlite:{$filename}");
            } catch (PDOException $e) {
                die ('Connection failed: ' . $e->getMessage());
            }
        }
    }

    class pdo_mysql extends pdo_custom {

        function __construct ($host, $dbase, $username, $password) {
            parent::__construct("mysql:host={$host};dbname={$dbase};charset=utf8", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        }
    }

    class pdo_custom extends PDO {

        private function get ($sql, $params, $fetch_style, $ignore_cache = FALSE) {
        	if (!$ignore_cache) {
        		$cache = $this->check_cache ($sql, $params, $fetch_style);
        		if ($cache !== NULL) {
					return $cache;
        		}
        	}
        	$start_time = microtime(true);
            if ($stmt = $this->prepare ($sql)) {
                if (is_array ($params))
                    $stmt->execute($params);
                else
                    $stmt->execute();
                $output = $stmt->fetchAll ($fetch_style);
                $total_time = microtime(true)-$start_time;
                if ($total_time > 0.25) {
                	$this->add_to_cache($output, $sql, $params, $fetch_style);
				} else {
					$this->delete_from_cache ($sql, $params, $fetch_style);
				}
                return $output;
            }
        }

        function get_col ($sql, $params = '', $ignore_cache = FALSE) {
            return $this->get ($sql, $params, PDO::FETCH_COLUMN, $ignore_cache);
        }

        function get_row ($sql, $params = '', $ignore_cache = FALSE) {
            if (stripos($sql, ' limit ') === FALSE) {
                $sql .= ' LIMIT 1';
            }
            $row = $this->get_results ($sql, $params, $ignore_cache);
            if (!$row)
                return false;
            else
                return $row[0];
        }

        function get_var ($sql, $params = '', $ignore_cache = FALSE) {
            if ($col = $this->get ($sql, $params, PDO::FETCH_COLUMN, $ignore_cache)) {
                return reset($col);
            }
        }

        function get_results ($sql, $params = '', $ignore_cache = FALSE) {
            return $this->get ($sql, $params, PDO::FETCH_ASSOC, $ignore_cache);
        }

        function query ($sql, $params = '', $special_binds = '') {
            $stmt = $this->prepare ($sql);
            if (is_array ($params)) {
                if ($special_binds != '') {
                    foreach ($params as $param => $value) {
                        if (is_array($special_binds) && array_key_exists($param, $special_binds)) {
                            $stmt->bindValue($param, $value, $special_binds[$param]);
                        } else {
                            $stmt->bindValue($param, $value, PDO::PARAM_STR);
                        }
                    }
                    $stmt->execute();
                } else {
                    $stmt->execute($params);
                }
            } else {
                $stmt->execute();
            }
			return $stmt;
        }

        function get_cache_file_name ($sql, $params, $fetch_style) {
        	$params = (array)$params;
			return 'cache/'.md5($sql.implode ('', $params).$fetch_style);
        }

        function check_cache ($sql, $params, $fetch_style) {
			$cache_name = $this->get_cache_file_name ($sql, $params, $fetch_style);
			if (strpos ($sql, 'NOW()')!== FALSE || !file_exists ($cache_name)) {
				return NULL;
            } elseif (filemtime($cache_name) < (time()-86400)) { // 24 hours
                return NULL;
			} else {
				return unserialize(file_get_contents($cache_name));
			}
        }

        function add_to_cache ($result, $sql, $params, $fetch_style) {
			$cache_name = $this->get_cache_file_name ($sql, $params, $fetch_style);
			file_put_contents ($cache_name, serialize($result));
		}

		function delete_from_cache ($sql, $params, $fetch_style) {
			$cache_name = $this->get_cache_file_name ($sql, $params, $fetch_style);
			if (file_exists($cache_name)) {
				@unlink ($cache_name);
			}
		}

		function delete_old_cache () {
			$files = glob(ABSPATH."/cache/*");
			$now = time();
			foreach ($files as $file) {
				if (is_file($file)) {
					if ($now - filemtime($file) >= 86400) { // 24 hours
						unlink($file);
					}
				}
			}
		}
    }
?>