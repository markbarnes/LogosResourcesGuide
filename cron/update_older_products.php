<?php
    require (realpath(dirname(__FILE__)).'/../init.php');

    function update_older_products() {
		global $db;
		$db->query("DELETE FROM products WHERE site = 'Downloading' AND last_checked < (NOW() - INTERVAL 15 MINUTE)");
		$product_ids = $db->get_col ("SELECT product_id FROM products WHERE last_checked < (NOW() - INTERVAL 1 MONTH) AND site = 'Logos' ORDER BY last_checked ASC LIMIT 150");
		foreach ($product_ids as $p) {
			download_product_details ($p);
		};
    }

    update_older_products();
?>