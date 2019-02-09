<?php
     $user = get_signed_in_user();
     if (($space = strpos($user['user'], ' ')) !== FALSE)
        $first_name = substr($user['user'], 0, $space);
     else
        $first_name = $user['user'];
     $top_left_menu = array ('Home' => 'index.php', "{$first_name}&#8217;s Profile" => 'profile.php', 'Resources' => 'resource.php', 'Authors' => 'author.php', 'Publishers' => 'publisher.php', 'Subjects' => 'subject.php', 'Series' => 'series.php');
     $top_right_menu = array ('Register' => 'register.php', "Sign in" => 'login.php');
     $logged_in_only = array ('profile.php');
     $current_page =  basename($_SERVER['SCRIPT_NAME']);
?>
    <!-- Fixed navbar -->
    <div class="navbar navbar-default navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            <?php
                foreach ($top_left_menu as $title => $page) {
                    if (array_search($page, $logged_in_only) === FALSE || $user) {
                        $active = ($current_page == $page ? ' class="active"' : '');
                        echo "\t\t\t<li{$active}><a href=\"{$page}\">{$title}</a></li>\r\n";
                    }
                }
		        $menu = $highlight_menu = '';
		        ?>
          </ul>
          <ul class="nav navbar-nav navbar-right">
		    <?php if ($user) { ?>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo $user['user'];?> <b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a href="upload.php">Upload library catalog</a></li>
                <?php if  (($user_id = get_signed_in_userid()) && get_num_resources ($user_id)) { ?>
                <li><a href="remove_library.php">Remove your library</a></li>
                <?php } ?>
                <li class="divider"></li>
                <li><a href="logout.php">Sign out</a></li>
              </ul>
            </li>
            <?php } else { ?>
            <?php
                foreach ($top_right_menu as $title => $page) {
                    $active = ($current_page == $page ? ' class="active"' : '');
                    echo "\t\t\t<li{$active}><a href=\"{$page}\">{$title}</a></li>\r\n";
                }
            ?>
            <?php } ?>
          </ul>
		  <!--
		  <div id="search" class="col-sm-3 col-md-3">
		  	<form class="navbar-form navbar-right" role="search" action="search.php">
				<div class="input-group">
		            <input type="text" class="form-control" placeholder="Search for title, author, publisher or ISBN" name="search" id="srch-term">
		            <div class="input-group-btn">
		                <button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
		            </div>
		        </div>
		    </form>
		  </div>
		  -->
        </div><!--/.nav-collapse -->
      </div>
    </div>
<?php

?>
