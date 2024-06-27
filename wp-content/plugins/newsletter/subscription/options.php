<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;

if ($controls->is_action()) {
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

        $controls->data['confirmed_message'] = NewsletterModule::clean_url_tags($controls->data['confirmed_message']);
        $controls->data['confirmed_text'] = NewsletterModule::clean_url_tags($controls->data['confirmed_text']);
        $controls->data['confirmation_text'] = NewsletterModule::clean_url_tags($controls->data['confirmation_text']);
        $controls->data['confirmation_message'] = NewsletterModule::clean_url_tags($controls->data['confirmation_message']);

        $controls->data['confirmed_url'] = trim($controls->data['confirmed_url']);
        $controls->data['confirmation_url'] = trim($controls->data['confirmation_url']);

        $this->save_options($controls->data, '', $language);
        NewsletterMainAdmin::instance()->set_completed_step('notificartion');
        $controls->add_toast_saved();
    }

    if ($controls->is_action('test-confirmation')) {

        $users = $this->get_test_users();
        if (count($users) == 0) {
            $controls->errors = 'There are no test subscribers. Read more about test subscribers <a href="https://www.thenewsletterplugin.com/plugins/newsletter/subscribers-module#test" target="_blank">here</a>.';
        } else {
            $addresses = array();
            foreach ($users as &$user) {
                $addresses[] = $user->email;
                $user->language = $language;
                $res = NewsletterSubscription::instance()->send_message('confirmation', $user);
                if (!$res) {
                    $controls->errors = 'The email address ' . $user->email . ' failed.';
                    break;
                }
            }
            $controls->messages .= 'Test emails sent to ' . count($users) . ' test subscribers: ' .
                    implode(', ', $addresses) . '. Read more about test subscribers <a href="https://www.thenewsletterplugin.com/plugins/newsletter/subscribers-module#test" target="_blank">here</a>.';
            $controls->messages .= '<br>If the message is not received, try to change the message text it could trigger some antispam filters.';
        }
    }

    if ($controls->is_action('test-confirmed')) {

        $users = $this->get_test_users();
        if (count($users) == 0) {
            $controls->errors = 'There are no test subscribers. Read more about test subscribers <a href="https://www.thenewsletterplugin.com/plugins/newsletter/subscribers-module#test" target="_blank">here</a>.';
        } else {
            $addresses = array();
            foreach ($users as $user) {
                $addresses[] = $user->email;
                // Force the language to send the message coherently with the current panel view
                $user->language = $language;
                $res = NewsletterSubscription::instance()->send_message('confirmed', $user);
                if (!$res) {
                    $controls->errors = 'The email address ' . $user->email . ' failed.';
                    break;
                }
            }
            $controls->messages .= 'Test emails sent to ' . count($users) . ' test subscribers: ' .
                    implode(', ', $addresses) . '. Read more about test subscribers <a href="https://www.thenewsletterplugin.com/plugins/newsletter/subscribers-module#test" target="_blank">here</a>.';
            $controls->messages .= '<br>If the message is not received, try to change the message text it could trigger some antispam filters.';
        }
    }

} else {
    $controls->data = $this->get_options('', $language);
}

