<?php
    require (realpath(dirname(__FILE__)).'/../init.php');

    function download_new_products($page_size = 60,$index =0 ) {
		global $db;
		$html = download_url ("https://www.logos.com/products/search?sort=newest&pageSize={$page_size}&Status=Live&start={$index}");
		if ($html && strpos($html, '<ol class="search-results list">')) {
			$html = extract_text($html, '<ol class="search-results list">', '</ol>');
			if ($html) {
				$product_snippets = explode('<div class="article product-result search-result">', $html);
				if ($product_snippets) {
					unset($product_snippets[0]);
					if ($product_snippets) {
						$products_added = 0;
						foreach ($product_snippets as $snippet) {
							$product_id = extract_text ($snippet, '<a href="/product/', '/');
							$product_exists = $db->get_var ('SELECT product_id FROM products WHERE products.product_id=:product_id', array(':product_id' => $product_id));
							if (!$product_exists) {
								$products_added++;
								$product = new product ($product_id);
							}
						}
						if ($products_added) {
							download_new_products ($page_size, $index + $page_size);
	    				}
					}
				}
			}
		}
    }

    download_new_products();
?>