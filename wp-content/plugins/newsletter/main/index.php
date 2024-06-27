<?php
/* @var $this NewsletterMainAdmin */
/* @var $controls NewsletterControls */
/* @var $wpdb wpdb */

defined('ABSPATH') || exit;

wp_enqueue_script('tnp-chart');

$emails_module = NewsletterEmailsAdmin::instance();
$statistics_module = NewsletterStatisticsAdmin::instance();
$emails = $wpdb->get_results("select * from " . NEWSLETTER_EMAILS_TABLE . " where type='message' or type like 'automated_%' and type<> 'automated_template' order by id desc limit 5");

$row = $wpdb->get_row("select sum(total) as total, sum(sent) as sent from " . NEWSLETTER_EMAILS_TABLE . " where status='sending' and send_on<" . time() . " order by id asc");
$total = $row->total;
$queued = $row->total - $row->sent;
$speed = Newsletter::instance()->get_send_speed();

$total_sent = (int) $wpdb->get_var("select sum(total) from " . NEWSLETTER_EMAILS_TABLE . " where status='sent'");

$subscribers = $wpdb->get_results("select * from " . NEWSLETTER_USERS_TABLE . " order by id desc limit 7");

$subscribers_count = (int) $wpdb->get_var("select count(*) from " . NEWSLETTER_USERS_TABLE . " where status='C'");
$subscribers_count_last_30_days = (int) $wpdb->get_var("select count(*) from " . NEWSLETTER_USERS_TABLE . " where status='C' and created>date_sub(now(), interval 30 day)");

// Confirmed subscribers
$confirmed = ['y' => [], 'x' => []];
$months = $wpdb->get_results("select count(*) as c, concat(year(created), '-', date_format(created, '%m')) as d "
        . "from " . NEWSLETTER_USERS_TABLE . " where status='C' "
        . "group by concat(year(created), '-', date_format(created, '%m')) order by d desc limit 12");

foreach (array_reverse($months) as $month) {
    $confirmed['y'][] = (int) $month->c;
    $confirmed['x'][] = date_i18n("M y", date_create_from_format("Y-m", $month->d)->getTimestamp());
}

// Unconfirmed subscribers
$unconfirmed = ['y' => [], 'x' => []];
$months = $wpdb->get_results("select count(*) as c, concat(year(created), '-', month(created)) as d "
        . "from " . NEWSLETTER_USERS_TABLE . " where status='S' "
        . "group by year(created), month(created) order by year(created) desc, month(created) desc limit 12");

foreach (array_reverse($months) as $month) {
    $unconfirmed['y'][] = (int) $month->c;
    $unconfirmed['x'][] = date_i18n("M y", date_create_from_format("Y-m", $month->d)->getTimestamp());
}

// Setup

$steps = $this->get_option_array('newsletter_main_steps');
$steps['sender'] = 1;

if (class_exists('NewsletterExtensions')) {
    $steps['addons-manager'] = 1;
}

global $wpdb;
$c = $wpdb->get_results("select id from " . NEWSLETTER_EMAILS_TABLE . " where status in ('sending', 'sent') limit 1");
if ($c) {
    $steps['first-newsletter'] = 1;
}

$max_steps = 8;
$completed_steps = count($steps);
$completed = $completed_steps == $max_steps;
?>

