<?php
    require (realpath(dirname(__FILE__)).'/../init.php');

    function update_older_resources() {
		global $db;
		while ($resource_ids = $db->get_col ('SELECT resource_id FROM resources WHERE last_updated < (NOW() - INTERVAL 1 WEEK) ORDER BY last_updated ASC LIMIT 500')) {
			update_metadata ($resource_ids);
		}
    }

    update_older_resources();
?>
