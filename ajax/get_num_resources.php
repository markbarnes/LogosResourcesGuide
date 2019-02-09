<?php
    require_once ('init.php');
    
    $user = get_signed_in_userid();
    
    if ($user) {
        $num_resources = get_num_resources($user);
        die (json_encode(array ('status' => 'success', 'num_resources' => $num_resources)));
    }
    
    die (json_encode (array ('status' => 'error')));

?>
