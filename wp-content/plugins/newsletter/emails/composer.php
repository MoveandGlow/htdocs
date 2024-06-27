<?php
/* @var $this NewsletterEmailsAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

wp_enqueue_style('tnpc-newsletter-style', home_url('/') . '?na=emails-composer-css');

include NEWSLETTER_INCLUDES_DIR . '/codemirror.php';

$email = null;

if ($controls->is_action()) {

    if ($controls->is_action('reset')) {
        $redirect = $this->get_admin_page_url('composer');
        if (isset($_GET['id'])) {
            $redirect = $this->add_qs($redirect, 'id=' . ((int) $_GET['id']));
        }
        $controls->js_redirect($redirect);
    }

    if ($controls->is_action('save_preset')) {
        $logger->info('Saving new preset: ' . $controls->data['subject']);
        // Create new preset email
        $email = new stdClass();
        TNP_Composer::update_email($email, $controls);
        $email->type = NewsletterEmails::PRESET_EMAIL_TYPE;
        $email->editor = NewsletterEmails::EDITOR_COMPOSER;
        $email->subject = $controls->data['subject'];
        $email->message = $controls->data['message'];

        $email = Newsletter::instance()->save_email($email);

        $redirect = $this->get_admin_page_url('composer');
        $controls->js_redirect($redirect);

        return;
    }

    if ($controls->is_action('update_preset')) {
        $logger->info('Updating preset ' . $_POST['preset_id']);
        $email = $this->get_email($_POST['preset_id']);
        TNP_Composer::update_email($email, $controls);

        $email->subject = $controls->data['subject'];

        // We store only the blocks, after the TNP_Composer::update_email(...) call we have the full HTML
        $email->message = $controls->data['message'];

        $email = $this->save_email($email);

        $redirect = $this->get_admin_page_url('composer');
        $controls->js_redirect($redirect);
    }


    if (empty($_GET['id'])) {

        $logger->info('Saving new newsletter from composer');

        // Create a new email
        $email = new stdClass();
        $email->status = 'new';
        $email->track = Newsletter::instance()->get_option('track');
        $email->token = $this->get_token();
        $email->message_text = NewsletterModuleBase::get_email_default_text_part();
        $email->editor = NewsletterEmails::EDITOR_COMPOSER;
        $email->type = 'message';
        $email->send_on = time();
        $email->query = "select * from " . NEWSLETTER_USERS_TABLE . " where status='C'";

        TNP_Composer::update_email($email, $controls);

        $email = $this->save_email($email);
        if ($controls->is_action('preview')) {
            $redirect = '?page=newsletter_emails_edit';
        } else {
            $redirect = '?page=newsletter_emails_composer';
        }

        $controls->js_redirect($redirect . '&id=' . $email->id);
    } else {
        $logger->info('Saving newsletter ' . $_GET['id'] . ' from composer');
        $email = $this->get_email($_GET['id']);
        if ($email->updated != $controls->data['updated']) {
            $controls->errors = 'This newsletter has been modified by someone else. Cannot save.';
            if (!empty($email->options['sender_email'])) {
                $controls->data['sender_email'] = $email->options['sender_email'];
            } else {
                $controls->data['sender_email'] = Newsletter::instance()->get_sender_email();
            }

            if (!empty($email->options['sender_name'])) {
                $controls->data['sender_name'] = $email->options['sender_name'];
            } else {
                $controls->data['sender_name'] = Newsletter::instance()->get_sender_name();
            }
        } else {
            TNP_Composer::update_email($email, $controls);

            if (empty($email->options['text_message_mode'])) {
                $text = TNP_Composer::convert_to_text($email->message);
                if ($text) {
                    $email->message_text = TNP_Composer::convert_to_text($email->message);
                }
            }

            $email->updated = time();
            $email = $this->save_email($email);
            TNP_Composer::prepare_controls($controls, $email);
            if ($controls->is_action('save')) {
                $controls->add_toast_saved();
            }
        }
    }

    if ($controls->is_action('preview')) {
        $controls->js_redirect('?page=newsletter_emails_edit&&id=' . $email->id);
    }

    if ($controls->is_action('test')) {
        $this->send_test_email($this->get_email($email->id), $controls);
    }

    if ($controls->is_action('send-test-to-email-address')) {
        $custom_email = sanitize_email($_POST['test_address_email']);
        if (!empty($custom_email)) {
            try {
                $message = $this->send_test_newsletter_to_email_address($this->get_email($email->id), $custom_email);
                $controls->messages .= $message;
            } catch (Exception $e) {
                $controls->errors = __('Newsletter should be saved before send a test', 'newsletter');
            }
        } else {
            $controls->errors = __('Empty email address', 'newsletter');
        }
    }
} else {

    if (!empty($_GET['id'])) {
        $email = NewsletterAdmin::instance()->get_email((int) $_GET['id']);
    }
    TNP_Composer::prepare_controls($controls, $email);
}
?>

<style>
    .tnp-composer-footer {
        background-color: #0073aa;
        border-radius: 3px !important;
        margin: 15px 0px 10px 0;
        padding: 10px;
        font-size: 15px;
        color: #fff !important;
        line-height: 32px;
    }

    .tnp-composer-footer form {
        display: inline-block;
        /*margin-left: 30px;*/
    }

    #wpfooter {
        display: none;
    }
</style>



<div class="wrap tnp-emails-composer" id="tnp-wrap">

    <div id="tnp-body" style="display: flex; flex-direction: column">

        <?php $controls->show() ?>

        <form method="post" action="" id="tnpc-form" style="margin-top: 1rem">
            <?php $controls->init(); ?>

            <?php $controls->composer_fields_v2(); ?>

            <?php //$controls->button('update_preset', __('Update preset', 'newsletter'), 'tnpc_update_preset(this.form)', 'update-preset-button'); ?>
            <?php //$controls->button('save_preset', __('Save as preset', 'newsletter'), 'tnpc_save_preset(this.form)', 'save-preset-button'); ?>
            <?php $controls->button_confirm_secondary('reset', __('Back to last save', 'newsletter'), 'Are you sure?'); ?>
            <?php $controls->button('save', __('Save', 'newsletter'), 'tnpc_save(this.form); this.form.submit();'); ?>
            <?php $controls->button('preview', __('Next', 'newsletter') . ' &raquo;', 'tnpc_save(this.form); this.form.submit();'); ?>
        </form>

        <div>
            <?php $controls->composer_load_v2(true); ?>
        </div>



    </div>
</div>