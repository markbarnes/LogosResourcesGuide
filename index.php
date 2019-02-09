<?php
    require ('init.php');
    if (isset($_GET['flush_cache'])) {
		flush_cache();
    }
    if (!($user_id = get_signed_in_userid())) {
        do_header('', 'home');
?>
    <div class="jumbotron">
        <h1>Unofficial Logos Resources Guide</h1>
        <p>This site is intended to allow get more information about the resources available for <a href="http://www.logos.com/">Logos Bible Software </a>. As well as being able to browse by author and publisher, you can also browse by subject and series. You can also view individual books that aren't available for sale individually. This means, for example, you can see at a glance all of the books written by your favourite author, without having to wade through lots of collections that happen to include one of his books.</p>
        <p>To get the most out of the site, you should upload your library catalog. When you do so, you'll be able to see which resources you already own for any product, series, author or subject. It's like the official 'New to Me' service, but it works for every product.</p>
        <p>
            <a class="btn btn-lg btn-primary" href="register.php" role="button">Upload my library &raquo;</a>
        </p>
        <p><br/>Or, if you've already registered, you can <a href="login.php">sign in</a>.</p>
      </div>
<?php
    } elseif (!get_num_resources ($user_id)) {
        do_header('', 'home');
        require ('templates/upload_library.php');
    } else {
        do_header('Global stats', 'my-profile');
        echo "<table id=\"main-stats\">";
        echo get_profile_module ('library_summary');
        echo get_profile_module ('popular_authors');
        echo get_profile_module ('popular_publishers');
        echo get_profile_module ('popular_subjects');
        echo get_profile_module ('popular_series');
        echo "</table>";

    }
    do_footer();
?>
