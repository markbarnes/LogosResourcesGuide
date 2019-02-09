<?php
    require ('init.php');


    if (isset($_GET['product_page']) || isset($_GET['product_id'])) {
        if (isset($_GET['product_page'])) {
        	if (substr($_GET['product_page'], 0, 30) != 'https://www.logos.com/product/') {
            	do_header ('New to me', 'new-to-me');
            	echo "<p class=\"bg-warning\">The URL must start with 'https://www.logos.com/product/'.</p>";
			} else {
            	$product_id = substr($_GET['product_page'], 30);
            	$product_id = (int)substr($product_id, 0, strpos($product_id, '/'));
			}
        } else {
        	$product_id = (int)$_GET['product_id'];
		}
        if ($product_id > 0) {
            $product = new product($product_id);
            if ($product->is_valid()) {
                do_header($product->get_name(), 'product-page', $product->get_image_url());
                echo "<table class=\"subhead\">";
                $lines['Price'] = '$'.number_format($product->get_price(), 2);
                $lines['Link'] = $product->get_name(true, true, true);
                foreach ($lines as $heading => $text) {
                    if ($text) {
                        echo '<tr><th scope="row">'.$heading.':</th><td>'.$text.'</td></tr>';
                    }
                }
                echo '</table>';
                echo "<p>{$product->get_description()}</p>";
                // echo "<p><em>Resources shown in white are new to you, whilst you already own the ones shown in yellow. Accuracy is not guaranteed, so double-check before making a purchasing decision. Base packages, pre-pubs and products in community pricing are not supported.</em></p>";
                echo "<hr style=\"clear:both\">";
                if ($product->user_has_some_resources()) {
                    echo tabs_header (array ('all-resources' => "All resources ({$product->get_num_resources()})", 'my-resources' => "My resources ({$product->get_num_owned_resources()})", 'not-my-resources' => "Not my resources ({$product->get_num_unowned_resources()})"), 1, '');
                    global $include_in_footer;
                    $include_in_footer['show-my-resources'] = true;
                    //$include_in_footer['select-my-resources'] = true;
                }
                echo $product->do_detailed_view();
                echo "<br/>";
            }
            $a=1;
        } else {
            echo "<p class=\"bg-warning\">That didn't appear to be a valid product page.</p>";
        }
    } else {
            do_header ('New to me', 'new-to-me');
            echo "<p><strong>New to me</strong> lets you see which resources would be new to you if you were to buy a product from the Logos website. To use this feature, you need to be logged in, have uploaded your own library, and know the URL product page you're interested in.</p>";
    }
    $user_id = get_signed_in_userid();
    if (!$user_id) {
        //echo "<p>You need to be signed in to use the <strong>New to me</strong> feature.</p>";
    } else {
?>
<form method="get">
  <div class="form-group">
    <label for="product_page">Logos product page</label>
    <input type="text" class="form-control" id="product_page" name="product_page" placeholder="https://www.logos.com/product/XXXXX/book-title">
  </div>
  <button type="submit" class="btn btn-default">Submit</button>
</form>
<?php
    }

    do_footer();
?>