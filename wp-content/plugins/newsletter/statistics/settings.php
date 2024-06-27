<?php
/* @var $this NewsletterStatisticsAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterControls */

defined('ABSPATH') || exit;

if ($controls->is_action()) {
    if ($controls->is_action('save')) {

        $controls->add_toast_saved();
    }
} else {
    $controls->data = $this->get_main_options();
}
?>

<div class="wrap tnp-statistics tnp-statistics-settings" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php //$controls->title_help('/profile-page')  ?>
        <h2><?php esc_html_e('Statistics', 'newsletter') ?></h2>
    </div>

    <div id="tnp-body">

        <?php $controls->show() ?>


        <form id="channel" method="post" action="">
            <?php $controls->init(); ?>

            <div id="tabs">
                <ul>
                    <li><a href="#tabs-general"><?php esc_html_e('General', 'newsletter') ?></a></li>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <li><a href="#tabs-debug">Debug</a></li>
                    <?php } ?>
                </ul>

                <div id="tabs-general">
                    <table class="form-table">

                        <tr>
                            <th>Key</th>
                            <td>
                                <?php $controls->value('key'); ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php if (NEWSLETTER_DEBUG) { ?>
                    <div id="tabs-debug">
                        <pre><?php echo esc_html(wp_json_encode($this->get_db_options(''), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                <?php } ?>
            </div>

            <p>
                <?php //$controls->button_save()  ?>
            </p>

        </form>

    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
