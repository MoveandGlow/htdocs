<?php
?>
<ul class="tnp-nav">
    <li class="<?php echo $_GET['page'] === 'newsletter_main_index'?'active':''?>"><a href="?page=newsletter_main_index"><?php esc_html_e('Overview', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_flow'?'active':''?>"><a href="?page=newsletter_main_flow"><?php esc_html_e('Flow view', 'newsletter')?></a></li>
</ul>
