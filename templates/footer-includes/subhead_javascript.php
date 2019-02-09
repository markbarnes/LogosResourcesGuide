    <!-- Additional javascript for the 'show more' links -->
    <script>
    $(function () { 
<?php
        foreach ($params as $type => $v) {
            echo "\t\t\$('#{$type}-more').on('click', function (e) {\r\n";
            echo "\t\t\t\$('#{$type}-show-more').hide();\r\n";
            echo "\t\t\t\$('#{$type}-extras').show(1000);\r\n";
            echo "\t\t\treturn false;\r\n";
            echo "\t\t})\r\n";
        }
?>
    });
    </script>
