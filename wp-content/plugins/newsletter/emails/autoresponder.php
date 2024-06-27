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
        <h2><?php esc_html_e('Newsletters', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav.php' ?>

    </div>

    <div id="tnp-body">


        <?php $controls->show(); ?>

        <?php if (!class_exists('NewsletterAutoresponder')) { ?>

            <p>
                To create one or more email series the <a href="?page=newsletter_main_autoresponder">Autoresponder Addon</a> is required.
            </p>


        <?php } else { ?>

            <p>
                Configure your welcome/follow up series on the <a href="?page=newsletter_autoresponder_index">Autoresponder settings page</a>.
            </p>

        <?php } ?>

    </div>
</div>

