<?php
$p = $_GET['page'];
?>
<ul class="tnp-nav">
    <li class="<?php echo $p === 'newsletter_subscription_form'?'active':''?>"><a href="?page=newsletter_subscription_form">Settings</a></li>
    <li class="<?php echo $p === 'newsletter_subscription_inject'?'active':''?>"><a href="?page=newsletter_subscription_inject">Inside posts</a></li>
    <li class="<?php echo $p === 'newsletter_subscription_popup'?'active':''?>"><a href="?page=newsletter_subscription_popup">Popup</a></li>
</ul>
<?php
unset($p);
?>
