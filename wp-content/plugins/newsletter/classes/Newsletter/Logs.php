<?php

namespace Newsletter;

defined('ABSPATH') || exit;

class Logs {

    /**
     *
     * @global wpdb $wpdb
     * @param string $source
     * @param mixed $data
     */
    static function add($source, $description, $status = 0, $data = '') {
        global $wpdb;
        if (!is_scalar($data)) {
            $data = wp_json_encode($data, JSON_PRETTY_PRINT);
        }
        $wpdb->insert($wpdb->prefix . 'newsletter_logs', ['source' => $source, 'description' => $description, 'status' => $status, 'data' => $data, 'created' => time()]);
    }

    /**
     *
     * @param string $source
     * @param \WP_Error $wp_error
     */
    static function add_wp_error($source, $wp_error) {
        global $wpdb;
        $data = $wp_error->get_error_data();
        if (!is_scalar($data)) {
            $data = wp_json_encode($data, JSON_PRETTY_PRINT);
        }
        $wpdb->insert($wpdb->prefix . 'newsletter_logs', ['source' => $source,
            'description' => $wp_error->get_error_code() . ' - ' . $wp_error->get_error_message(), 'status' => 1,
            'data' => $data, 'created' => time()]);
    }

    static function get($id) {
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare("select * from {$wpdb->prefix}newsletter_logs where id=%d limit 1", $id));
        return $log;
    }

    static function get_all($source) {
        global $wpdb;
        $list = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}newsletter_logs where source=%s order by created desc", $source));
        return $list;
    }

    static function clean() {
        global $wpdb;
        $wpdb->get_results($wpdb->prepare("delete from {$wpdb->prefix}newsletter_logs where created < %d", time() - 30 * DAY_IN_SECONDS));
    }
}
