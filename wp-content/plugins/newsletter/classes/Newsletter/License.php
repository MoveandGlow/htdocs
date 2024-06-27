<?php

namespace Newsletter;

defined('ABSPATH') || exit;

class License {

    static function get_data() {
        
    }

    static function update() {
        \Newsletter::instance()->get_license_data(true);
    }

    static function get_badge() {
        $license_data = \Newsletter::instance()->get_license_data(false);
        $badge = '';

        if (is_wp_error($license_data)) {
            $badge = '<span class="tnp-badge-red">License check failed</span>';
        } else {
            if ($license_data !== false) {
                $type = $license_data->type ?? 'personal';
                if ($type === 'personal') $type = '';
                $class = $type === 'reseller' ? 'tnp-badge-blue' : 'tnp-badge-green';
                if ($license_data->expire == 0) {
                    $badge = '<span class="tnp-badge-green">Free license</span>';
                } elseif ($license_data->expire >= time()) {
                    $badge = '<span class="' . $class . '">' . esc_html($type) . ' license expires on ' . esc_html(date('Y-m-d', $license_data->expire))
                            . '</span>';
                } else {
                    $badge = '<span class="tnp-badge-red">' . esc_html($type) . ' license expired on ' . esc_html(date('Y-m-d', $license_data->expire))
                            . '</span>';
                }
            } else {
                $badge = '<span class="tnp-badge-gray">License not set</span>';
            }
        }

        return $badge;
    }

    /**
     * Changing this code does not bypass the server side validation checks and does not enable
     * premium services.
     *
     * @return bool
     */
    static function is_premium() {
        $license_data = \Newsletter::instance()->get_license_data();
        if (empty($license_data)) {
            return false;
        }
        if (is_wp_error($license_data)) {
            return false;
        }

        return $license_data->expire >= time();
    }

    static function is_free() {
        return !self::is_premium();
    }

    static function is_personal() {
        $license_data = \Newsletter::instance()->get_license_data(false);
        if (is_wp_error($license_data) || !$license_data) return true;
        return $license_data->type === 'personal';
    }

    static function is_reseller() {
        $license_data = \Newsletter::instance()->get_license_data(false);
        if (is_wp_error($license_data) || !$license_data) return false;
        return $license_data->type === 'reseller';
    }
}
