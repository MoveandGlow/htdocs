<?php
$p = $_GET['page'];
?>
<ul class="tnp-nav">
    <!--<li class="tnp-nav-title">Subscription</li>-->
    <!--<li class="<?php echo $p === 'newsletter_subscription_index'?'active':''?>"><a href="?page=newsletter_subscription_index">Overview</a></li>-->
    <li class="<?php echo $p === 'newsletter_subscription_form'?'active':''?>"><a href="?page=newsletter_subscription_form">Form</a></li>
    <!--<li class="<?php echo $p === 'newsletter_subscription_sources'?'active':''?>"><a href="?page=newsletter_subscription_sources">Forms</a></li>-->
    <li class="<?php echo $p === 'newsletter_subscription_options'?'active':''?>"><a href="?page=newsletter_subscription_options">Settings and messages</a></li>
    <li class="<?php echo $p === 'newsletter_subscription_welcome'?'active':''?>"><a href="?page=newsletter_subscription_welcome">Welcome email</a></li>
    <?php if (false && class_exists('NewsletterAutoresponder')) { ?>
    <li class="<?php echo $p === 'newsletter_autoresponder_subscription_index'?'active':''?>"><a href="?page=newsletter_autoresponder_subscription_index">Welcome series</a></li>
    <?php } else { ?>
    <li class="<?php echo $p === 'newsletter_subscription_autoresponder'?'active':''?>"><a href="?page=newsletter_subscription_autoresponder">Welcome series</a></li>
    <?php } ?>
    <li class="<?php echo $p === 'newsletter_subscription_antispam'?'active':''?>"><a href="?page=newsletter_subscription_antispam">Antispam</a></li>
    <?php if (NEWSLETTER_DEBUG) { ?>
    <li class="<?php echo $p === 'newsletter_subscription_debug'?'active':''?>"><a href="?page=newsletter_subscription_debug">Debug</a></li>
    <?php } ?>

</ul>