<style>
<?php include __DIR__ . '/css/dashboard.css' ?>
<?php include __DIR__ . '/css/setup.css' ?>
</style>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('https://www.thenewsletterplugin.com/plugins/newsletter/newsletter-configuration') ?>

        <h2><?php esc_html_e('Dashboard', 'newsletter'); ?></h2>
        <?php include __DIR__ . '/dashboard-nav.php' ?>

    </div>

    <div id="tnp-body" class="tnp-main-index">

        <div class="tnp-dashboard">

            <?php if (current_user_can('administrator')) { ?>

                <div class="tnp-cards-container">


                    <div class="tnp-card">

                        <div class="tnp-step sender <?php echo!empty($steps['sender']) ? 'ok' : ''; ?>">
                            <div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3>Your sender name and address</h3>
                                <p>
                                    From who your subscribers will see the emails coming from?

                                    <a href="?page=newsletter_main_main">Review</a>
                                </p>
                            </div>
                        </div>

                        <div class="tnp-step forms <?php echo!empty($steps['forms']) ? 'ok' : ''; ?>">
                            <div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3>Subscription: popup and inline forms</h3>
                                <p>
                                    Activate the subscription forms to grow your subscriber list.

                                    <a href="?page=newsletter_subscription_sources">Configure</a>.
                                </p>
                            </div>
                        </div>

                        <div class="tnp-step <?php echo!empty($steps['notification']) ? 'ok' : ''; ?>">
                            <div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3>Be notified when someone subscribes</h3>
                                <p>
                                    Activate the notification when you get a new subscriber.

                                    <a href="?page=newsletter_subscription_options#advanced">Configure</a>.
                                </p>
                            </div>
                        </div>

                        <div class="tnp-step welcome-email <?php echo!empty($steps['welcome-email']) ? 'ok' : ''; ?>">
                            <div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3>Welcome email: give it your style</h3>
                                <p>
                                    Customize the welcome email to reflect your style.
                                    <a href="?page=newsletter_subscription_welcome">Review</a>.
                                </p>
                            </div>
                        </div>



                        <div class="tnp-step addons-manager <?php echo!empty($steps['addons-manager']) ? 'ok' : ''; ?>">
                            <div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3>Get a free license</h3>
                                <p>
                                    And install free addons to get more power.

                                    <a href="?page=newsletter_main_extensions">Get it</a>.
                                </p>
                            </div>
                        </div>
                    </div>


                    <div class="tnp-card">


                        <div class="tnp-step test-email <?php echo !empty($steps['test-email']) ? 'ok' : ''; ?>">
                            <div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3>Test the email delivery</h3>
                                <p>
                                    Check if your blog can deliver emails.

                                    <a href="?page=newsletter_system_delivery">Run a test</a>.
                                </p>
                            </div>
                        </div>

                        <div class="tnp-step company <?php echo!empty($steps['company']) ? 'ok' : ''; ?>">
                            <div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3>Your company info and socials</h3>
                                <p>
                                    Review your company info and socials

                                    <a href="?page=newsletter_main_info">Review</a>.
                                </p>
                            </div>
                        </div>

                        <div class="tnp-step first-newsletter <?php echo!empty($steps['first-newsletter']) ? 'ok' : ''; ?>">
                            <div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3>Create your first newsletter</h3>
                                <p>
                                    Explore the composer and send it.

                                    <a href="?page=newsletter_emails_index">Go create</a>.
                                </p>
                            </div>
                        </div>

                        <div class="tnp-step <?php echo!empty($steps['delivery-speed']) ? 'ok' : ''; ?>">
                            <div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3>Change the delivery speed</h3>
                                <p>
                                    Set how many emails per hour you want to send.

                                    <a href="?page=newsletter_main_main">Review</a>
                                </p>
                            </div>
                        </div>

                        <div class="tnp-step <?php echo!empty($steps['automated']) ? 'ok' : ''; ?>">
                            <div>
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3>Explore the Automated Newsletters</h3>
                                <p>
                                    Everything on autopilot: set the direction and relax

                                    <a href="?page=newsletter_main_automated">Check it out.</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php } ?>




            <div class="tnp-cards-container">
                <div class="tnp-card">
                    <div class="tnp-card-header">
                        <div class="tnp-card-title"><?php esc_html_e('Subscribers', 'newsletter') ?></div>
                        <div class="tnp-card-upper-buttons"><a href="?page=newsletter_users_statistics"><i class="fas fa-chart-bar"></i></a></div>
                    </div>
                    <div class="tnp-card-value"><?php echo esc_html($subscribers_count); ?></div>
                    <div class="tnp-card-description">Confirmed subscribers</div>
                </div>

                <div class="tnp-card">
                    <div class="tnp-card-title"><?php esc_html_e('Last 30 days', 'newsletter') ?></div>
                    <div class="tnp-card-value"><?php echo esc_html($subscribers_count_last_30_days); ?></div>
                    <div class="tnp-card-description">Confirmed subscribers</div>

                </div>


                <div class="tnp-card">
                    <div class="tnp-card-title"><?php esc_html_e('Queued emails', 'newsletter') ?></div>
                    <div class="tnp-card-value"><?php echo esc_html($queued); ?></div>
                    <div class="tnp-card-description">Delivering at <?php echo esc_html($speed); ?> emails per hour.</div>
                </div>

                <div class="tnp-card">
                    <div class="tnp-card-title"><?php esc_html_e('Total sent emails', 'newsletter') ?></div>
                    <div class="tnp-card-value"><?php echo esc_html($total_sent); ?></div>
                    <div class="tnp-card-description"></div>
                </div>


            </div>


            <div class="tnp-cards-container">


                <div class="tnp-card">
                    <div class="tnp-card-header">
                        <div class="tnp-card-title"><?php esc_html_e('Subscribers', 'newsletter') ?></div>
                        <div class="tnp-card-upper-buttons"><a href="?page=newsletter_users_index"><i class="fas fa-folder-open"></i></a></div>
                    </div>
                    <div class="tnp-card-content">
                        <table class="widefat" style="width: 100%">
                            <thead></thead>
                            <tbody>
                                <?php foreach ($subscribers as $s) { ?>
                                    <tr>
                                        <td>
                                            <small><?php echo esc_html($s->name) ?> <?php echo esc_html($s->surname) ?></small>
                                            <?php echo esc_html($s->email) ?>
                                        </td>

                                        <td style="text-align: center">
                                            <?php echo TNP_User::get_status_label($s->status, true) ?>
                                        </td>
                                        <td style="text-align: right">
                                            <?php $controls->button_icon_edit('?page=newsletter_users_edit&id=' . $s->id, ['tertiary' => true]) ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>


                    </div>
                </div>

                <div class="tnp-card">
                    <div class="tnp-card-header">
                        <div class="tnp-card-title"><?php esc_html_e('Newsletters', 'newsletter') ?></div>
                        <div class="tnp-card-upper-buttons"><a href="?page=newsletter_emails_index"><i class="fas fa-folder-open"></i></a></div>
                    </div>
                    <div class="tnp-card-content">

                        <table class="widefat" style="width: 100%">
                            <thead></thead>
                            <tbody>
                                <?php foreach ($emails as $email) { ?>
                                    <tr>
                                        <td>
                                            <small><?php echo esc_html($email->type == 'message' ? 'Regular newsletter' : 'Automated newsletter'); ?></small><br>
                                            <?php echo esc_html($email->subject) ?>
                                        </td>
                                        <td style="text-align: center">
                                            <?php $emails_module->show_email_status_label($email) ?>
                                        </td>
                                        <td style="text-align: center">
                                            <?php $emails_module->show_email_progress_bar($email, array('scheduled' => true)) ?>
                                        </td>
                                        <td style="text-align: right">
                                            <?php
                                            if ($email->status === TNP_Email::STATUS_SENT || $email->status === TNP_Email::STATUS_SENDING) {
                                                $controls->button_icon_statistics($statistics_module->get_statistics_url($email->id), ['tertiary' => true]);
                                            } else {
                                                $controls->button_icon_edit('?page=newsletter_emails_edit&id=' . $email->id, ['tertiary' => true]);
                                            }
                                            ?>

                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                    </div>
                </div>

            </div>

            <div class="tnp-cards-container">

                <div class="tnp-card">
                    <div class="tnp-card-header">
                        <div class="tnp-card-title"><?php esc_html_e('Confirmed subscriptions', 'newsletter') ?></div>
                        <div class="tnp-card-upper-buttons"><a href="?page=newsletter_users_statistics"><?php _e('Full statistics', 'newsletter') ?></a></div>
                    </div>

                    <div class="tnp-card-content">

                        <div class="tnp-canvas">
                            <canvas id="tnp-events-chart-canvas" height="300"></canvas>
                        </div>
                    </div>

                    <script type="text/javascript">
                        var events_data = {
                            labels: <?php echo json_encode($confirmed['x']) ?>,
                            datasets: [
                                {
                                    label: "",
                                    fill: true,
                                    strokeColor: "#3498db",
                                    backgroundColor: "#72b8e6",
                                    borderColor: "#3498db",
                                    data: <?php echo wp_json_encode($confirmed['y']) ?>
                                }
                            ]
                        };

                        jQuery(function ($) {
                            ctxe = $('#tnp-events-chart-canvas').get(0).getContext("2d");
                            eventsLineChart = new Chart(ctxe, {
                                type: 'bar', data: events_data,
                                options: {
                                    maintainAspectRatio: false,
                                    xresponsive: true,
                                    scales: {
                                        xAxes: [{
                                                type: "category",
                                            }],
                                        yAxes: [
                                            {
                                                type: "linear",
                                                ticks: {
                                                    beginAtZero: true
                                                }
                                            },
                                        ]
                                    },
                                }
                            });
                        });
                    </script>
                </div>
            </div>

            <div class="tnp-cards-container">

                <div class="tnp-card">
                    <div class="tnp-card-header">
                        <div class="tnp-card-title"><?php esc_html_e('Unconfirmed subscriptions', 'newsletter') ?></div>
                        <div class="tnp-card-upper-buttons"><a href="?page=newsletter_users_statistics"><?php _e('Full statistics', 'newsletter') ?></a></div>
                    </div>

                    <div class="tnp-card-content">

                        <div class="tnp-canvas">
                            <canvas id="tnp-unconfirmed-chart" height="300"></canvas>
                        </div>
                    </div>

                    <script type="text/javascript">
                        var events_data2 = {
                            labels: <?php echo json_encode($unconfirmed['x']) ?>,
                            datasets: [
                                {
                                    label: "",
                                    fill: true,
                                    backgroundColor: "#f27b36",
                                    data: <?php echo wp_json_encode($unconfirmed['y']) ?>
                                }
                            ]
                        };

                        jQuery(function ($) {
                            ctxe = $('#tnp-unconfirmed-chart').get(0).getContext("2d");
                            eventsLineChart2 = new Chart(ctxe, {
                                type: 'bar', data: events_data2,
                                options: {
                                    maintainAspectRatio: false,
                                    xresponsive: true,
                                    scales: {
                                        xAxes: [{
                                                type: "category",
                                            }],
                                        yAxes: [
                                            {
                                                type: "linear",
                                                ticks: {
                                                    beginAtZero: true
                                                }
                                            },
                                        ]
                                    },
                                }
                            });
                        });
                    </script>
                </div>
            </div>


            <div class="tnp-cards-container">


                <div class="tnp-card">
                    <div class="tnp-card-header">
                        <div class="tnp-card-title"><?php _e('Documentation', 'newsletter') ?></div>
                    </div>
                    <div>
                        <a href="https://www.thenewsletterplugin.com/documentation/installation/" target="_blank">
                            <div class="tnp-card-documentation-index">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><title>saved items</title><g class="nc-icon-wrapper" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  ><path d="M37,4h3a4,4,0,0,1,4,4V40a4,4,0,0,1-4,4H8a4,4,0,0,1-4-4V8A4,4,0,0,1,8,4h3" fill="none"  stroke-miterlimit="10"/> <polygon points="32 24 24 18 16 24 16 4 32 4 32 24" fill="none" stroke-miterlimit="10" data-color="color-2"/></g></svg>
                                Installation
                            </div>
                        </a>
                        <a href="https://www.thenewsletterplugin.com/documentation/subscription/" target="_blank">
                            <div class="tnp-card-documentation-index">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><title>saved items</title><g class="nc-icon-wrapper" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  ><path d="M37,4h3a4,4,0,0,1,4,4V40a4,4,0,0,1-4,4H8a4,4,0,0,1-4-4V8A4,4,0,0,1,8,4h3" fill="none"  stroke-miterlimit="10"/> <polygon points="32 24 24 18 16 24 16 4 32 4 32 24" fill="none" stroke-miterlimit="10" data-color="color-2"/></g></svg>
                                Subscription
                            </div>
                        </a>
                        <a href="https://www.thenewsletterplugin.com/category/tips" target="_blank">
                            <div class="tnp-card-documentation-index">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><title>saved items</title><g class="nc-icon-wrapper" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  ><path d="M37,4h3a4,4,0,0,1,4,4V40a4,4,0,0,1-4,4H8a4,4,0,0,1-4-4V8A4,4,0,0,1,8,4h3" fill="none"  stroke-miterlimit="10"/> <polygon points="32 24 24 18 16 24 16 4 32 4 32 24" fill="none" stroke-miterlimit="10" data-color="color-2"/></g></svg>
                                Tips & Tricks
                            </div>
                        </a>
                        <a href="https://www.thenewsletterplugin.com/documentation/subscribers-and-management/" target="_blank">
                            <div class="tnp-card-documentation-index">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><title>saved items</title><g class="nc-icon-wrapper" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  ><path d="M37,4h3a4,4,0,0,1,4,4V40a4,4,0,0,1-4,4H8a4,4,0,0,1-4-4V8A4,4,0,0,1,8,4h3" fill="none"  stroke-miterlimit="10"/> <polygon points="32 24 24 18 16 24 16 4 32 4 32 24" fill="none" stroke-miterlimit="10" data-color="color-2"/></g></svg>
                                Subscribers and management
                            </div>
                        </a>
                        <a href="https://www.thenewsletterplugin.com/documentation/newsletters/newsletters-module/" target="_blank">
                            <div class="tnp-card-documentation-index">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><title>saved items</title><g class="nc-icon-wrapper" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  ><path d="M37,4h3a4,4,0,0,1,4,4V40a4,4,0,0,1-4,4H8a4,4,0,0,1-4-4V8A4,4,0,0,1,8,4h3" fill="none"  stroke-miterlimit="10"/> <polygon points="32 24 24 18 16 24 16 4 32 4 32 24" fill="none" stroke-miterlimit="10" data-color="color-2"/></g></svg>
                                Creating Newsletters
                            </div>
                        </a>
                        <a href="https://www.thenewsletterplugin.com/documentation/addons/" target="_blank">
                            <div class="tnp-card-documentation-index">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><title>saved items</title><g class="nc-icon-wrapper" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  ><path d="M37,4h3a4,4,0,0,1,4,4V40a4,4,0,0,1-4,4H8a4,4,0,0,1-4-4V8A4,4,0,0,1,8,4h3" fill="none"  stroke-miterlimit="10"/> <polygon points="32 24 24 18 16 24 16 4 32 4 32 24" fill="none" stroke-miterlimit="10" data-color="color-2"/></g></svg>
                                Premium Addons
                            </div>
                        </a>
                        <a href="https://www.thenewsletterplugin.com/documentation/customization/" target="_blank">
                            <div class="tnp-card-documentation-index">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><title>saved items</title><g class="nc-icon-wrapper" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  ><path d="M37,4h3a4,4,0,0,1,4,4V40a4,4,0,0,1-4,4H8a4,4,0,0,1-4-4V8A4,4,0,0,1,8,4h3" fill="none"  stroke-miterlimit="10"/> <polygon points="32 24 24 18 16 24 16 4 32 4 32 24" fill="none" stroke-miterlimit="10" data-color="color-2"/></g></svg>
                                Customization
                            </div>
                        </a>
                        <a href="https://www.thenewsletterplugin.com/documentation/delivery-and-spam/" target="_blank">
                            <div class="tnp-card-documentation-index">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><title>saved items</title><g class="nc-icon-wrapper" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  ><path d="M37,4h3a4,4,0,0,1,4,4V40a4,4,0,0,1-4,4H8a4,4,0,0,1-4-4V8A4,4,0,0,1,8,4h3" fill="none"  stroke-miterlimit="10"/> <polygon points="32 24 24 18 16 24 16 4 32 4 32 24" fill="none" stroke-miterlimit="10" data-color="color-2"/></g></svg>
                                Delivery and spam
                            </div>
                        </a>
                        <a href="https://www.thenewsletterplugin.com/documentation/developers/" target="_blank">
                            <div class="tnp-card-documentation-index">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20"><title>saved items</title><g class="nc-icon-wrapper" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"  ><path d="M37,4h3a4,4,0,0,1,4,4V40a4,4,0,0,1-4,4H8a4,4,0,0,1-4-4V8A4,4,0,0,1,8,4h3" fill="none"  stroke-miterlimit="10"/> <polygon points="32 24 24 18 16 24 16 4 32 4 32 24" fill="none" stroke-miterlimit="10" data-color="color-2"/></g></svg>
                                Developers & Advanced Topics
                            </div>
                        </a>
                    </div>
                </div>
            </div>




        </div>

    </div>
</div>
