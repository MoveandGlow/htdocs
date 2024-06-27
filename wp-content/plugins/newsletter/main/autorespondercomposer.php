<?php

defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

$autoresponder = new stdClass();
$autoresponder->id = 1;
$autoresponder->name = 'Welcome email series';
$autoresponder->list = 0;
$autoresponder->status = 1;
$autoresponder->subscribers = 346;
$autoresponder->emails = [1, 2, 3];
$autoresponder->list_name = 'Not linked to a list';

$email = new stdClass();
$email->id = 1;
$email->subject = 'What you should not miss at all';
$email->options = ['delay' => 24];

TNP_Composer::prepare_controls($controls, $email);
?>
<div class="wrap" id="tnp-wrap">


    <?php $controls->show(); ?>


    <div id="tnp-body">

        <form id="tnpc-form" method="post" action="" onsubmit="tnpc_save(this); return true;">
            <?php $controls->init(); ?>
            <p>
                <?php $controls->button_back('?page=newsletter_main_autorespondermessages', '') ?>
            </p>

            <table class="form-table" style="width: auto; margin-bottom: 20px">
                <tr>
                    <th>Delay (hours)</th>
                    <td><?php $controls->text('options_delay') ?></td>
                </tr>
            </table>

            <?php $controls->composer_fields_v2() ?>

        </form>

        <?php $controls->composer_load_v2(true, false, 'autoresponder') ?>
    </div>

</div>
