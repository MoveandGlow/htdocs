<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

use Newsletter\Integrations;

defined('ABSPATH') || exit;

$extensions_url = '?page=newsletter_main_extensions';
if (class_exists('NewsletterExtensions')) {
    $extensions_url = '?page=newsletter_extensions_index';
}
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscription') ?>
        <h2><?php esc_html_e('Forms', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav-forms.php' ?>
    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <p>
            Quick access to all subscription sources.
        </p>
        <form method="post" action="">
            <?php $controls->init(); ?>

            <table class="widefat" style="width: auto">
                <thead>
                    <tr>
                        <th style="width: 30rem" colspan="2">Form</th>

                        <th style="width: 5rem">&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Newsletter</td>
                        <td>Standard form</td>
                        <td style="white-space: nowrap">
                            <?php $controls->button_icon_configure('?page=newsletter_subscription_form') ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Newsletter</td>
                        <td>After posts' content</td>

                        <td style="white-space: nowrap">
                            <?php $controls->button_icon_configure('?page=newsletter_subscription_inject') ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Newsletter</td>
                        <td>Popup</td>

                        <td style="white-space: nowrap">
                            <?php $controls->button_icon_configure('?page=newsletter_subscription_popup') ?>
                        </td>
                    </tr>


                    <tr>
                        <td>WordPress</td>
                        <td>WP User Registration</td>

                        <td style="white-space: nowrap">
                            <?php if (class_exists('NewsletterWpUsers')) { ?>
                                <?php $controls->button_icon_configure('?page=newsletter_wpusers_index') ?>
                            <?php } else { ?>
                                <?php $controls->btn_link($extensions_url . '#newsletter-wpusers', 'Addon required', ['tertiary' => true, 'target' => '_blank']) ?>
                            <?php } ?>
                        </td>
                    </tr>

                    <tr>
                        <td>WordPress</td>
                        <td>Comment form</td>
                        <td style="white-space: nowrap">
                            <?php if (class_exists('NewsletterComments')) { ?>
                                <?php $controls->button_icon_configure('?page=newsletter_comments_index') ?>
                            <?php } else { ?>
                                <?php $controls->btn_link($extensions_url . '#newsletter-comments', 'Addon required', ['tertiary' => true, 'target' => '_blank']) ?>
                            <?php } ?>
                        </td>
                    </tr>

                    <?php Integrations::source_rows(Integrations::get_cf7_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_gravityforms_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_wpforms_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_forminator_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_formidable_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_ninjaforms_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_fluentforms_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_woocommerce_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_edd_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_ultimatemember_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_pmpro_sources(), $controls) ?>

                </tbody>
            </table>

            <p>Integrations with many other plugins are available on the addons page.<p>

        </form>
    </div>
</div>