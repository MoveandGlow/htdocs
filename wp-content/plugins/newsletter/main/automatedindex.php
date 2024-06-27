<?php
/* @var $this NewsletterAutomated */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

$feeds = [];
$feed = new stdClass();
$feed->id = 1;
$feed->last_time = time() - DAY_IN_SECONDS;
$feed->sent = 12;
$feed->data = [
    'name' => 'Weekly wellness tips',
    'enabled' => 1];
$feed->email = new stdClass();
$feed->email->status = 'sending';

$feeds[] = $feed;
$feed = new stdClass();
$feed->id = 2;
$feed->last_time = time() - MONTH_IN_SECONDS;
$feed->sent = 4;
$feed->email = new stdClass();
$feed->email->status = 'sent';
$feed->data = [
    'name' => 'Last month activities summary',
    'enabled' => 1];
$feeds[] = $feed;
$feed = new stdClass();
$feed->id = 3;
$feed->last_time = time() - WEEK_IN_SECONDS;
$feed->email = new stdClass();
$feed->email->status = 'sent';
$feed->sent = 57;
$feed->data = [
    'name' => 'Next seven days meeting locations',
    'enabled' => 1];

$feeds[] = $feed;

NewsletterMainAdmin::instance()->set_completed_step('automated');
?>
<script src="<?php echo plugins_url('newsletter') ?>/vendor/driver/driver.js.iife.js"></script>
<link rel="stylesheet" href="<?php echo plugins_url('newsletter') ?>/vendor/driver/driver.css"/>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER ?>
    <div id="tnp-heading">
        <?php $controls->title_help('/addons/extended-features/automated-extension/') ?>
        <h2>Automated Newsletters</h2>
    </div>
    <div id="tnp-body">
        <?php $controls->show(); ?>
        <p>This is only a demonstrative panel.</p>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div class="tnp-buttons">
                <?php $controls->button('add', 'New channel') ?>
            </div>

            <table class="widefat" id="tnp-channels">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Name</th>
                        <th><!--Status--></th>
                        <th colspan="2">Last newsletter</th>

                        <th>Sent</th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($feeds as $feed) { ?>
                        <tr>
                            <td>
                                <?php echo $feed->id ?>

                            </td>
                            <td><?php echo esc_html($feed->data['name']) ?></td>
                            <td class="tnp-automated-status">
                                <span class="tnp-led-<?php echo!empty($feed->data['enabled']) ? 'green' : 'gray' ?>">&#x2B24;</span>
                            </td>

                            <td style="white-space: nowrap">
                                <?php echo date_i18n(get_option('date_format'), $feed->last_time); ?>


                            </td>

                            <td>
                                <?php if ($feed->email) { ?>

                                    <?php Newsletter::instance()->show_email_status_label($feed->email) ?>
                                <?php } ?>
                            </td>
                            <td class="tnp-sent"><?php echo $feed->sent ?></td>

                            <td style="white-space: nowrap" class="tnp-automated-actions">

                                <?php $controls->button_icon_configure('?page=newsletter_main_automatededit') ?>
                                <?php $controls->button_icon_newsletters('?page=newsletter_main_automatednewsletters') ?>
                                <?php $controls->button_icon_design('?page=newsletter_main_automatedtemplate') ?>
                            </td>

                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_copy(); ?>
                                <?php $controls->button_icon_delete(); ?>
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
    { element: '#tnp-channels', popover: { title: 'Channels', description: 'Every channel generates automatically newsletters with your site contents.', side: "left", align: 'start' }},
    { element: '.tnp-buttons', popover: { title: 'Create', description: 'Create as many channel as you need with different templates and planning.', side: "left", align: 'start' }},
    { element: '.tnp-automated-actions', popover: { title: 'Actions', description: 'Configure, change the template, check the generated newsletters of a channel.', side: "left", align: 'start' }},
      { popover: { title: 'More...', description: 'Click on action buttons to see more.' } }
  ]
});

driverObj.drive();
    </script>