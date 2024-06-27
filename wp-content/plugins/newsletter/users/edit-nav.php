<?php
?>
<ul class="tnp-nav">
    <li><a href="?page=newsletter_users_index"><i class="fas fa-chevron-left"></i></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_users_edit'?'active':''?>"><a href="?page=newsletter_users_edit&id=<?php echo $user->id?>"><?php esc_html_e('Data', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_users_logs'?'active':''?>"><a href="?page=newsletter_users_logs&id=<?php echo $user->id?>"><?php esc_html_e('Logs', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_users_newsletters'?'active':''?>"><a href="?page=newsletter_users_newsletters&id=<?php echo $user->id?>"><?php esc_html_e('Newsletters', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_users_autoresponders'?'active':''?>"><a href="?page=newsletter_users_autoresponders&id=<?php echo $user->id?>"><?php esc_html_e('Autoresponders', 'newsletter')?></a></li>
</ul>
