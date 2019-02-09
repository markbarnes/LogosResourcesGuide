<?php
    require('init.php');
    require_login();
    if (($user_id = get_signed_in_userid())) {
        $secure = md5($db->query('SELECT AVG(resources_users.id) FROM resources_users WHERE user_id=:user_id', array(':user_id' => $user_id)));
        if (isset($_POST['remove_library']) && $_POST['remove_library'] == $secure) {
            delete_all_resources_for_user ($user_id);
            delete_all_tags_for_user ($user_id);
        }
    }
    if ($num = get_num_resources ($user_id)) {
        do_header('', 'home');

?>
    <div id="upload-library" class="jumbotron">
        <h1>Remove your library</h1>
        <p>You have <?php echo number_format($num); ?> resources in your catalog. If you wish, you can permanently remove your library data from this site. <strong>This cannot be undone</strong>, although you may re-upload your library in the future, if you wish.</p>
        <form role="form" method="post">
        <input type="hidden" name="remove_library" value="<?php echo $secure; ?>">
        <input type="submit" class="btn btn-danger btn-large" value="Remove my library &raquo;">
        </form>
<?php
    } else {
        redirect_to_page(BASE_URL.'/profile.php');
    }
    do_footer();
?>