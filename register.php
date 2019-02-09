<?php
    require ('init.php');
    $add_text = '';
    if (isset ($_POST['name']) && isset ($_POST['email']) && isset($_POST['password'])) {
        if (get_user_by_email ($_POST['email'])) {
            $add_text = "    <div class=\"warning\"><p>There is already an account registered with that email address.</p><p>Please <a href=\"login.php\">sign in</a> to continue. </p></div>\r\n";
        } elseif (strlen($_POST['password']) < 6) {
            $add_text = "    <div class=\"warning\"><p>Your password must have at least six characters.</p></div>\r\n";
        } else {
            $user_id = add_user($_POST['name'], $_POST['email'], $_POST['password'], isset($_POST['private']) ? (boolean)($_POST['[private]'] = 'private') : '', isset($_POST['faithlife']) ? $_POST['faithlife'] : '');
            if ($user_id) {
                process_signin ($user_id);
            } else {
                $add_text = "    <div class=\"warning\"><p>Sorry, there was an unexplained error. Please try again.</p></div>\r\n";
            }
        }
    }
        
    if (get_signed_in_user()) {
        redirect_to_page (BASE_URL.'/profile.php', 302);
    }

    do_header('Register');
    echo $add_text;
?>
    <p>If you have already registered, please <a href="login.php">sign in</a>.</p>
    <form class="form-register" role="form" method="post">
        <input name="name" type="text" class="form-control" placeholder="Name" <?php if (isset($_POST['name'])) echo 'value="'.htmlentities($_POST['name']).'" '; ?>required autofocus>
        <input name="email" type="text" class="form-control" placeholder="Email address" <?php if (isset($_POST['email'])) echo 'value="'.htmlentities($_POST['email']).'" '; ?>required>
        <input name="password" type="password" class="form-control" placeholder="Password" required>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Register</button>
    </form>
<?php
    do_footer();  
?>
