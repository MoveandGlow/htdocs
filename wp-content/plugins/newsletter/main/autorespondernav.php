<?php

$autoresponder->name = 'Welcome email series (DEMO)';

if ($autoresponder->status) {
    $status_badge = '<span class="tnp-badge-green">' . esc_html('Enabled', 'newsletter') . '</span>';
} else {
    $status_badge = '<span class="tnp-badge-orange">' . esc_html('Disabled', 'newsletter') . '</span>';
}
?>
<?php $controls->title_help('/addons/extended-features/autoresponder-extension/'); ?>
<h2><?php echo esc_html($autoresponder->name) ?> <?php echo $status_badge; ?></h2>
<ul class="tnp-nav">
    <li class="<?php echo $_GET['page'] === ''?'active':''?>"><a href="?page=newsletter_main_autoresponderindex">&laquo;</a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_autoresponderedit'?'active':''?>"><a href="?page=newsletter_main_autoresponderedit"><?php _e('Settings', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_autorespondermessages'?'active':''?>"><a href="?page=newsletter_main_autorespondermessages"><?php _e('Emails', 'newsletter')?></a></li>
    <!--
    <li class="<?php echo $_GET['page'] === 'newsletter_main_autoresponderusers'?'active':''?>"><a href="?page=newsletter_main_autoresponderusers"><?php _e('Subscribers', 'newsletter')?></a></li>
    -->
    <li class="<?php echo $_GET['page'] === 'newsletter_main_autoresponderstatistics'?'active':''?>"><a href="?page=newsletter_main_autoresponderstatistics"><?php _e('Statistics', 'newsletter')?></a></li>
</ul>
