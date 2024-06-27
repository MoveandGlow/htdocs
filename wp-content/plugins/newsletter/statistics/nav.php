<?php
$p = $_GET['page'];
?>
<ul class="tnp-nav">
    <li class="<?php echo $p === 'newsletter_statistics_view' ? 'active' : '' ?>"><a href="?page=newsletter_statistics_view&id=<?php echo (int) $email->id ?>"><?php esc_html_e('Overview', 'newsletter') ?></a></li>
    <li class="<?php echo $p === 'newsletter_statistics_view-urls' ? 'active' : '' ?>"><a href="?page=newsletter_statistics_view-urls&id=<?php echo (int) $email->id ?>"><?php esc_html_e('Links', 'newsletter') ?></a> <span class="tnp-pro-badge">Pro</span></li>
    <li class="<?php echo $p === 'newsletter_statistics_view-users' ? 'active' : '' ?>"><a href="?page=newsletter_statistics_view-users&id=<?php echo (int) $email->id ?>"><?php esc_html_e('Subscribers', 'newsletter') ?></a> <span class="tnp-pro-badge">Pro</span></li>
    <?php if (class_exists('NewsletterReports')) { ?>
        <li><a href="?page=newsletter_reports_view&id=<?php echo $email->id ?>"><?php esc_html_e('Full report', 'newsletter') ?></a></li>
    <?php } ?>
</ul>
<?php
unset($p);
?>