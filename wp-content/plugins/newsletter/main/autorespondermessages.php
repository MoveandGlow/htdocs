<?php
defined('ABSPATH') || exit;

$autoresponder = new stdClass();
$autoresponder->id = 1;
$autoresponder->name = 'Welcome email series';
$autoresponder->list = 0;
$autoresponder->status = 1;
$autoresponder->subscribers = 346;
$autoresponder->emails = [1, 2, 3];
$autoresponder->list_name = 'Not linked to a list';

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

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

$debug = false;
?>
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

            <table class="widefat" style="width: 100%">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <?php if ($debug) { ?>
                            <th><code>Email ID</code></th>
                        <?php } ?>
                        <th><?php esc_html_e('Subject', 'newsletter') ?></th>
                        <th>Delay</th>
                        <th><?php esc_html_e('Subscribers waiting', 'newsletter') ?></th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php for ($i = 0; $i < count($emails); $i++) { ?>
                        <?php
                        $email = $emails[$i];
                        ?>
                        <tr>
                            <td><?php echo $i + 1 ?></td>
                            <td><?php echo esc_html($email->subject) ?></td>
                            <td><?php echo esc_html($email->delay) ?></td>
                            <td><?php echo esc_html($email->waiting); ?></td>

                            <td>
                                <?php
                                if ($i > 0) {
                                    $controls->button_confirm('up', '↑', '', $i);
                                } else {
                                    echo '<span style="margin-left: 34px"></span>';
                                }
                                ?>
                                <?php
                                if ($i < ( count($emails) - 1 )) {
                                    $controls->button_confirm('down', '↓', '', $i);
                                }
                                ?>
                            </td>
                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_edit('?page=newsletter_main_autorespondercomposer') ?>
                                <?php $controls->button_icon_statistics('?page=newsletter_main_autorespondermessages') ?>
                            </td>
                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_copy($i); ?>
                                <?php $controls->button_icon_delete($i); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="tnp-buttons"><?php $controls->button('add', 'New email'); ?></div>

        </form>

    </div>
</div>
