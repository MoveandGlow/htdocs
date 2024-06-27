<?php
/* @var $wpdb wpdb */
/* @var $this NewsletterSystemAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

wp_enqueue_script('tnp-chart');

$newsletter = Newsletter::instance();
$mailer = $newsletter->get_mailer();

if ($controls->is_action('test')) {

    if (!NewsletterModule::is_email($controls->data['test_email'])) {
        $controls->errors = 'The test email address is not set or is not correct.';
    }

    if (empty($controls->errors)) {

        $options = $controls->data;

        if ($controls->data['test_email'] == $newsletter->get_sender_email()) {
            $controls->messages .= '<strong>Warning:</strong> you are using as test email the same address configured as sender in main configuration. Test can fail because of that.<br>';
        }

        $message = NewsletterMailerAddon::get_test_message($controls->data['test_email'], 'Newsletter test email at ' . date(DATE_ISO8601));

        $r = $newsletter->deliver($message);

        if (!is_wp_error($r)) {
            $options['mail'] = 1;

            $controls->messages .= 'A test email has been sent.<br>';
            $controls->messages .= 'If you did not receive the message in your mailbox within 5 minutes '
                    . '(check also the spam folder), please contact your hosting provider.<br>'
                    . '<a href="https://www.thenewsletterplugin.com/documentation/?p=15170" target="_blank"><strong>Read more here</strong></a>.';

            NewsletterMainAdmin::instance()->set_completed_step('test-email');
        } else {
            $options['mail'] = 0;
            $options['mail_error'] = $r->get_error_message();

            $controls->errors .= '<strong>FAILED</strong> (' . esc_html($r->get_error_message()) . ')<br>';

            if (!empty($newsletter->options['return_path'])) {
                $controls->errors .= '- Try to remove the return path on main settings.<br>';
            }

            $controls->errors .= '<a href="https://www.thenewsletterplugin.com/documentation/?p=15170" target="_blank"><strong>' . __('Read more', 'newsletter') . '</strong></a>.';

            $parts = explode('@', $newsletter->get_sender_email());
            $sitename = strtolower($_SERVER['SERVER_NAME']);
            if (substr($sitename, 0, 4) == 'www.') {
                $sitename = substr($sitename, 4);
            }
            if (strtolower($sitename) != strtolower($parts[1])) {
                $controls->errors .= '- Try to set on main setting a sender address with the same domain of your blog: ' . esc_html($sitename) . ' (you are using ' . esc_html($newsletter->get_sender_email()) . ')<br>';
            }
        }
        $this->save_options($options, 'status');
    }
}

// Compute the number of newsletters ongoing and other stats
//$emails = $wpdb->get_results("select * from " . NEWSLETTER_EMAILS_TABLE . " where status='sending' and send_on<" . time() . " order by id asc");
$emails = $this->get_results("select * from " . NEWSLETTER_EMAILS_TABLE . " where status='sending' and send_on<=" . time() . " order by send_on asc");

$total = 0;
$queued = 0;
foreach ($emails as $email) {
    $total += $email->total;
    $queued += $email->total - $email->sent;

    // Type label
    if ($email->type === 'message') {
        $email->type_label = 'Standard';
    } elseif (strpos($email->type, 'automated_') === 0) {
        $email->type_label = 'Automated ' . substr($email->type, 10);
    } elseif (strpos($email->type, 'instant_') === 0) {
        $email->type_label = 'Instant ' . substr($email->type, 8);
    } else {
        $email->type_label = $email->type;
    }
}
$speed = $newsletter->get_send_speed();

// Nedded to have access to non regular paused newsletters in a easy way
$paused_emails = $this->get_results("select * from " . NEWSLETTER_EMAILS_TABLE . " where status='paused' and send_on<=" . time() . " order by send_on asc");
foreach ($paused_emails as $email) {

    // Type label
    if ($email->type === 'message') {
        $email->type_label = 'Standard';
    } elseif (strpos($email->type, 'automated_') === 0) {
        $email->type_label = 'Automated ' . substr($email->type, 10);
    } elseif (strpos($email->type, 'instant_') === 0) {
        $email->type_label = 'Instant ' . substr($email->type, 8);
    } else {
        $email->type_label = $email->type;
    }
}

$options = $this->get_options('status');

$functions = $this->get_hook_functions('phpmailer_init');
$icon = 'fas fa-plug';
if ($mailer instanceof NewsletterDefaultMailer) {
    $mailer_name = 'Wordpress';
    $service_name = 'Hosting Provider';
    $icon = 'fab fa-wordpress';
} else {
    $mailer_name = 'Unknown';
    $service_name = 'Unknown';
    if (is_object($mailer)) {
        if (method_exists($mailer, 'get_description')) {
            $mailer_name = esc_html($mailer->get_description());
            $service_name = esc_html(ucfirst($mailer->get_name()) . ' Service');
        } else {
            $mailer_name = esc_html(get_class($mailer));
            $service_name = $mailer_name;
        }
    }
}

$speed = Newsletter::instance()->get_send_speed();
?>

<style>
<?php include __DIR__ . '/css/system.css' ?>
</style>

<div class="wrap tnp-system tnp-system-delivery" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">

        <h2><?php esc_html_e('System', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav.php' ?>

    </div>

    <div id="tnp-body">

        <?php $controls->show() ?>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div class="tnp-dashboard">
                <div class="tnp-cards-container">
                    <div class="tnp-card">
                        <?php
                        $condition = empty($options['mail']) ? 0 : 1;
                        if (!$condition && empty($options['mail_error']))
                            $condition = 2;
                        ?>
                        <div class="tnp-card-title">Test <?php $this->condition_flag($condition) ?></div>
                        <p>
                            <?php $controls->text_email('test_email', ['required' => true]) ?>
                            <?php $controls->button('test', __('Send')) ?>
                        </p>


                        <?php if (!empty($options['mail_error'])) { ?>
                            <p style="font-weight: bold">Last test failed with error "<?php echo esc_html($options['mail_error']) ?>".</p>
                        <?php } ?>


                        <p>If you didn't receive the test email:</p>
                        <ol>
                            <li>If you're using an third party SMTP plugin, do a test from that plugin configuration panel</li>
                            <li>If you're using a Newsletter Delivery Addon, do a test from that addon configuration panel</li>
                            <li>If previous points do not apply to you, ask for support to your provider reporting the emails from your blog are not delivered</li>
                        </ol>

                        <p><a href="https://www.thenewsletterplugin.com/documentation/email-sending-issues" target="_blank">Read more to solve your issues, if any</a></p>

                    </div>
                    <div class="tnp-card">
                        <div class="tnp-card-title">Parameters</div>
                        <table class="widefat">

                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th></th>
                                    <th>Note</th>
                                </tr>

                            </thead>

                            <tbody>
                                <tr>
                                    <td>Speed</td>
                                    <td class="status">
                                        &nbsp;
                                    </td>
                                    <td>
                                        <strong><?php echo (int) $speed ?></strong> emails per hour<br>
                                        can be set on
                                        <a href="admin.php?page=newsletter_main_main" target="_blank">Settings/General</a>
                                        or on installed delivery addon if available
                                    </td>

                                </tr>
                                <tr>
                                    <td>Delivering</td>
                                    <td class="status">
                                        &nbsp;
                                    </td>
                                    <td>
                                        <?php if (count($emails)) { ?>
                                            Delivering <?php echo count($emails) ?> newsletters to about <?php echo (int) $queued ?> recipients.
                                            At speed of <?php echo (int) $speed ?> emails per hour it will take <?php esc_html(printf('%.1f', $queued / $speed)) ?> hours to finish.

                                        <?php } else { ?>
                                            Nothing delivering right now
                                        <?php } ?>
                                    </td>

                                </tr>
                                <tr>
                                    <td>Mailer</td>
                                    <td>
                                        &nbsp;
                                    </td>
                                    <td>
                                        <?php echo esc_html($mailer->get_description()) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        Engine lock
                                    </td>
                                    <td>
                                        &nbsp;
                                    </td>
                                    <td>
                                        <?php echo esc_html(get_option('newsletter_lock_engine')) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        NEWSLETTER_CRON_INTERVAL
                                    </td>
                                    <td>
                                        &nbsp;
                                    </td>
                                    <td>
                                        <?php echo esc_html(NEWSLETTER_CRON_INTERVAL) ?> seconds
                                    </td>
                                </tr>


                                <tr>
                                    <td>
                                        NEWSLETTER_SEND_DELAY
                                    </td>
                                    <td>
                                        &nbsp;
                                    </td>
                                    <td>
                                        <?php echo esc_html(NEWSLETTER_SEND_DELAY) ?> milliseconds
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="tnp-cards-container">
                    <div class="tnp-card">
                        <div class="tnp-card-header">
                            <div class="tnp-card-title"><?php esc_html_e('Sending', 'newsletter') ?></div>
                            <div class="tnp-card-upper-buttons"><a href="#sending"><i class="fas fa-chart-bar"></i></a></div>
                        </div>
                        <div class="tnp-card-value"><?php echo (int) count($emails); ?></div>
                        <div class="tnp-card-description">Newsletters</div>
                    </div>

                    <div class="tnp-card">
                        <div class="tnp-card-header">
                            <div class="tnp-card-title"><?php esc_html_e('Progress', 'newsletter') ?></div>
                        </div>
                        <div class="tnp-card-value"><?php echo $total - $queued, '/', (int) $queued; ?></div>
                        <div class="tnp-card-description">Emails</div>
                    </div>

                    <div class="tnp-card">
                        <div class="tnp-card-header">
                            <div class="tnp-card-title"><?php esc_html_e('Time to finish', 'newsletter') ?></div>
                        </div>
                        <div class="tnp-card-value"><?php printf('%.1f', $queued / $speed) ?></div>
                        <div class="tnp-card-description">Hours</div>
                    </div>



                </div>



                <div class="tnp-cards-container">

                    <div class="tnp-card">
                        <?php
                        $stats = $this->get_send_stats();
                        $condition = 1;
                        if ($stats) {
                            $condition = $stats->mean > 3 ? 2 : 1;
                        }
                        ?>
                        <div class="tnp-card-title">Statistics <?php $this->condition_flag($condition) ?></div>

                        <?php if (!$stats) { ?>

                            <p>Not enough data available.</p>

                        <?php } else { ?>

                            <?php if ($condition == 2) { ?>
                                <p>
                                    <strong>Sending a single email is taking more than 3 seconds (by mean), too slow.</strong>
                                    <a href="https://www.thenewsletterplugin.com/documentation/installation/status-panel/#email-speed" target="_blank">Read more</a>.
                                </p>
                            <?php } ?>


                            <p>
                                Average time to send an email: <?php echo (float)$stats->mean ?> seconds<br>
                                <?php if ($stats->mean > 0) { ?>
                                    Max speed: <?php echo esc_html(sprintf("%.2f", 1.0 / $stats->mean * 3600)) ?> emails per hour<br>
                                <?php } ?>

                                Max mean time measured: <?php echo (int) $stats->max ?> seconds<br>
                                Min mean time measured: <?php echo (int) $stats->min ?> seconds<br>
                                Total emails in the sample: <?php echo (int) $stats->total_emails ?><br>
                                Total sending time: <?php echo (int) $stats->total_time ?> seconds<br>
                                Runs in the sample: <?php echo (int) $stats->total_runs ?><br>
                                Runs prematurely interrupted: <?php echo (int) $stats->interrupted ?><br>
                            </p>

                            <canvas id="tnp-send-chart" style="width: 100%; height: 200px"></canvas>
                            <canvas id="tnp-send-speed" style="width: 100%; height: 200px"></canvas>
                            <script>
                                jQuery(function () {
                                    var sendChartData = {
                                        labels: <?php echo wp_json_encode(range(1, count($stats->means))) ?>,
                                        datasets: [
                                            {
                                                label: "Seconds to complete a batch",
                                                data: <?php echo wp_json_encode($stats->means) ?>,
                                                borderColor: '#2980b9',
                                                fill: false
                                            }
                                        ]
                                    };
                                    var sendChartConfig = {
                                        type: "line",
                                        data: sendChartData,
                                        options: {
                                            responsive: false,
                                            maintainAspectRatio: false,
                                            scales: {
                                                yAxes: [{
                                                        type: "linear",
                                                        ticks: {
                                                            beginAtZero: true
                                                        }
                                                    }
                                                ]
                                            }
                                        }
                                    };
                                    new Chart('tnp-send-chart', sendChartConfig);


                                    var sendSpeedData = {
                                        labels: <?php echo wp_json_encode(range(1, count($stats->speeds))) ?>,
                                        datasets: [
                                            {
                                                label: "Emails per second",
                                                data: <?php echo wp_json_encode($stats->speeds) ?>,
                                                borderColor: '#2980b9',
                                                fill: false
                                            }
                                        ]
                                    };
                                    var sendSpeedConfig = {
                                        type: "line",
                                        data: sendSpeedData,
                                        options: {
                                            responsive: false,
                                            maintainAspectRatio: false,
                                            scales: {
                                                yAxes: [{
                                                        type: "linear",
                                                        ticks: {
                                                            beginAtZero: true
                                                        }
                                                    }
                                                ]
                                            }
                                        }
                                    };
                                    new Chart('tnp-send-speed', sendSpeedConfig);
                                });
                            </script>

                            <p><?php $controls->button_reset('reset_send_stats') ?></p>
                        <?php } ?>
                    </div>
                </div>


                <div class="tnp-cards-container">

                    <div class="tnp-card">
                        <div class="tnp-card-title">How are messages delivered by Newsletter to your subscribers?</div>
                        <div class="tnp-flow tnp-flow-row">
                            <div class="tnp-mail"><i class="fas fa-envelope"></i><br><br>Newsletter<br>
                                (max: <?php echo esc_html($speed) ?> emails per hour)
                            </div>
                            <div class="tnp-arrow">&rightarrow;</div>
                            <div class="tnp-addon"><i class="<?php echo esc_attr($icon) ?>"></i><br><br><?php echo esc_html($mailer_name) ?></div>
                            <div class="tnp-arrow">&rightarrow;</div>
                            <div class="tnp-service"><i class="fas fa-cog"></i><br><br>
                                <?php echo esc_html($service_name) ?>
                            </div>
                            <div class="tnp-arrow">&rightarrow;</div>
                            <div class="tnp-user"><i class="fas fa-user"></i><br><br>Subscriber</div>
                        </div>
                    </div>
                </div>




                <div class="tnp-cards-container">

                    <div class="tnp-card" id="sending">
                        <div class="tnp-card-title">Sending</div>
                        <?php if (empty($emails)) { ?>
                            <p>None.</p>
                        <?php } else { ?>
                            <p>Newsletters are sent in the shown order.</p>
                            <table class="widefat" style="width: 100%">
                                <thead></thead>
                                <tbody>
                                    <?php foreach ($emails as $email) { ?>
                                        <tr>
                                            <td>
                                                <small><?php echo esc_html($email->type_label); ?></small><br>
                                                <?php echo esc_html($email->subject) ?>
                                            </td>
                                            <td style="width: 20rem">
                                                <?php $controls->echo_date($email->send_on); ?>
                                            </td>
                                            <td style="text-align: center; width: 15rem">
                                                <?php NewsletterEmails::instance()->show_email_progress_bar($email, ['scheduled' => true]) ?>
                                            </td>
                                            <td style="text-align: right; width: 3rem">
                                                <?php
                                                $controls->button_icon_edit('?page=newsletter_emails_edit&id=' . ((int) $email->id), ['tertiary' => true]);
                                                ?>

                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>


                    </div>
                </div>

                <div class="tnp-cards-container">

                    <div class="tnp-card" id="sending">
                        <div class="tnp-card-title">Paused</div>
                        <?php if (empty($paused_emails)) { ?>
                            <p>None.</p>
                        <?php } else { ?>

                            <table class="widefat" style="width: 100%">
                                <thead></thead>
                                <tbody>
                                    <?php foreach ($paused_emails as $email) { ?>
                                        <tr>
                                            <td>
                                                <small><?php echo esc_html($email->type_label); ?></small><br>
                                                <?php echo esc_html($email->subject) ?>
                                            </td>
                                            <td style="width: 20rem">
                                                <?php $controls->echo_date($email->send_on); ?>
                                            </td>
                                            <td style="text-align: center; width: 15rem;">
                                                <?php NewsletterEmails::instance()->show_email_progress_bar($email, ['scheduled' => true]) ?>
                                            </td>
                                            <td style="text-align: right; width: 3rem">
                                                <?php
                                                $controls->button_icon_edit('?page=newsletter_emails_edit&id=' . ((int) $email->id), ['tertiary' => true]);
                                                ?>

                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>


                    </div>
                </div>

                <div class="tnp-cards-container">

                    <div class="tnp-card">
                        <div class="tnp-card-title">Filters applied to WP mailing system</div>
                        <?php if (empty($functions)) { ?>
                            <p>None.</p>
                        <?php } else { ?>

                            <p><?php echo $functions ?></p>
                        <?php } ?>


                    </div>
                </div>

            </div>
        </form>

    </div>
    <?php include NEWSLETTER_ADMIN_FOOTER; ?>
</div>
