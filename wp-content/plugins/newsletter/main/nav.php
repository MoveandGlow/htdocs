<?php
?>
<ul class="tnp-nav">
    <li class="<?php echo $_GET['page'] === 'newsletter_main_main'?'active':''?>"><a href="?page=newsletter_main_main"><?php _e('General', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_info'?'active':''?>"><a href="?page=newsletter_main_info"><?php _e('Company', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_welcome'?'active':''?>"><a href="?page=newsletter_main_welcome"><?php _e('Setup Wizard', 'newsletter')?></a></li>
</ul>
