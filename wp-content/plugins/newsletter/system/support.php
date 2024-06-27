<?php
/* @var $this NewsletterSystemAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;
?>

<style>
<?php include __DIR__ . '/css/system.css' ?>
</style>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">

        <h2><?php esc_html_e('Support', 'newsletter') ?> <?php echo \Newsletter\License::get_badge(); ?></h2>

    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <form method="post" action="">
            <?php $controls->init(); ?>
            <div class="tnp-dashboard">
                <div class="tnp-cards-container">
                    <div class="tnp-card">
                        <div class="tnp-card-title">How to get support</div>

                        <h3><i class="fas fa-book"></i> Documentation</h3>
                        <p>
                            We have <a href="https://www.thenewsletterplugin.com/documentation" target=_blank">extensive documentation</a>
                            about the Newsletter plugin settigs and feature and the free and commercial addons.
                        </p>

                        <h3><i class="fas fa-comment"></i> Forum</h3>
                        <p>We run a <a href="https://www.thenewsletterplugin.com/forums" target=_blank">support forum</a>
                            where you can send your requests for help, new features, ideas and so on.</p>

                    </div>


                    <div class="tnp-card">
                        <div class="tnp-card-title">Premium support (with an active license)</div>
                        <p style="font-weight: bold">
                            With an active license, please install our
                            <a href="https://www.thenewsletterplugin.com/documentation/installation/how-to-install-the-addons-manager/" target=_blank">Addons Manager</a>
                            to send debug data and test emails to our staff.
                        </p>

                        <p>
                            You can open a ticket from your account page to have the fastest support.
                        </p>
                        <p>
                            <?php $controls->btn_link('https://www.thenewsletterplugin.com/account', __('Open a ticket', 'newsletter'), ['target' => '_blank']); ?>
                        </p>

                    </div>


                </div>
            </div>
        </form>
    </div>
    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
