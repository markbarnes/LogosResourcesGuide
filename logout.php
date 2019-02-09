<?php
    require ('init.php');
    sign_out();
    if (isset($_SERVER['HTTP_REFERER']) && stripos($_SERVER['HTTP_REFERER'], BASE_URL) !== FALSE && stripos($_SERVER['HTTP_REFERER'], 'signout.php') === FALSE && stripos($_SERVER['HTTP_REFERER'], 'profile.php') === FALSE)
        $url = $_SERVER['HTTP_REFERER'];
    else
        $url = BASE_URL;
    redirect_to_page ($url, 303);
?>
