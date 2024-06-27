<?php
/* @var $this NewsletterUsersAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

$user = $this->get_user((int) $_GET['id']);

?>

<div class="wrap tnp-users tnp-users-edit" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscribers-and-management/') ?>
        <h2><?php echo esc_html($user->email) ?></h2>
        <?php include __DIR__ . '/edit-nav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <form method="post" action="">

            <?php $controls->init(); ?>

            <div id="tabs">

                <ul>
                    <li><a href="#tabs-newsletters"><?php esc_html_e('Newsletters', 'newsletter') ?></a></li>
                </ul>


                <div id="tabs-newsletters" class="tnp-tab">
                    <?php if (!has_action('newsletter_user_newsletters_tab') && !has_action('newsletter_users_edit_newsletters')) { ?>
                        <p>
                            This panel requires the <a href="https://www.thenewsletterplugin.com/plugins/newsletter/reports-module" target="_blank">Reports Addon</a>.
                        </p>
                        <?php
                    } else {
                        do_action('newsletter_user_newsletters_tab', $user->id);
                        do_action('newsletter_users_edit_newsletters', $user->id);
                    }
                    ?>
                </div>

            </div>


        </form>
    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
