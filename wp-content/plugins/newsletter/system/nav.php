<?php ?>
<ul class="tnp-nav">
    <!--<li class="tnp-nav-title">Subscription</li>-->
    <li class="<?php echo $_GET['page'] === 'newsletter_system_status' ? 'active' : '' ?>">
        <?php if ($system_warnings['status']) { ?>
            <i class="fas fa-exclamation-triangle tnp-menu-warning" style="color: red;"></i>
        <?php } ?>
        <a href="?page=newsletter_system_status">General</a>
    </li>
    <li class="<?php echo $_GET['page'] === 'newsletter_system_delivery' ? 'active' : '' ?>"><a href="?page=newsletter_system_delivery">Delivery</a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_system_scheduler' ? 'active' : '' ?>">
        <?php if ($system_warnings['scheduler']) { ?>
            <i class="fas fa-exclamation-triangle tnp-menu-warning" style="color: red;"></i>
        <?php } ?>
        <a href="?page=newsletter_system_scheduler">Scheduler</a>
    </li>
    <li class="<?php echo $_GET['page'] === 'newsletter_system_logs' ? 'active' : '' ?>"><a href="?page=newsletter_system_logs">Logs</a></li>
    <?php if (NEWSLETTER_DEBUG) { ?>
        <li class="<?php echo $_GET['page'] === 'newsletter_system_backup' ? 'active' : '' ?>"><a href="?page=newsletter_system_backup">Settings backup</a></li>
    <?php } ?>
    <li><a href="<?php echo admin_url('site-health.php') ?>" target="_tab">WP Site Health</a></li>
</ul>
