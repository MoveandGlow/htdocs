<?php
header('Content-Type: text/html;charset=UTF-8');
header('X-Robots-Tag: noindex,nofollow,noarchive');
header('Cache-Control: no-cache,no-store,private');
?><!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css">
            .tnp-captcha {
                text-align: center;
                margin: 200px auto 0 auto !important;
                max-width: 300px !important;
                padding: 10px !important;
                font-family: "Open Sans", sans-serif;
                background: #ECF0F1;
                border-radius: 5px;
                padding: 50px !important;
                border: none !important;
            }
            input[type=text] {
                width: 50px;
                padding: 10px 10px;
                border: none;
                border-radius: 2px;
                margin: 0px 5px;
            }
            input[type=button] {
                text-align: center;
                border: none;
                padding: 10px 15px;
                font-family: "Open Sans", sans-serif;
                background-color: #27AE60;
                color: white;
                cursor: pointer;
            }
        </style>
        <script>
            var captcha = <?php echo $captcha ? 'true' : 'false'; ?>;
            function m(ev) {
                let e = new Date();
                e.setTime(e.getTime() + 300 * 1000);
                document.cookie = "tnpab=1; expires=" + e.toGMTString() + "; path=/";
                let f = document.getElementById("form");
                f.action = location.pathname;
                f.method = 'POST';
                f.submit();
            }
            if (!captcha) {
                window.setTimeout(() => {
                    m();
                }, 500);
            }
            function go() {
                if (!captcha)
                    return;

                m();
            }
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
            if (isset($_SERVER['HTTP_REFERER'])) {
                echo '<input type="hidden" name="nhr" value="' . esc_attr(sanitize_url($_SERVER['HTTP_REFERER'])) . '">';
            }
            echo '<input type="hidden" name="ts" value="' . time() . '">';
            echo '</div>';

            if ($captcha) {
                echo '<div class="tnp-captcha">';
                echo '<input type="text" name="n1" value="', rand(1, 9), '" readonly style="width: 50px">';
                echo '+';
                echo '<input type="text" name="n2" value="', rand(1, 9), '" readonly style="width: 50px">';
                echo '=';
                echo '<input type="text" name="n3" value="" placeholder="?" style="width: 50px">';
                echo '<br><br>';
                echo '<input type="button" value="&gt;" onclick="go(); return false;">';
                echo '</div>';
            }
            ?>
        </form>
    </body>
</html>
