<?php
/* @var $this NewsletterEmailsAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;

if ($controls->is_action('copy')) {
    $original = $this->get_email($_POST['btn']);
    $email = [];
    $email['subject'] = $original->subject;
    $email['message'] = $original->message;
    $email['message_text'] = $original->message_text;
    $email['send_on'] = time();
    $email['type'] = 'composer_template';
    $email['editor'] = NewsletterEmails::EDITOR_COMPOSER;
    $email['track'] = $original->track;
    $email['options'] = $original->options;

    $this->save_email($email);
    $controls->messages .= __('Message duplicated.', 'newsletter');
}

if ($controls->is_action('new')) {
    $email = [];
    $email['subject'] = 'New template';
    $email['message'] = '';
    $email['message_text'] = '';
    $email['send_on'] = 0;
    $email['type'] = 'composer_template';
    $email['editor'] = NewsletterEmails::EDITOR_COMPOSER;

    $email = $this->save_email($email);
    $controls->js_redirect('admin.php?page=newsletter_emails_presets-edit&id=' . $email->id);
}

if ($controls->is_action('delete')) {
    $this->delete_email($_POST['btn']);
    $controls->add_message_deleted();
}

if ($controls->is_action('delete_selected')) {
    $r = $this->delete_email($_POST['ids']);
    $controls->messages .= $r . ' message(s) deleted';
}

$emails = $this->get_emails('composer_template');
?>

<div class="wrap tnp-emails tnp-emails-index" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-heading">

        <h2><?php _e('Newsletters', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show() ?>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <?php $controls->button('new', __('New', 'newsletter')) ?>


            <table class="widefat tnp-newsletters-list" style="width: auto">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th><?php _e('Subject', 'newsletter') ?></th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($emails as $email) { ?>
                        <tr>
                            <td>
                                <?php echo $email->id; ?>
                            </td>
                            <td style="min-width: 300px">
                                <?php
                                if ($email->subject)
                                    echo esc_html($email->subject);
                                else
                                    echo "Newsletter #" . $email->id;
                                ?>
                            </td>

                            <td>
                                <?php
                                echo '<a class="button-primary tnpc-button" href="admin.php?page=newsletter_emails_presets-edit&id=' . $email->id . '" title="' . esc_attr__('Edit', 'newsletter') . '">' .
                                '<i class="fas fa-th-large"></i> ' . __('Edit', 'newsletter') . '</a>';
                                ?>
                            </td>


                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_view(home_url('/') . '?na=view&id=' . $email->id) ?>
                                <?php $controls->button_icon_copy($email->id); ?>
                                <?php $controls->button_icon_delete($email->id, ['secondary' => true]); ?>
                                <?php if (NEWSLETTER_DEBUG) { ?>
                                    <?php $controls->btn_link(home_url('/') . '?na=json&id=' . $email->id, '{}') ?>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </form>
    </div>

    <?php include NEWSLETTER_DIR . '/tnp-footer.php'; ?>

</div>
