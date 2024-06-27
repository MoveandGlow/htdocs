<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscription') ?>
        <h2><?php _e('Forms', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav-forms.php' ?>

    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <h3>Shortcodes</h3>
        <p>
            The shortcode <code>[newsletter_form]</code> can be used anyware to display the subscription
            form. Use the "shortcode block" in your posts and pages and in your widgets.
        </p>
        <p>
            <a href="https://www.thenewsletterplugin.com/documentation/subscription/subscription-form-shortcodes/" target="_blank">Read more</a>
            to know all the available features.
        </p>

        <h3>Widgets</h3>
        <p>
            Two widgets are provides (standard and minimal). You can use them on
            <?php if (function_exists('wp_is_block_theme') && wp_is_block_theme()) { ?>
            the <a href="<?php echo esc_attr(admin_url('site-editor.php')) ?>" target="_blank">Site Editor</a>
            <?php } else { ?>
            the <a href="<?php echo esc_attr(admin_url('widgets.php')) ?>" target="_blank">Widgets panel</a>
            <?php } ?>

        </p>

    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
