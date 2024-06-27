<?php
/* @var $this NewsletterUsersAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

$user = $this->get_user((int) $_GET['id']);

if (!$user) {
    echo 'Subscriber not found.';
    return;
}

do_action('newsletter_users_edit_autoresponders_init', $user, $controls);

?>

<div class="wrap tnp-users tnp-users-edit" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscribers-and-management/') ?>
        <h2><?php echo esc_html($user->email) ?></h2>
        <?php include __DIR__ . '/edit-nav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <?php if (!class_exists('NewsletterAutoresponder')) { ?>

            <div class="tnp-notice">
                This panel requires the Autoresponder Addon.
            </div>

        <?php } else { ?>


            <form method="post" action="">

                <?php $controls->init(); ?>

                <?php do_action('newsletter_users_edit_autoresponders', $user, $controls); ?>

            </form>
        <?php } ?>
    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
