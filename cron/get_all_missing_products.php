<?php
    require (realpath(dirname(__FILE__)).'/../init.php');

	function get_all_missing_products() {
		global $db;
	    $start = 1;
	    $end = $db->get_var ('SELECT MAX(product_id)+1000 FROM products WHERE name IS NOT NULL');
	    for ($product_id = $start; $product_id < $end; $product_id++) {
	        $product = new product ($product_id);
		}
	}

	get_all_missing_products();
?>
