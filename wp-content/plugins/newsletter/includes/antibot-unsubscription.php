<?php
header('Content-Type: text/html;charset=UTF-8');
header('X-Robots-Tag: noindex,nofollow,noarchive');
header('Cache-Control: no-cache,no-store,private');

// set cookie?
?><!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script>
            addEventListener("DOMContentLoaded", (event) => {
                let e = new Date();
                e.setTime(e.getTime() + 300 * 1000);
                document.cookie = "tnpab=1; expires=" + e.toGMTString() + "; path=/";
                let f = document.getElementById("form");
                f.action = location.pathname;
                f.method = 'POST';
                f.submit();
            });
        </script>
    </head>
    <body>
        <form method="get" action="" id="form">

            <?php
            foreach ($_REQUEST as $name => $value) {
                if ($name == 'submit')
                    continue;
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        echo '<input type="hidden" name="';
                        echo esc_attr($name);
                        echo '[', esc_attr($k), ']" value="';
                        echo esc_attr(stripslashes($v));
                        echo '">';
                    }
                } else {
                    echo '<input type="hidden" name="', esc_attr($name), '" value="', esc_attr(stripslashes($value)), '">';
                }
            }

            echo '<input type="hidden" name="ts" value="' . time() . '">';
            ?>
        </form>
    </body>
</html>
