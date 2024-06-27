<?php
/* @var $this NewsletterUnsubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */


defined('ABSPATH') || exit;

if (!$controls->is_action()) {
    $controls->data = $this->get_options('', $language);
} else {
    foreach ($controls->data as $k => $v) {
        if (strpos($k, '_custom') > 0) {
            if (empty($v)) {
                $controls->data[str_replace('_custom', '', $k)] = '';
            }
            // Remove the _custom field
            unset($controls->data[$k]);
        }
    }

    if ($controls->is_action('save')) {
        $controls->data = wp_kses_post_deep($controls->data);
        $this->save_options($controls->data, '', $language);
        $controls->data = $this->get_options('', $language);
        $controls->add_toast_saved();
    }

    if ($controls->is_action('change')) {
        $controls->data = wp_kses_post_deep($controls->data);
        $this->save_options($controls->data, '', $language);
        $controls->data = $this->get_options('', $language);
        $controls->add_toast_saved();
    }
}

foreach (['unsubscribe_text', 'error_text', 'unsubscribed_text', 'unsubscribed_message', 'reactivated_text'] as $key) {
    if (!empty($controls->data[$key])) {
        $controls->data[$key . '_custom'] = '1';
    }
}

$one_step = false; //$controls->data['mode'] == '1';
?>

