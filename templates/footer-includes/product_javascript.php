    <!-- Additional javascript for the 'show more' links -->
    <script>
    $(function () { 
<?php
        foreach ($params as $id) {
        	echo "\t\t\$('#product-{$id}').hide();\r\n";
        	echo "\t\t\$('#expand-{$id}').on('click', function (e) {\r\n";
            echo "\t\t\t\$('#product-{$id}').toggle(1000);\r\n";
            echo "\t\t\t\$('#expand-{$id}').toggleClass('glyphicon-chevron-down').toggleClass('glyphicon-chevron-up');\r\n";
            echo "\t\t\treturn false;\r\n";
            echo "\t\t})\r\n";
        }
?>
    });
    </script>
