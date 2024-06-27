<?php
/* @var $this NewsletterStatisticsAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

wp_enqueue_script('tnp-chart');

$email = $this->get_email($_GET['id']);

if (empty($email)) {
    echo 'Newsletter not found';
    return;
}

$report = $this->get_statistics($email);

if ($email->status == 'new') {
    $controls->warnings[] = __('Draft newsletter, no data available', 'newsletter');
} else if ($email->status == 'sending') {
    $controls->warnings[] = __('Newsletter still sending', 'newsletter');
}

if (empty($email->track)) {
    $controls->warnings[] = __('This newsletter has the tracking disabled. No statistics will be available.', 'newsletter');
}
?>

<style>
<?php include __DIR__ . '/style.css'; ?>
</style>

<div class="wrap tnp-statistics tnp-statistics-view" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <?php include __DIR__ . '/view-heading.php' ?>


    <div id="tnp-body" style="min-width: 500px">

        <?php $controls->show() ?>

        <p>
            The full report for this newsletter can be generated with the
            <a href="https://www.thenewsletterplugin.com/reports" target="_blank">Reports Addon</a>.
        </p>

        <div class="tnp-cards-container">

            <div class="tnp-card">
                <div class="tnp-card-title"><?php esc_html_e('Reach', 'newsletter'); ?></div>
                <div class="tnp-card-value">
                    <span><?php echo $report->total ?></span>
                    <div class="tnp-card-description"><?php esc_html_e('Total people that got your email', 'newsletter'); ?></div>
                </div>
            </div>

            <div class="tnp-card">
                <div class="tnp-card-title"><?php esc_html_e('Opens', 'newsletter'); ?></div>
                <div class="tnp-card-value">
                    <span class="percentage"><?php echo (float) $report->open_rate; ?></span>%
                    <div class="tnp-card-description">
                        <span class="value"><?php echo (int) $report->open_count ?></span>
                        <?php esc_html_e('total people that opened your email', 'newsletter'); ?>
                    </div>
                </div>
            </div>

            <div class="tnp-card">
                <div class="tnp-card-title"><?php esc_html_e('Clicks', 'newsletter'); ?></div>
                <div class="tnp-card-value">
                    <span class="percentage"><?php echo (float) $report->click_rate; ?></span>%
                    <div class="tnp-card-description">
                        <span class="value"><?php echo (int) $report->click_count ?></span>
                        <?php esc_html_e('total people that clicked a link in your email', 'newsletter'); ?>
                    </div>
                </div>
            </div>

            <div class="tnp-card">
                <div class="tnp-card-title"><?php esc_html_e('Reactivity', 'newsletter'); ?></div>
                <div class="tnp-card-value">
                    <span class="tnp-counter-animationx percentage"><?php echo $report->reactivity ?></span>%
                    <div class="tnp-card-description">
                        <span class="value"><?php echo (int) $report->click_count ?></span> <?php esc_html_e('clicks out of', 'newsletter'); ?>
                        <span class="value"><?php echo (int) $report->open_count ?></span> <?php esc_html_e('opens', 'newsletter'); ?>
                    </div>
                </div>
            </div>

        </div>
        <div class="tnp-cards-container">
            <div class="tnp-card">
                <div class="tnp-card-title">Opens/Sent</div>
                <div class="tnp-card-chart">
                    <canvas id="tnp-opens-sent-chart" class="mini-chart"></canvas>
                </div>
            </div>
            <div class="tnp-card">
                <div class="tnp-card-title">Clicks/Opens</div>
                <div class="tnp-card-chart">
                    <canvas id="tnp-clicks-opens-chart" class="mini-chart"></canvas>
                </div>
            </div>
            <div class="tnp-card" style="opacity: 50%">
                <div class="tnp-card-title">Unsubscribed</div>
                <div class="tnp-card-value">
                    <span class="">-</span>
                    <div class="tnp-card-description">
                        Cancellations started from this newsletter (cannot always be tracked)
                    </div>
                </div>
                <div class="tnp-card-icon"><div class="tnp-card-icon-filter-remove"></div></div>
            </div>
            <div class="tnp-card" style="opacity: 50%">
                <div class="tnp-card-title">Errors</div>
                <div class="tnp-card-value">
                    <span class="">-</span>
                    <div class="tnp-card-description">
                        Errors encountered while delivery, usually due to a faulty mailing service.
                    </div>

                </div>
                <div class="tnp-card-icon"><div class="tnp-card-icon-remove"></div></div>
            </div>
        </div>

    </div>
    <?php include NEWSLETTER_ADMIN_FOOTER; ?>
</div>

<script>
    jQuery(document).ready(function ($) {

        var opensSentChartData = {
            labels: [
                "Sent",
                "Opens"
            ],
            datasets: [
                {
                    data: [<?php echo $report->total - $report->open_count; ?>, <?php echo (int)$report->open_count ?>],
                    backgroundColor: [
                        "#49a0e9",
                        "#27AE60",
                    ]
                }]
        };
        var opensSentChartConfig = {
            type: "doughnut",
            data: opensSentChartData,
            options: {
                responsive: true,
                legend: {display: false},
                elements: {
                    arc: {borderWidth: 0}
                }
            }
        };
        new Chart('tnp-opens-sent-chart', opensSentChartConfig);
        var clicksOpensChartData = {
            labels: [
                "Opens",
                "Clicks"
            ],
            datasets: [
                {
                    data: [<?php echo $report->open_count - $report->click_count; ?>, <?php echo $report->click_count ?>],
                    backgroundColor: [
                        "#49a0e9",
                        "#27AE60",
                    ]
                }]
        };
        var clicksOpensChartConfig = {
            type: "doughnut",
            data: clicksOpensChartData,
            options: {
                responsive: true,
                legend: {display: false},
                elements: {
                    arc: {borderWidth: 0}
                }
            }
        };
        new Chart('tnp-clicks-opens-chart', clicksOpensChartConfig);
    });

</script>