<?php if ($controls->data['mode'] == '1') { ?>
    <style>
        .tnp-extended {
            display: none;
        }
    </style>
<?php } ?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/cancellation') ?>
        <h2><?php esc_html_e('Subscribers', 'newsletter') ?></h2>
        <?php include __DIR__ . '/../users/nav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show() ?>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <p>
                <?php //$controls->select('mode', ['1' => 'One-step', '2' => 'Two-step (recommended)'], null, ['onchange' => 'this.form.act.value="change";this.form.submit()']); ?>
                <?php if (current_user_can('administrator')) { ?>
                <a href="<?php echo esc_attr($this->build_action_url('u')); ?>&nk=0-0" target="_blank">Preview online</a>
                <?php } ?>
                <?php if ($one_step) { ?>
                <div class="tnpc-hint">
                    Single step lowers the protection against mail scanner and unwanted unsubscriptions. You're always conformant
                    to the One-Click-Un subscribe standard since the Newsletter plugin implements the
                    <a href="https://www.rfc-editor.org/rfc/rfc8058.txt" target="_blank">RFC 8058</a> and the
                    <a href="https://support.google.com/a/answer/14229414" target="_blank">Google Guidelines</a>.
                </div>
            <?php } ?>
            </p>


            <div class="tnp-tabs">

                <ul>
                    <?php if (!$one_step) { ?>
                        <li><a href="#tabs-cancellation"><?php esc_html_e('Confirm', 'newsletter') ?></a></li>
                    <?php } ?>
                    <li><a href="#tabs-goodbye"><?php esc_html_e('Goodbye', 'newsletter') ?></a></li>
                    <li><a href="#tabs-reactivation"><?php esc_html_e('Resubscribe', 'newsletter') ?></a></li>
                    <li><a href="#tabs-advanced" style="font-style: italic"><?php esc_html_e('Advanced', 'newsletter') ?></a></li>
                        <?php if (NEWSLETTER_DEBUG) { ?>
                        <li><a href="#tabs-debug">Debug</a></li>
                    <?php } ?>
                </ul>

                <?php if (!$one_step) { ?>
                    <div id="tabs-cancellation">
                        <?php $this->language_notice(); ?>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Opt-out message', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->checkbox2('unsubscribe_text_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                                    <div data-bind="options-unsubscribe_text_custom">
                                        <?php $controls->wp_editor('unsubscribe_text', ['editor_height' => 250], ['default' => wp_kses_post($this->get_default_text('unsubscribe_text'))]); ?>
                                    </div>
                                    <div data-bind="!options-unsubscribe_text_custom" class="tnpc-default-text">
                                        <?php echo wp_kses_post($this->get_default_text('unsubscribe_text')) ?>
                                    </div>
                                </td>
                            </tr>

                        </table>
                    </div>
                <?php } else { ?>
                    <?php $controls->hidden('unsubscribe_text_custom') ?>
                    <?php $controls->hidden('unsubscribe_text') ?>
                <?php } ?>

                <div id="tabs-goodbye">

                    <?php $this->language_notice(); ?>

                    <table class="form-table">


                        <tr>
                            <th><?php esc_html_e('Goodbye message', 'newsletter') ?></th>
                            <td>
                                <?php $controls->checkbox2('unsubscribed_text_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                                <div data-bind="options-unsubscribed_text_custom">
                                    <?php $controls->wp_editor('unsubscribed_text', ['editor_height' => 150], ['default' => wp_kses_post($this->get_default_text('unsubscribed_text'))]); ?>
                                </div>
                                <div data-bind="!options-unsubscribed_text_custom" class="tnpc-default-text">
                                    <?php echo wp_kses_post($this->get_default_text('unsubscribed_text')) ?>
                                </div>

                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Goodbye email', 'newsletter') ?></th>
                            <td>
                                <?php if (!$language) { ?>
                                    <?php $controls->disabled('unsubscribed_disabled') ?>
                                <?php } ?>

                                <?php $controls->text('unsubscribed_subject', 70, wp_kses_post($this->get_default_text('unsubscribed_subject'))); ?>
                                <br><br>
                                <?php $controls->checkbox2('unsubscribed_message_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                                <div data-bind="options-unsubscribed_message_custom">
                                    <?php $controls->wp_editor('unsubscribed_message', ['editor_height' => 150], ['default' => wp_kses_post($this->get_default_text('unsubscribed_message'))]); ?>
                                </div>
                                <div data-bind="!options-unsubscribed_message_custom" class="tnpc-default-text">
                                    <?php echo wp_kses_post($this->get_default_text('unsubscribed_message')) ?>
                                </div>

                            </td>
                        </tr>
                    </table>
                </div>

                <div id="tabs-reactivation">
                    <?php $this->language_notice(); ?>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Reactivated message', 'newsletter') ?></th>
                            <td>
                                <?php $controls->checkbox2('reactivated_text_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                                <div data-bind="options-reactivated_text_custom">
                                    <?php $controls->wp_editor('reactivated_text', ['editor_height' => 150], ['default' => wp_kses_post($this->get_default_text('reactivated_text'))]); ?>
                                </div>
                                <div data-bind="!options-reactivated_text_custom" class="tnpc-default-text">
                                    <?php echo wp_kses_post($this->get_default_text('reactivated_text')) ?>
                                </div>


                            </td>
                        </tr>
                    </table>
                </div>

                <div id="tabs-advanced">
                    <?php $this->language_notice(); ?>
                    <?php if (!$language) { ?>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Notifications', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->yesno('notify'); ?>
                                    <?php $controls->text_email('notify_email'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('On error', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->checkbox2('error_text_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                                    <div data-bind="options-error_text_custom">
                                        <?php $controls->wp_editor('error_text', ['editor_height' => 150], ['default' => wp_kses_post($this->get_default_text('error_text'))]); ?>
                                    </div>
                                    <div data-bind="!options-error_text_custom" class="tnpc-default-text">
                                        <?php echo wp_kses_post($this->get_default_text('error_text')) ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <h3>List-Unsubscribe headers</h3>
                        <table class="form-table">
                            <tr>
                                <th>
                                    <?php esc_html_e('Disable unsubscribe headers', 'newsletter') ?>
                                    <?php $controls->field_help('/subscribers-and-management/cancellation/#list-unsubscribe') ?>
                                </th>
                                <td>
                                    <?php $controls->yesno('disable_unsubscribe_headers'); ?>

                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <?php esc_html_e('Cancellation requests via email', 'newsletter') ?>
                                    <?php $controls->field_help('/subscribers-and-management/cancellation/#list-unsubscribe') ?>
                                </th>
                                <td>
                                    <?php $controls->text_email('list_unsubscribe_mailto_header'); ?>
                                    <span class="description">
                                        <i class="fas fa-exclamation-triangle"></i> Please, read carefully the documentation page
                                    </span>
                                </td>
                            </tr>

                        </table>
                    <?php } ?>

                </div>


                <?php if (NEWSLETTER_DEBUG) { ?>
                    <div id="tabs-debug">
                        <pre><?php echo esc_html(wp_json_encode($this->get_db_options('', $language), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                <?php } ?>

            </div>

            <p>
                <?php $controls->button_save() ?>
            </p>
        </form>


    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
