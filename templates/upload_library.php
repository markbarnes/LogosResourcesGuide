<?php
    $os = detect_os();
    $instructions['common'] = '<li>In the browse window you&#8217;ll see a list of folders. Your Logos installation will be in a folder called Logos, Logos4, Logos5, or Verbum. Double-click on the one you find.</li>
    <li>Next, double-click on the data folder, and you&#8217;ll see a mysterious folder name such as <em>5amtqjih.p4f</em>. Double-click on that, too.</li>
    <li>Nearly there! Now double-click on the ResourceManager folder, and you should finally see the correct file - ResourceManager.db</li>';
    $instructions ['windows'] = '<li>Click the <strong>Upload my library</strong> button below, type in <strong>%localappdata%</strong>, then click Open.</li>'.$instructions['common'];
    $instructions ['mac'] = '<li>Click the <strong>Upload my library</strong> button below, type in <strong>~/Library</strong></li>, then click Open.</li>'.$instructions['common'];
    $instructions ['unknown'] = '<li>I can&#8217;t tell what Operating System you&#8217;re using, so can&#8217;t provide more specific instructions, sorry. But do make sure you&#8217;re accessing this site from the computer where Logos is installed.</li>';
    $include_in_footer['upload'] = true;

    $user = get_signed_in_userid();
?>
    <div id="upload-library" class="jumbotron">
        <h1>Upload your library</h1>
        <p>To get the most out of this site, you can upload your library catalog. When you do so, you'll be able to see which resources you already own for any product, series, author or subject. It's like the official 'New to Me' service, but it works for every product.</p>
        <p style="border:2px solid #489E45;padding:5px;background-color:#eef7ed"><strong>Privacy policy:</strong> The file you are uploading does not include any personal data such as tags, collections or custom resource or series titles. It’s simply a list of all the resources in your library and all the resources you have hidden. All these resources (including hidden ones, but not personal books) will be added to the public site. However, other users won't know which resources belong to you, nor which resources you have hidden.</p>
        <p>Your library catalog file is stored in a hidden and randomised folder, so it takes a few steps to dig it out:</p>
        <ul>
            <?php echo $instructions[$os];?>
        </ul><?php
            if (($user_id = get_signed_in_userid()) && ($num = get_num_resources ($user_id))) {
                echo "<p><strong>You already have ".number_format($num). " resources uploaded. If you upload your library again, it will replace your existing data.</strong></p>";
            }
            ?>
        <form action="receive_upload.php" method="post" enctype="multipart/form-data">
        <span class="btn btn-success fileinput-button">
            <i class="glyphicon glyphicon-plus"></i>
            <span>Upload my library</span>
            <!-- The file input field used as target for the file upload widget -->
            <input id="fileupload" type="file" name="file">
        </span>
        </form>
        <br>
        <!-- The global progress bar -->
        <div id="progress" class="progress">
            <div class="progress-bar progress-bar-success"></div>
        </div>
        <!-- The container for status reports -->
        <div id="files" class="files"><p></p></div>
      </div>
