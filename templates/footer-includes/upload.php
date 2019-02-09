    <!-- Additional javascript for file uploader -->
    <script src="js/jquery.ui.widget.js"></script>
    <script src="js/jquery.iframe-transport.js"></script>
    <script src="js/jquery.fileupload.js"></script>        
    <script>
    $(function () {
        'use strict';
        $('#fileupload').fileupload({
            url: 'receive_upload.php',
            type: 'POST',
            autoUpload: true,
            maxNumberOfFiles: 1,
            dataType: 'json',
            done: function (e, data) {
                $('#files').html('<p>'+data.result.text+'</p>');
            },
            progressall: function (e, data) {
                $('#files').html('<p>Please wait while the file is uploaded. On an average connection this should take about 30 seconds. If you own lots of resources that are not already in the database, the process may appear to stall at the end, but if you give it a minute or two, you should see a message telling you that the file has been successfully received.</p>');
                var progress = parseInt(data.loaded / data.total * 100, 10);
                $('#progress .progress-bar').css('width', progress + '%');
            }
        }).prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');
    });
    </script>