<?php
$p = $_GET['page'];
?>
<ul class="tnp-nav">
    <!--<li class="tnp-nav-title">Subscription</li>-->
    <li class="<?php echo $p === 'newsletter_emails_index'?'active':''?>"><a href="?page=newsletter_emails_index"><?php esc_html_e('Newsletters', 'newsletter')?></a></li>
    <li class="<?php echo $p === 'newsletter_emails_presets'?'active':''?>"><a href="?page=newsletter_emails_presets"><?php esc_html_e('Templates', 'newsletter')?></a></li>
    <li class="<?php echo $p === 'newsletter_emails_automated'?'active':''?>"><a href="?page=newsletter_emails_automated"><?php esc_html_e('Recurring', 'newsletter')?></a></li>
    <li class="<?php echo $p === 'newsletter_emails_autoresponder'?'active':''?>"><a href="?page=newsletter_emails_autoresponder"><?php esc_html_e('Series', 'newsletter')?></a></li>
</ul>
<?php
unset($p);
?>