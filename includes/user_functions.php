<?php

    function require_login() {
        if (!get_signed_in_user()) {
            redirect_to_page (BASE_URL.'/login.php', 302);
        };
    }

    function get_secure_session_id ($user_id) {
        return md5 ($user_id.$_SERVER['HTTP_USER_AGENT'].'random_text');
    }
    
    function process_signin ($user_id, $remember = false) {
        $cookie_length = $remember ? (time()+60*60*24*30) : 0; // 30 days
        $_COOKIE['mll_session'] = get_secure_session_id ($user_id);
        $_COOKIE['mll_id'] = $user_id;
        setcookie ('mll_session', $_COOKIE['mll_session'], $cookie_length);
        setcookie ('mll_id', $user_id, $cookie_length);
    }
          
    function sign_out() {
        $before = time()-3600;
        setcookie ('mll_session', '', $before);
        setcookie ('mll_id', '', $before);
    }
    
    function get_signed_in_userid() {
        if (isset($_COOKIE['mll_session']) && isset($_COOKIE['mll_id'])) {
            if ($_COOKIE['mll_session'] == get_secure_session_id ($_COOKIE['mll_id'])) {
                return $_COOKIE['mll_id'];
            } else {
                sign_out();
            }
        }
        return false;
    }
    
    function get_signed_in_user($field = '') {
        global $db;
        $user_id = get_signed_in_userid();
        if (!$user_id)
            return false;
        $user = $db->get_row ("SELECT * FROM users WHERE user_id=:user_id", array (':user_id' => $user_id));
        if ($field == '' || !isset ($user[$field])) {
            return $user;
        } else {
            return $user[$field];
        }
    }
    
    function get_user_by_email ($email_address) {
        global $db;
        return $db->get_row ("SELECT * FROM users WHERE email=:email", array (':email' => $email_address));
    }
    
    function add_user ($name, $email, $password, $is_private, $faithlife) {
        global $db;
        $params = array (':name' => $name, ':email' => $email, ':password' => $password, ':is_private' => (boolean)$is_private, ':faithlife' => $faithlife);
        $db->query('INSERT INTO users (user, email, password, private, faithlife) VALUES (:name, :email, md5(:password), :is_private, :faithlife)', $params);
        return $db->lastInsertId();
    }
    
?>