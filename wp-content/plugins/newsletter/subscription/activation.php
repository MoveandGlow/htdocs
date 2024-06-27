<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;

if (!$controls->is_action()) {

    $controls->data = $this->get_options('', $language);

    $email = Newsletter::instance()->get_email($controls->data['activation_email_id'] ?? 0);

    if (!$email) {
        $email = [];
        $email['type'] = 'welcome';
        $email['editor'] = NewsletterEmails::EDITOR_COMPOSER;
        $email['message'] = NewsletterComposer::instance()->get_preset_content('activation-1');
        $email['track'] = Newsletter::instance()->get_option('track');
        $email['subject'] = 'Confirm your subscription';
        $email['status'] = 'sent';
        $email = NewsletterEmails::instance()->save_email($email);
        $controls->data['activation_email_id'] = $email->id;
        $controls->data['activation_email'] = '';
        $this->save_options($controls->data, '', $language);
    }
    $email = Newsletter::instance()->get_email($controls->data['activation_email_id']);
    TNP_Composer::prepare_controls($controls, $email);
} else {
    if ($controls->is_action('save')) {
        foreach ($controls->data as $k => $v) {
            if (strpos($k, '_custom') > 0) {
                if (!$v) {
                    $controls->data[str_replace('_custom', '', $k)] = '';
                }
                // Remove the _custom field
                unset($controls->data[$k]);
            }
        }

        $options = $this->get_options('', $language);
        $options['confirmation_message'] = NewsletterModule::clean_url_tags($controls->data['confirmation_message']);
        $options['confirmation_subject'] = $controls->data['confirmation_subject'];
        $options['activation_email'] = $controls->data['activation_email'];
        $this->save_options($options, '', $language);
        $email = Newsletter::instance()->get_email($options['activation_email_id']);
        $email->track = Newsletter::instance()->get_option('track');
        TNP_Composer::update_email($email, $controls);
        $email = NewsletterEmails::instance()->save_email($email);
        $controls->add_toast_saved();
        $controls->data = $options;
        TNP_Composer::prepare_controls($controls, $email);

        //NewsletterMainAdmin::instance()->set_completed_step('activation_email');
    }
}

foreach (['confirmation_message'] as $key) {
    if (!empty($controls->data[$key])) {
        $controls->data[$key . '_custom'] = '1';
    }
}


?>

<script>
    var tnp_preset_show = false;
    jQuery(function () {
        jQuery('#options-activation_email').on('change', function () {
            //console.log(document.getElementById('options-activation_email').value);
            switch (document.getElementById('options-activation_email').value) {
                case '':
                    jQuery('#tnp-composer-activation').hide();
                    jQuery('#tnp-stats-button').hide();
                    jQuery('#tnp-standard-activation').show();
                    break;
                case '1':
                    jQuery('#tnp-composer-activation').show();
                    jQuery('#tnp-stats-button').show();
                    jQuery('#tnp-standard-activation').hide();
                    break;
                case '2':
                    jQuery('#tnp-composer-activation').hide();
                    jQuery('#tnp-stats-button').hide();
                    jQuery('#tnp-standard-activation').hide();
                    break;

            }
        });
        switch (document.getElementById('options-activation_email').value) {
            case '':
                jQuery('#tnp-composer-activation').hide();
                jQuery('#tnp-stats-button').hide();
                jQuery('#tnp-standard-activation').show();
                break;
            case '1':
                jQuery('#tnp-composer-activation').show();
                jQuery('#tnp-stats-button').show();
                jQuery('#tnp-standard-activation').hide();
                break;
            case '2':
                jQuery('#tnp-composer-activation').hide();
                jQuery('#tnp-stats-button').hide();
                jQuery('#tnp-standard-activation').hide();
                break;

        }
    });
</script>
<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscription') ?>
        <h2><?php esc_html_e('Subscription', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav.php' ?>

    </div>

    <div id="tnp-body">


        <?php $controls->show(); ?>

        <form method="post" id="tnpc-form" action="" onsubmit="tnpc_save(this); return true;">
            <?php $controls->init(); ?>

            <p>

                <?php
                $controls->select('activation_email',
                        ['' => __('Standard email', 'newsletter'), '1' => __('New email', 'newsletter'), '2' => __('Do not send', 'newsletter')]);
                ?>
                <?php $controls->button_save() ?>
                &nbsp;&nbsp;
                <?php
                $controls->button_icon_statistics(NewsletterStatisticsAdmin::instance()->get_statistics_url($controls->data['activation_email_id']),
                        ['secondary' => true, 'id' => 'tnp-stats-button', 'target' => '_blank'])
                ?>
                <?php if (NEWSLETTER_DEBUG) { ?>
                                <?php $controls->btn_link(home_url('/') . '?na=json&id=' . $email->id, '{}') ?>
                                <?php } ?>
            </p>

            <?php $controls->composer_fields_v2() ?>

            <div id="tnp-standard-activation" style="display: none">
                <table class="form-table">
                    <tr>
                        <th>
                            <?php esc_html_e('Activation email', 'newsletter') ?>
                        </th>
                        <td>

                            <?php $controls->text('confirmation_subject', 70, $this->get_default_text('confirmation_subject')); ?>
                            <br><br>
                            <?php $controls->checkbox2('confirmation_message_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                            <div data-bind="options-confirmation_message_custom">
                                <?php $controls->wp_editor('confirmation_message', ['editor_height' => 150], ['default' => $this->get_default_text('confirmation_message')]); ?>
                            </div>
                            <div data-bind="!options-confirmation_message_custom" class="tnpc-default-text">
                                <?php echo wp_kses_post($this->get_default_text('confirmation_message')) ?>
                            </div>

                        </td>
                    </tr>

                </table>

            </div>
        </form>

        <div id="tnp-composer-activation" style="display: none">

            <?php $controls->composer_load_v2(true, false, 'automated') ?>

        </div>

    </div>
</div>

