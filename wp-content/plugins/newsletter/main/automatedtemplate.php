<?php
/* @var $this NewsletterAutomated */

defined('ABSPATH') || exit;

$channel = new stdClass();
$channel->id = 1;
$channel->data = [
    'name' => 'Weekly wellness tips',
    'track' => 1,
    'frequency' => 'weekly',
    'day_1' => 1,
];

$email = new stdClass();
$email->id = 1;
$email->status = 'new';
$email->subject = 'Week 3 Welness Tips';
$email->send_on = time() - WEEK_IN_SECONDS * 4;
TNP_Composer::prepare_controls($controls, $email);
?>
<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER ?>
    <div id="tnp-heading">

        <?php include __DIR__ . '/automatednav.php' ?>

    </div>

    <div id="tnp-body" class="tnp-automated-edit">
        <?php $controls->show(); ?>


        <div class="tnp-automated-edit">

            <form method="post" id="tnpc-form" action="" onsubmit="tnpc_save(this); return true;">
                <?php $controls->init(); ?>

                <?php $controls->composer_fields_v2() ?>

            </form>
            <?php $controls->composer_load_v2(true, false, 'automated') ?>

        </div>

    </div>
</div>
