<?php
/* @var $this NewsletterUsersAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

$id = (int) $_GET['id'];
$user = $this->get_user($id);

if (!$user) {
    echo 'Subscriber not found.';
    return;
}

?>

<div class="wrap tnp-users tnp-users-edit" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscribers-and-management/') ?>
        <h2><?php echo esc_html($user->email) ?></h2>
        <?php include __DIR__ . '/edit-nav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <form method="post" action="">

            <?php $controls->init(); ?>

            <div id="tabs">

                <ul>
                    <li><a href="#tabs-history"><?php esc_html_e('Logs', 'newsletter') ?></a></li>

                </ul>

                <div id="tabs-history" class="tnp-tab">
                    <?php
                    $logs = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}newsletter_user_logs where user_id=%d order by id desc", $id));
                    ?>
                    <?php if (empty($logs)) { ?>
                        <p>No logs available</p>
                    <?php } else { ?>
                        <p>Only public lists are recorded.</p>
                        <table class="widefat" style="width: auto">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php esc_html_e('Date', 'newsletter'); ?></th>
                                    <th><?php esc_html_e('Source', 'newsletter'); ?></th>
                                    <th>IP</th>
                                    <th><?php esc_html_e('Lists', 'newsletter'); ?></th>
                                </tr>

                            <tbody>
                                <?php foreach ($logs as $log) { ?>
                                    <?php
                                    $data = json_decode($log->data, ARRAY_A);
                                    if (isset($data['new']))
                                        $data = $data['new'];
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($log->id) ?></td>
                                        <td><?php echo $controls->print_date($log->created) ?></td>
                                        <td><?php echo esc_html($log->source) ?></td>
                                        <td><?php echo esc_html($log->ip) ?></td>
                                        <td>
                                            <?php
                                            if (is_array($data)) {
                                                foreach ($data as $key => $value) {
                                                    echo esc_html(str_replace('_', ' ', $key)), ': ', esc_html($value), '<br>';
                                                }
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>

                        </table>
                    <?php } ?>

                </div>

            </div>

        </form>
    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
