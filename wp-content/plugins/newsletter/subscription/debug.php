<?php
/* @var $this NewsletterSubscription */
/* @var $wpdb wpdb */
defined('ABSPATH') || exit;

global $wpdb;

include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

$items = $wpdb->get_results("select * from {$wpdb->options} where option_name like 'newsletter_subscription%' order by option_name");
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-heading">
        <h2><?php _e('Subscription', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>
        <?php $controls->init(); ?>

        <div id="tabs">

            <ul>
                <?php foreach ($items as $item) { ?>
                    <li><a href="#tabs-<?php echo esc_attr($item->option_name) ?>"><?php echo esc_html(substr($item->option_name, 24)) ?></a></li>
                <?php } ?>
            </ul>

            <?php foreach ($items as $item) { ?>
                <div id="tabs-<?php echo esc_attr($item->option_name) ?>">
                    <pre style="white-space: wrap" wrap="on"><?php echo esc_html(json_encode(maybe_unserialize($item->option_value), JSON_PRETTY_PRINT)) ?></pre>
                </div>

            <?php } ?>

        </div>

    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
