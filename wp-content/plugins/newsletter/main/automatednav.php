<?php
defined('ABSPATH') || exit;
?>
<?php $controls->title_help('/addons/extended-features/automated-extension/') ?>
<h2><?php echo esc_html($channel->data['name']) ?> (demo)</h2>
<ul class="tnp-nav">
    <li class="<?php echo $_GET['page'] === ''?'active':''?>"><a href="?page=newsletter_main_automatedindex">&laquo;</a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_automatededit'?'active':''?>"><a href="?page=newsletter_main_automatededit"><?php _e('Settings', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_automatedtemplate'?'active':''?>"><a href="?page=newsletter_main_automatedtemplate"><?php _e('Template', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_automatednewsletters'?'active':''?>"><a href="?page=newsletter_main_automatednewsletters"><?php _e('Newsletters', 'newsletter')?></a></li>
</ul>