foreach (['confirmed_text', 'confirmed_message', 'confirmation_text', 'confirmation_message', 'subscription_text', 'error_text'] as $key) {
    if (!empty($controls->data[$key])) {
        $controls->data[$key . '_custom'] = '1';
    }
}
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscription') ?>
        <h2><?php _e('Subscription', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <form method="post" action="">
            <?php $controls->init(); ?>
            <?php $controls->hidden('welcome_email_id'); ?>
            <?php $controls->hidden('welcome_email'); ?>
            <?php $controls->hidden('confirmed_message'); ?>
            <?php $controls->hidden('confirmed_subject'); ?>

            <div id="tabs">
                <ul>
                    <li><a href="#tabs-subscription"><?php esc_html_e('Subscription', 'newsletter') ?></a></li>
                    <li><a href="#tabs-4"><?php esc_html_e('Welcome', 'newsletter') ?></a></li>
                    <li><a href="#tabs-3"><?php esc_html_e('Activation', 'newsletter') ?></a></li>
                    <li class="tnp-tabs-advanced"><a href="#tabs-advanced"><?php esc_html_e('Advanced', 'newsletter') ?></a></li>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <li><a href="#tabs-debug">Debug</a></li>
                    <?php } ?>
                </ul>

                <div id="tabs-subscription">
                    <?php $this->language_notice(); ?>
                    <table class="form-table">
                        <tr>
                            <th><?php $controls->field_label(__('Opt In', 'newsletter'), '/subscription/subscription/') ?></th>
                            <td>
                                <?php $controls->select('noconfirmation', array(0 => __('Double Opt In', 'newsletter'), 1 => __('Single Opt In', 'newsletter'))); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Subscription page', 'newsletter') ?></th>
                            <td>

                                <?php $controls->checkbox2('subscription_text_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                                <div data-bind="options-subscription_text_custom">
                                    <?php $controls->wp_editor('subscription_text', ['editor_height' => 150], ['default' => $this->get_default_text('subscription_text')]); ?>
                                    <p class="description">
                                        Remember to add at least the <code>[newsletter_form]</code> shortcode
                                        (<a href="https://www.thenewsletterplugin.com/documentation/subscription/subscription-form-shortcodes/#attributes" target="_blank">see all the options</a>).
                                        Remove the shortcode if you don't want to show the subscription form.
                                    </p>
                                </div>
                                <div data-bind="!options-subscription_text_custom" class="tnpc-default-text">
                                    <?php echo wp_kses_post($this->get_default_text('subscription_text')) ?>
                                </div>


                            </td>
                        </tr>

                    </table>

                    <?php if (!$language) { ?>
                        <table class="form-table">
                            <tr>
                                <th>
                                    <?php esc_html_e('Repeated subscriptions', 'newsletter') ?>
                                </th>
                                <td>
                                    <?php
                                    $controls->select('multiple', ['0' => __('Not allowed', 'newsletter'), '1' => __('Allowed', 'newsletter'),
                                        '2' => __('Allowed force single opt-in', 'newsletter')]);
                                    ?>
                                    <br><br>

                                    <?php $controls->checkbox2('error_text_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                                    <div data-bind="options-error_text_custom">
                                        <?php $controls->wp_editor('error_text', ['editor_height' => 150], ['default' => $this->get_default_text('error_text')]); ?>
                                    </div>
                                    <div data-bind="!options-error_text_custom" class="tnpc-default-text">
                                        <?php echo wp_kses_post($this->get_default_text('error_text')) ?>
                                    </div>

                                    <p class="description">Shown only when "not allowed" is selected<p>
                                </td>
                            </tr>
                        </table>
                    <?php } ?>

                </div>


                <div id="tabs-3">

                    <?php $this->language_notice(); ?>

                    <p><?php esc_html_e('Only for double opt-in mode.', 'newsletter') ?></p>


                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Activation page content', 'newsletter') ?></th>
                            <td>
                                <?php $controls->checkbox2('confirmation_text_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                                <div data-bind="options-confirmation_text_custom">
                                    <?php $controls->wp_editor('confirmation_text', ['editor_height' => 150], ['default' => $this->get_default_text('confirmation_text')]); ?>
                                </div>
                                <div data-bind="!options-confirmation_text_custom" class="tnpc-default-text">
                                    <?php echo wp_kses_post($this->get_default_text('confirmation_text')) ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Alternative activation page', 'newsletter'); ?></th>
                            <td>
                                <?php $controls->text('confirmation_url', 70, 'https://...'); ?>
                            </td>
                        </tr>


                        <!-- CONFIRMATION EMAIL -->
                        <tr>
                            <th><?php esc_html_e('Activation email', 'newsletter') ?></th>
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

                                <br>
                                <?php $controls->btn('test-confirmation', __('Test', 'newsletter'), ['secondary' => true]); ?>
                            </td>
                        </tr>
                    </table>
                </div>


                <div id="tabs-4">

                    <?php $this->language_notice(); ?>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Welcome page content', 'newsletter') ?></th>
                            <td>
                                <?php $controls->checkbox2('confirmed_text_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                                <div data-bind="options-confirmed_text_custom">
                                    <?php $controls->wp_editor('confirmed_text', ['editor_height' => 150], ['default' => $this->get_default_text('confirmed_text')]); ?>
                                </div>
                                <div data-bind="!options-confirmed_text_custom" class="tnpc-default-text">
                                    <?php echo wp_kses_post($this->get_default_text('confirmed_text')) ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Custom welcome page', 'newsletter') ?></th>
                            <td>
                                <?php $controls->page_or_url('confirmed'); ?>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Conversion tracking code', 'newsletter') ?>
                                <?php $controls->help('/subscription#conversion') ?></th>
                            <td>
                                <?php $controls->textarea('confirmed_tracking'); ?>
                            </td>
                        </tr>
                    </table>

                </div>

                <div id="tabs-advanced">

                    <?php $this->language_notice(); ?>

                    <?php if (!$language) { ?>
                        <table class="form-table">
                            <tr>
                                <th><?php $controls->field_label(__('Override Opt In', 'newsletter'), '/subscription/subscription/#advanced') ?></th>
                                <td>
                                    <?php $controls->yesno('optin_override'); ?>
                                </td>
                            </tr>

                            <tr>
                                <th><?php esc_html_e('Notifications', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->yesno('notify'); ?>
                                    <?php $controls->text_email('notify_email'); ?>
                                </td>
                            </tr>
                        </table>
                    <?php } ?>
                </div>

                <?php if (NEWSLETTER_DEBUG) { ?>
                    <div id="tabs-debug">
                        <pre><?php echo esc_html(json_encode($this->get_db_options('', $language), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                <?php } ?>

            </div>

            <p>
                <?php $controls->button_save(); ?>
            </p>

        </form>


    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
