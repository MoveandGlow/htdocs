<?php
/* @var $this NewsletterEmails */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;

$email = $this->get_email($_GET['id']);

if (!$email) {
    echo 'Invalid email ID.';
    return;
}

if (!$controls->is_action()) {
    NewsletterEmails::instance()->regenerate($email);
    TNP_Composer::prepare_controls($controls, $email);
} else {

    if ($controls->is_action('save')) {

        TNP_Composer::update_email($email, $controls);
        $email = $this->save_email($email);

        TNP_Composer::prepare_controls($controls, $email);
        $controls->add_toast_saved();
    }
}
?>
<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER ?>
    <div id="tnp-heading">

        <h2><?php echo esc_html($email->subject) ?></h2>

    </div>

    <div id="tnp-body">


        <?php $controls->show(); ?>

        <div class="tnp-automated-edit">

            <form method="post" id="tnpc-form" action="" onsubmit="tnpc_save(this); return true;">
                <?php $controls->init(); ?>

                <p>
                    <?php $controls->button_icon_back('admin.php?page=newsletter_emails_presets') ?>
                    <?php $controls->button_save() ?>
                </p>
                <?php $controls->composer_fields_v2() ?>

            </form>
            <?php $controls->composer_load_v2(true, false, 'automated') ?>

        </div>

    </div>
</div>
