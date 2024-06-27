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

$emails = [];

$email = new stdClass();
$email->id = 6;
$email->status = 'sending';
$email->subject = 'What you should not miss at all';
$email->send_on = time() - WEEK_IN_SECONDS * 1;
$email->waiting = 89;
$email->delay = '1 day(s)';

$emails[] = $email;

$email = new stdClass();
$email->id = 5;
$email->status = 'sent';
$email->subject = 'Do you have the right habits?';
$email->send_on = time() - WEEK_IN_SECONDS * 2;
$email->waiting = 47;
$email->delay = '5 day(s)';

$emails[] = $email;

$email = new stdClass();
$email->id = 4;
$email->status = 'sent';
$email->subject = 'Learn the good and the bad of those exercises';
$email->send_on = time() - WEEK_IN_SECONDS * 3;
$email->waiting = 34;
$email->delay = '7 day(s)';

$emails[] = $email;
?>
<style>
    .widefat {
        min-width: 500px;
    }
</style>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>
    <div id="tnp-heading">
        <?php include __DIR__ . '/autorespondernav.php' ?>
    </div>
    <div id="tnp-body">

        <?php $controls->show(); ?>

        <p>This is only a demonstrative panel.</p>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div id="tabs" class="tnp-tabs">

                <ul>
                    <li><a href="#tabs-email"><?php esc_html_e('By email', 'newsletter') ?></a></li>
                    <li><a href="#tabs-status"><?php esc_html_e('By status', 'newsletter') ?></a></li>
                </ul>

                <div id="tabs-email">
                    <p>Counts are limited to active subscribers who have not abandoned the series (by list change, cancellation, ...).</p>

                    <table class="widefat" style="width: auto">
                        <thead>
                            <tr>
                                <th>Progress</th>
                                <th>Subscribers</th>
                                <th>Subject</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = 0; ?>
                            <?php for ($i = 0; $i < count($emails); $i++) { ?>
                                <?php
                                $email = $emails[$i];
                                $total += $email->waiting;
                                ?>
                                <tr>
                                    <td>Waiting to receive message <?php echo $i + 1 ?></td>

                                    <td>
                                        <?php echo esc_html($email->waiting) ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($email->subject) ?>
                                    </td>
                                    <td>
                                        <?php $controls->button_icon_statistics('') ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td><strong>Total queued</strong></td>
                                <td>
                                    <strong>
                                        <?php echo $total ?>
                                    </strong>
                                </td>
                                <td>
                                    &nbsp;
                                </td>
                                <td>
                                    &nbsp;
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="tabs-status">

                    <p>Overview of subscriber on this message series.</p>
                    <table class="widefat" style="width: auto">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Subscribers</th>
                            </tr>
                        </thead>
                        <tbody>

                            <tr>
                                <td>Completed</td>
                                <td>
                                    17
                                </td>

                            </tr>
                            <tr>
                                <td>Active</td>
                                <td>
                                    347
                                </td>

                            </tr>
                            <tr>
                                <td>Abandoned</td>
                                <td>
                                    19
                                </td>

                            </tr>
                            <tr>
                                <td>
                                    Other<br>
                                    <small>Missing user, errors</small>
                                </td>
                                <td>
                                    4
                                </td>

                            </tr>
                            <tr>
                                <td><strong>Total</strong></td>
                                <td>
                                    <strong>387</strong>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>



            </div>
        </form>

    </div>

</div>
