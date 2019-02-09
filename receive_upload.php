<?php
    require ('init.php');

	function import_library ($filename) {
		global $db;
        $catalog = new  pdo_sqlite ($filename);
		if (!$user_id = get_signed_in_userid()) {
			die (json_encode(array ('status' => 'forbidden', 'text' => 'You must remain logged in when uploading files.')));
        }
		$db->query ('DELETE FROM resources_users WHERE user_id=:user_id', array (':user_id' => $user_id));
		$hidden_resources = $catalog->get_results ('SELECT ResourceId FROM HiddenResources WHERE IsHidden=1 AND NOT(ResourceId LIKE "PBB:%")');
		if ($hidden_resources) {
			foreach ($hidden_resources as $h) {
				$db->query('INSERT IGNORE INTO resources_users (user_id, resource_id, hidden) VALUES (:user_id, :resource_id, TRUE)', array (':user_id' => $user_id, ':resource_id' => $h['ResourceId']));
			}
		}
		$resources = $catalog->get_results ('SELECT ResourceId FROM Resources WHERE NOT(ResourceId LIKE "PBB:%")');
		if ($resources) {
			foreach ($resources as $resource) {
				$db->query('INSERT IGNORE INTO resources_users (user_id, resource_id, hidden) VALUES (:user_id, :resource_id, FALSE)', array (':user_id' => $user_id, ':resource_id' => $resource['ResourceId']));
			}
		} else {
			die (json_encode(array ('status' => 'error', 'text' => "That doesn't seem to be a valid file.")));
		}
		flush_cache();
		$missing_resources = $db->get_col('SELECT resources_users.resource_id FROM resources_users LEFT JOIN resources ON resources_users.resource_id=resources.resource_id WHERE resources.resource_id IS NULL');
		if ($missing_resources) {
			update_metadata ($missing_resources);
		}
		$message = "Successfully uploaded ".number_format(count($resources))." resources (and ".number_format(count($hidden_resources))." hidden resources).";
		if (($c = count ($missing_resources)) > 0) {
			$message .= " This includes ".number_format ($c).' resources that are new to the site, and unique to you.';
		}
		die (json_encode(array ('status' => 'success', 'text' => $message, 'num_records' => count($resources))));
	}

    $user = get_signed_in_userid();

    if (!isset($_FILES['file']['tmp_name']) || !$user) {
        die (json_encode(array ('status' => 'error', 'text' => 'Something went wrong the upload. Please try again.')));
    } else {
		import_library ($_FILES['file']['tmp_name']);
    }

?>