<?php
    require ('init.php');
    if (isset ($_POST['email']) && isset($_POST['password'])) {
        $user_id = $db->get_var ('SELECT user_id FROM users WHERE email=:email and password=:password', array (':email' => $_POST['email'], ':password' => md5($_POST['password'])));
        if ($user_id) {
            process_signin($user_id, isset($_POST['remember']) && $_POST['remember'] == 'remember-me');
        } else
            $signin_failed = true;
    }
        
    if (get_signed_in_user()) {
        redirect_to_page (BASE_URL.'/profile.php', 303);
    }

    do_header('Sign in');
?>
    <p>If you have not already registered,<br/>please <a href="register.php">do so now</a>.</p>
    <form class="form-signin" role="form" method="post">
        <input name="email" type="text" class="form-control" placeholder="Email address" required autofocus>
        <input name="password" type="password" class="form-control" placeholder="Password" required>
        <label class="checkbox">
          <input name="remember" type="checkbox" value="remember-me"> Remember me
        </label>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
      </form>
<?php
    do_footer();  
?>
