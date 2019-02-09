<?php
    require ('init.php');
    
    function update_this_metadata() {
        if (isset($_GET['resource'])) {
            update_metadata($_GET['resource']);
        }
    }
    
    update_this_metadata();
?>