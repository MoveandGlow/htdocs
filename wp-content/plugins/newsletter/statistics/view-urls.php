<?php
/* @var $this NewsletterStatisticsAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;
$email = $this->get_email($_GET['id']);
if (empty($email)) {
    echo 'Newsletter not found';
    return;
}
$urls = [];

$s = new stdClass();
$s->url = 'https://www.example.org/page-1';
$s->number = 130;

$urls[] = $s;

$s = new stdClass();
$s->url = 'https://www.example.org/page-2';
$s->number = 40;

$urls[] = $s;

$s = new stdClass();
$s->url = 'https://www.example.org/page-3';
$s->number = 20;

$urls[] = $s;

$s = new stdClass();
$s->url = 'https://www.example.org/page-4';
$s->number = 1;

$urls[] = $s;

$s = new stdClass();
$s->url = 'https://www.example.org/page-5';
$s->number = 0;

$urls[] = $s;

$total = array_reduce($urls, function ($carry, $item) {
    $carry += $item->number;
    return $carry;
});
?>
<style>
<?php include __DIR__ . '/style.css'; ?>
</style>
<div class="wrap tnp-statistics tnp-statistics-view" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <?php include __DIR__ . '/view-heading.php' ?>

    <div id="tnp-body">
        <p style="font-size: 1.1em;">
            Details by single clicked link for this newsletter are available with the
            <a href="https://www.thenewsletterplugin.com/reports?utm_source=statistics&utm_campaign=plugin" target="_blank">Reports Addon</a>.
            Data below is a sample view.
        </p>

        <table class="widefat" style="opacity: 50%">
            <colgroup>
                <col class="w-80">
                <col class="w-10">
                <col class="w-10">
            </colgroup>
            <thead>
                <tr class="text-left">
                    <th>Clicked URLs</th>
                    <th>Clicks</th>
                    <th>%</th>
                    <th>Who clicked...</th>
                </tr>
            </thead>
            <tbody>

                <?php for ($i = 0; $i < count($urls); $i++) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_attr($urls[$i]->url) ?>" target="_blank">
                                <?php echo esc_html($urls[$i]->url) ?>
                            </a>
                        </td>
                        <td><?php echo $urls[$i]->number ?></td>
                        <td>
                            <?php echo NewsletterModule::percent($urls[$i]->number, $total); ?>
                        </td>
                        <td>
                            <form action="" method="post">
                                <?php $controls->init() ?>
                                <?php $controls->data['url'] = $urls[$i]->url; ?>
                                <?php $controls->hidden('url') ?>
                                <?php $controls->lists_select() ?>
                                <?php $controls->btn('set', 'Add to this list', ['secondary' => true]) ?>
                            </form>
                        </td>
                    </tr>
                <?php endfor; ?>

            </tbody>
        </table>

    </div>
    <?php include NEWSLETTER_ADMIN_FOOTER; ?>
</div>
