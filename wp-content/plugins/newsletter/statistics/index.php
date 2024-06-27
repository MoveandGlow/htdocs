<?php
/* @var $wpdb wpdb */
/* @var $this NewsletterStatisticsAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

wp_enqueue_script('tnp-chart');

// Optimized query with a reduced set of fields
$emails = $wpdb->get_results("select send_on, id, subject, total, status, type from " . NEWSLETTER_EMAILS_TABLE . " where status='sent' and type='message' order by send_on desc limit 10");

$report = new TNP_Statistics();

$overview_labels = array();
$overview_titles = array();
$overview_open_rate = array();
$overview_click_rate = array();

$total_sent = 0;
$open_count_total = 0;
$click_count_total = 0;
foreach ($emails as $email) {
    $data = $this->get_statistics($email);

    $entry = array();

    if (empty($data->total)) {
        continue;
    }

    // Used later for the tabled view
    $email->report = $data;

    $report->total += $data->total;
    $report->open_count += $data->open_count;
    $report->click_count += $data->click_count;

    $overview_labels[] = strftime('%a, %e %b %y', $email->send_on);

    $overview_open_rate[] = $data->open_rate;
    $overview_click_rate[] = $data->click_rate;
    $overview_titles[] = $email->subject;
}

$report->update();

$overview_labels = array_reverse($overview_labels);
$overview_open_rate = array_reverse($overview_open_rate);
$overview_click_rate = array_reverse($overview_click_rate);

if (empty($emails)) {
    $controls->warnings[] = esc_html__('No newsletters have been sent till now', 'newsletter');
}
?>

<style>
<?php include __DIR__ . '/style.css'; ?>
</style>

<script>
    var titles = <?php echo wp_json_encode(array_reverse($overview_titles)) ?>;
</script>

<div class="wrap tnp-statistics tnp-statistics-index" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>
    <div id="tnp-heading">
        <h2><?php esc_html_e('Overall basic statistics (last 20 newsletters)', 'newsletter') ?></h2>
    </div>

    <div id="tnp-body" class="tnp-statistics">

        <?php $controls->show() ?>

        <p>
            Full details, including Automated and Autoresponder newsletter statistics are available with the
            <a href="https://www.thenewsletterplugin.com/reports?utm_source=statistics&utm_campaign=plugin" target="_blank">Reports Addon</a>.
        </p>

        <div class="tnp-cards-container">

            <div class="tnp-card">
                <div class="tnp-card-title">Sent</div>
                <div class="tnp-card-value"><?php echo number_format_i18n($report->total, 0) ?></div>
                <div class="tnp-card-description"></div>
            </div>

            <div class="tnp-card">
                <div class="tnp-card-title">Opens</div>
                <div class="tnp-card-value"><?php echo $report->open_rate; ?>%</div>
                <div class="tnp-card-description"></div>
            </div>

            <div class="tnp-card">
                <div class="tnp-card-title">Clicks</div>
                <div class="tnp-card-value"><?php echo $report->click_rate; ?>%</div>
                <div class="tnp-card-description"></div>
            </div>

        </div>

        <div class="tnp-cards-container">

            <div class="tnp-card">
                <div class="tnp-card-title">Open rate</div>


                <div id="tnp-opens-chart" style="width: 90%">
                    <canvas id="tnp-opens-chart-canvas"></canvas>
                </div>

                <script type="text/javascript">
                    var open_config = {
                        type: 'line',
                        data: {
                            labels: <?php echo wp_json_encode($overview_labels) ?>,
                            datasets: [
                                {
                                    label: "Open",
                                    fill: false,
                                    strokeColor: "#2980b9",
                                    backgroundColor: "#2980b9",
                                    borderColor: "#2980b9",
                                    //pointBorderColor: "#27AE60",
                                    pointBackgroundColor: "#2980b9",
                                    data: <?php echo wp_json_encode($overview_open_rate) ?>
                                }
                            ]
                        },
                        options: {
                            scales: {
                                xAxes: [{type: "category", "id": "x-axis-1", gridLines: {display: true}, ticks: {}}],
                                yAxes: [
                                    {type: "linear", "id": "y-axis-1", gridLines: {display: true}, ticks: {fontColor: "#333"}}
                                ]
                            },
                            responsive: true,
                            tooltips: {
                                callbacks: {
                                    afterTitle: function (data) {
                                        return titles[data[0].index];
                                    },
                                    label: function (tooltipItem, data) {
                                        return data.datasets[0].label + ": " + data.datasets[0].data[tooltipItem.index] + "%";

                                    }
                                }
                            }
                        }
                    };

                    jQuery(document).ready(function ($) {
                        eventsLineChart = new Chart("tnp-opens-chart-canvas", open_config);
                    });
                </script>


            </div>


            <div class="tnp-card">
                <div class="tnp-card-title">Click rate</div>


                <div id="tnp-clicks-chart" style="width: 90%">
                    <canvas id="tnp-clicks-chart-canvas"></canvas>
                </div>

                <script type="text/javascript">
                    var click_config = {
                        type: 'line',
                        data: {
                            labels: <?php echo wp_json_encode($overview_labels) ?>,
                            datasets: [

                                {
                                    label: "Click",
                                    fill: false,
                                    strokeColor: "#2980b9",
                                    backgroundColor: "#2980b9",
                                    borderColor: "#2980b9",
                                    pointBorderColor: "#2980b9",
                                    pointBackgroundColor: "#2980b9",
                                    data: <?php echo wp_json_encode($overview_click_rate) ?>,
                                }
                            ]
                        },
                        options: {
                            scales: {
                                xAxes: [{type: "category", "id": "x-axis-1", gridLines: {display: true}, ticks: {}}],
                                yAxes: [
                                    {type: "linear", "id": "y-axis-1", gridLines: {display: true}, ticks: {fontColor: "#333"}}
                                ]
                            },
                            responsive: true,
                            tooltips: {
                                callbacks: {
                                    afterTitle: function (data) {
                                        return titles[data[0].index];
                                    },
                                    label: function (tooltipItem, data) {
                                        return data.datasets[0].label + ": " + data.datasets[0].data[tooltipItem.index] + "%";
                                    }
                                }
                            }
                        }
                    };

                    jQuery(document).ready(function ($) {
                        eventsLineChart = new Chart("tnp-clicks-chart-canvas", click_config);
                    });
                </script>

            </div>
        </div>

    </div>

</div>
