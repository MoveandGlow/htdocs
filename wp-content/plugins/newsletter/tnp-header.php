<?php
defined('ABSPATH') || exit;

include __DIR__ . '/header.php';

?>

<div id="tnp-notification">
    <?php
    if (isset($controls)) {
        $controls->show();
        $controls->messages = '';
        $controls->errors = '';
    }
    ?>
</div>


