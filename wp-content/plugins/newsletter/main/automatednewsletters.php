<?php

defined('ABSPATH') || exit;

$channel = new stdClass();
$channel->id = 1;
$channel->data = [
    'name' => 'Weekly wellness tips',
    'track' => 1,
    'frequency' => 'weekly',
    'day_1' => 1,
];

$emails = [];

$email = new stdClass();
$email->id = 6;
$email->status = 'sending';
$email->subject = 'Week 6 Welness Tips';
$email->send_on = time() - WEEK_IN_SECONDS * 1;

$emails[] = $email;

$email = new stdClass();
$email->id = 5;
$email->status = 'sent';
$email->subject = 'Week 5 Welness Tips';
$email->send_on = time() - WEEK_IN_SECONDS * 2;

$emails[] = $email;

$email = new stdClass();
$email->id = 4;
$email->status = 'sent';
$email->subject = 'Week 4 Welness Tips';
$email->send_on = time() - WEEK_IN_SECONDS * 3;

$emails[] = $email;

$email = new stdClass();
$email->id = 3;
$email->status = 'sent';
$email->subject = 'Week 3 Welness Tips';
$email->send_on = time() - WEEK_IN_SECONDS * 4;

$emails[] = $email;

$email = new stdClass();
$email->id = 2;
$email->status = 'sent';
$email->subject = 'Week 2 Welness Tips';
$email->send_on = time() - WEEK_IN_SECONDS * 5;

$emails[] = $email;

$email = new stdClass();
$email->id = 1;
$email->status = 'sent';
$email->subject = 'Week 1 Welness Tips';
$email->send_on = time() - WEEK_IN_SECONDS * 6;

$emails[] = $email;
?>
<script src="<?php echo plugins_url('newsletter') ?>/vendor/driver/driver.js.iife.js"></script>
<link rel="stylesheet" href="<?php echo plugins_url('newsletter') ?>/vendor/driver/driver.css"/>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER ?>
    <div id="tnp-heading">
       <?php include __DIR__ . '/automatednav.php' ?>
    </div>

    <div id="tnp-body" class="tnp-automated-edit">


        <form method="post" action="">
            <?php $controls->init(); ?>


            <table class="widefat" id="tnp-automated-newsletters">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $count = 0; ?>
                    <?php foreach ($emails as $email) { ?>
                        <?php $count++; ?>
                        <tr>
                            <td><?php echo $email->id; ?></td>
                            <td>
                                <?php echo esc_html($email->subject); ?>
                            </td>
                            <td style="white-space: nowrap">
                                <?php echo NewsletterControls::print_date($email->send_on); ?>
                            </td>
                            <td class="tnp-automated-status">
                                <?php Newsletter::instance()->show_email_status_label($email) ?>
                            </td>

                            <td style="white-space: nowrap" class="tnp-automated-actions">
                                <?php $controls->button_icon_statistics('#') ?>
                                <?php $controls->button_icon_view('#') ?>
                                <?php $controls->button_icon_delete(0); ?>
                                <?php $controls->button_icon('abort', 'fa-stop', 'Block this newsletter', 0, true); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>



        </form>

    </div>

</div>
<script>
    const driver = window.driver.js.driver;

    const driverObj = driver({
        showProgress: true,
        steps: [
            {element: '#tnp-automated-newsletters', popover: {title: 'Newsletter', description: 'The list of all generated newsletters.', side: "left", align: 'start'}},
            {element: '.tnp-automated-status', popover: {title: 'Status', description: 'Check if the newsletter is goingin out or the delivery has been completed.', side: "left", align: 'start'}},
            {element: '.tnp-automated-actions', popover: {title: 'Actions', description: 'Check the statistics, stop the delivery, view online or delete.', side: "left", align: 'start'}},

        ]
    });

    driverObj.drive();
</script>