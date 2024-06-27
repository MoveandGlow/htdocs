<?php
use Newsletter\Logs;

global $wpdb;



require_once NEWSLETTER_INCLUDES_DIR . '/paginator.php';

$paginator = new TNP_Pagination_Controller($wpdb->prefix . 'newsletter_logs', 'id', ['source' => $source]);
$logs = $paginator->get_items();

$ajax_url = wp_nonce_url(admin_url('admin-ajax.php') . '?action=newsletter-log', 'newsletter-log');

$show_status = $attrs['status'] ?? true;
?>


<?php if (empty($logs)) { ?>
    <p>No logs.</p>
<?php } else { ?>

    <?php $paginator->display_paginator(); ?>
    <table class="widefat">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <?php if ($show_status) { ?>
                <th>Status</th>
                <?php } ?>
                <th>Description</th>
                <th>Data</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($logs as $log) { ?>
                <tr>
                    <td><?php echo esc_html($log->id); ?></td>
                    <td style="width: 5%; white-space: nowrap"><?php echo esc_html($this->print_date($log->created)); ?></td>
                    <?php if ($show_status) { ?>
                    <td><?php echo esc_html($log->status) ?></td>
                    <?php } ?>
                    <td><?php echo esc_html($log->description) ?></td>
                    <td>
                        <?php if (!empty($log->data)) $this->button_icon_view($ajax_url . '&id=' . $log->id) ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } ?>


