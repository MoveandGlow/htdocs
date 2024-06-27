<?php
/* @var $this NewsletterAutomated */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

global $wpdb;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

$channel = new stdClass();
$channel->id = 1;
$channel->data = [
    'name'=> 'Weekly wellness tips',
    'track' => 1,
    'frequency' => 'weekly',
    'day_1' => 1,
];

$controls->data = $channel->data;

?>
<script src="<?php echo plugins_url('newsletter') ?>/vendor/driver/driver.js.iife.js"></script>
<link rel="stylesheet" href="<?php echo plugins_url('newsletter') ?>/vendor/driver/driver.css"/>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER ?>
    <div id="tnp-heading">

        <?php include __DIR__ . '/automatednav.php' ?>

    </div>

    <div id="tnp-body" class="tnp-automated-edit">

        <?php $controls->show(); ?>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div id="tabs">

                <ul>
                    <li><a href="#tabs-configuration">Configuration</a></li>
                    <li><a href="#tabs-planning">Planning</a></li>
                </ul>

                <div id="tabs-configuration">

                    <table class="form-table">
                        <tr valign="top">
                            <th>Enabled?</th>
                            <td>
                                <?php $controls->yesno('enabled'); ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th>Channel name</th>
                            <td>
                                <?php $controls->text('name', 50); ?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>Subject</th>
                            <td>
                                <?php $controls->text('subject', 50); ?>
                                <p class="description">
                                    If empty the first block suggested subject is used. Use <code>{dynamic_subject}</code> to reference the first block suggested
                                    subject. Use <code>{date}</code> tag for the current date
                                    (<a href="https://www.thenewsletterplugin.com/documentation/newsletter-tags" target="_blank">see more options</a>).

                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>Send to list</th>
                            <td>
                                <?php $controls->lists_select('list', 'Everyone'); ?>
                                <p class="description">
                                    The subscriber list this channel is sent to. Subscribers can stop to receive this channel disabling the
                                    list on their profile.
                                </p>
                            </td>
                        </tr>

                            <tr valign="top">
                                <th>Filter subscribers by language</th>
                                <td>
                                    <?php $controls->languages(); ?>
                                    <p class="description">
                                        If no language is selected, no filter is applied. This filter DOES NOT affect the newsletter content.
                                    </p>
                                </td>
                            </tr>


                        <tr valign="top">
                            <th>Track opens and clicks?</th>
                            <td>
                                <?php $controls->yesno('track'); ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th>Sender name</th>
                            <td>
                                <?php $controls->text('sender_name', ['size' => 40]); ?>
                                <span class="description">
                                    Default: <?php echo esc_html(Newsletter::instance()->get_sender_name()) ?>
                                </span>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>Sender email name</th>
                            <td>
                                <?php $controls->text_email('sender_email', ['size' => 40]); ?>
                                <span class="description">
                                    Default: <?php echo esc_html(Newsletter::instance()->get_sender_email()) ?>
                                </span>
                            </td>
                        </tr>
                    </table>

                </div>

                <div id="tabs-planning">
                    <table class="form-table">
                        <tr valign="top">
                            <th>Planning</th>
                            <td>
                                <?php $controls->radio('frequency', 'weekly', 'Weekly on...'); ?><br>
                                Monday&nbsp;<?php $controls->yesno('day_1'); ?>
                                Tuesday&nbsp;<?php $controls->yesno('day_2'); ?>
                                Wednesday&nbsp;<?php $controls->yesno('day_3'); ?>
                                Thursday&nbsp;<?php $controls->yesno('day_4'); ?>
                                Friday&nbsp;<?php $controls->yesno('day_5'); ?>
                                Saturday&nbsp;<?php $controls->yesno('day_6'); ?>
                                Sunday&nbsp;<?php $controls->yesno('day_7'); ?>
                                <br><br>
                                <?php $controls->radio('frequency', 'monthly', 'Monthly on...'); ?><br>

                                <style>
                                    #tnp-monthly-plan {
                                        width: auto!important;
                                    }
                                    #tnp-monthly-plan th, #tnp-monthly-plan td {
                                        padding: 3px;
                                        text-align: center;
                                        width: 80px;
                                    }
                                    #tnp-monthly-plan th {
                                        font-weight: bold;
                                    }
                                </style>
                                <table id="tnp-monthly-plan">
                                    <tr>
                                        <th>Week</th>
                                        <th>Monday</th>
                                        <th>Tuesday</th>
                                        <th>Wednesday</th>
                                        <th>Thursday</th>
                                        <th>Friday</th>
                                        <th>Saturday</th>
                                        <th>Sunday</th>
                                    </tr>
                                    <?php for ($week = 1; $week <= 5; $week++) { ?>

                                        <tr>
                                            <td><?php echo $week; ?></td>
                                            <?php
                                            for ($i = 1; $i <= 7; $i++) {
                                                echo '<td>';
                                                $controls->checkbox_group('monthly_' . $week . '_days', $i);
                                                echo '</td>';
                                            }
                                            ?>
                                        </tr>
                                    <?php } ?>

                                </table>

                            </td>
                        </tr>

                        <tr valign="top">
                            <th>Delivery hour</th>
                            <td>
                                <?php $controls->hours('hour'); ?>
                                <span class="description">
                                    <a href="https://www.thenewsletterplugin.com/documentation/addons/extended-features/automated-extension/#hours">Read more about DST (Daylight Time Saving)</a>
                                </span>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>Second optional delivery hour</th>
                            <td>
                                <?php $controls->enabled('hour2_enabled', ['bind_to' => 'hour2']); ?>

                                <?php $controls->hours('hour2'); ?>
                            </td>
                        </tr>
                    </table>
                </div>

            </div>


        </form>

    </div>
</div>
<script>
//    const driver = window.driver.js.driver;
//
//const driverObj = driver({
//  showProgress: true,
//  steps: [
//    { element: '#tabs', popover: { title: 'Options and planning', description: 'Here you can configure the channel targeting, planning e some advanced options', side: "left", align: 'start' }},
//  ]
//});
//
//driverObj.drive();
    </script>