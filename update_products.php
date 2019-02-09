<?php
    require ('init.php');
    
    function update_products() {
        global $db;
        $product_ids = $db->get_col ('SELECT product_id FROM products WHERE TIMESTAMPDIFF(DAY, last_checked, NOW()) > 0 ORDER BY last_checked ASC LIMIT 10');
        if ($product_ids) {
            foreach ($product_ids as $product_id) {
                $p = new product ($product_id);
            }
        }
        
    }
    
    update_products();
?>