<?php

defined('ABSPATH') || exit;

class NewsletterStatisticsAdmin extends NewsletterModuleAdmin {

    static $instance;

    const SENT_NONE = 0;
    const SENT_READ = 1;
    const SENT_CLICK = 2;

    /**
     * @return NewsletterStatisticsAdmin
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new NewsletterStatisticsAdmin();
        }
        return self::$instance;
    }

    function __construct() {
        parent::__construct('statistics');
    }

    function admin_menu() {
        $this->add_admin_page('index', 'Statistics');
        $this->add_admin_page('view', 'Statistics');
        $this->add_admin_page('users', 'Statistics');
        $this->add_admin_page('urls', 'Statistics');

        //$this->add_admin_page('newsletters', 'Statistics');
        $this->add_admin_page('settings', 'Statistics');
        //$this->add_admin_page('view_retarget', 'Statistics');
        $this->add_admin_page('view-urls', 'Statistics');
        $this->add_admin_page('view-users', 'Statistics');
    }

    function get_statistics_url($email_id) {
        $page = apply_filters('newsletter_statistics_view', 'newsletter_statistics_view');
        return 'admin.php?page=' . $page . '&amp;id=' . $email_id;
    }

    function get_index_url() {
        $page = apply_filters('newsletter_statistics_index', 'newsletter_statistics_index');
        return 'admin.php?page=' . $page;
    }

    /**
     * @deprecated
     *
     * @param type $email_id
     * @return type
     */
    function get_total_count($email_id) {
        $report = $this->get_statistics($email_id);
        return $report->total;
    }

    /**
     * @deprecated
     *
     * @param type $email_id
     * @return type
     */
    function get_open_count($email_id) {
        $report = $this->get_statistics($email_id);
        return $report->open_count;
    }

    /**
     * @deprecated
     *
     * @param type $email_id
     * @return type
     */
    function get_error_count($email_id) {
        return 0;
    }

    /**
     * @deprecated
     *
     * @param type $email_id
     * @return type
     */
    function get_click_count($email_id) {
        $report = $this->get_statistics($email_id);
        return $report->click_count;
    }

    /**
     * @deprecated
     *
     * @global wpdb $wpdb
     * @param TNP_Email $email
     */
    function maybe_fix_sent_stats($email) {
        global $wpdb;

        // Very old emails was missing the send_on
        if ($email->send_on == 0) {
            $this->query($wpdb->prepare("update " . NEWSLETTER_EMAILS_TABLE . " set send_on=unix_timestamp(created) where id=%d limit 1", $email->id));
            $email = $this->get_email($email->id);
        }

        if ($email->status == 'sending') {
            return;
        }

        if ($email->type == 'followup') {
            return;
        }

        $count = $wpdb->get_var($wpdb->prepare("select count(*) from " . NEWSLETTER_SENT_TABLE . " where email_id=%d", $email->id));

        if ($count) {
            return;
        }

        if (empty($email->query)) {
            $email->query = "select * from " . NEWSLETTER_USERS_TABLE . " where status='C'";
        }

        $query = $email->query . " and unix_timestamp(created)<" . $email->send_on;

        $query = str_replace('*', 'id, ' . $email->id . ', ' . $email->send_on, $query);
        $this->query("insert ignore into " . NEWSLETTER_SENT_TABLE . " (user_id, email_id, time) " . $query);
    }

    function update_stats($email) {
        global $wpdb;

        $wpdb->query($wpdb->prepare("update " . $wpdb->prefix . "newsletter_sent s1 join " . $wpdb->prefix . "newsletter_stats s2 on s1.user_id=s2.user_id and s1.email_id=s2.email_id and s1.email_id=%d set s1.open=1, s1.ip=s2.ip", $email->id));
        $wpdb->query($wpdb->prepare("update " . $wpdb->prefix . "newsletter_sent s1 join " . $wpdb->prefix . "newsletter_stats s2 on s1.user_id=s2.user_id and s1.email_id=s2.email_id and s2.url<>'' and s1.email_id=%d set s1.open=2, s1.ip=s2.ip", $email->id));
    }

    function reset_stats($email) {
        global $wpdb;
        $email_id = $this->to_int_id($email);
        $this->query("delete from " . $wpdb->prefix . "newsletter_sent where email_id=" . $email_id);
        $this->query("delete from " . $wpdb->prefix . "newsletter_stats where email_id=" . $email_id);
    }

    function add_click($url, $user_id, $email_id, $ip = null) {
        global $wpdb;
        if (is_null($ip)) {
            $ip = $this->get_remote_ip();
        }

        $ip = $this->process_ip($ip);

        $this->insert(NEWSLETTER_STATS_TABLE, array(
            'email_id' => $email_id,
            'user_id' => $user_id,
            'url' => $url,
            'ip' => $ip
                )
        );
    }

    /**
     * Returns an object with statistics values
     *
     * @global wpdb $wpdb
     * @param TNP_Email $email
     * @return TNP_Report
     */
    function get_statistics($email) {
        global $wpdb;

        if (!is_object($email)) {
            $email = $this->get_email($email);
        }

        $report = new TNP_Statistics();

        $report->email_id = $email->id;

        if ($email->status != 'new') {
            $data = $wpdb->get_row($wpdb->prepare("SELECT COUNT(*) as total,
            count(case when status>0 then 1 else null end) as `errors`,
            count(case when open>0 then 1 else null end) as `opens`,
            count(case when open>1 then 1 else null end) as `clicks`
            FROM " . NEWSLETTER_SENT_TABLE . " where email_id=%d", $email->id));

            $report->total = $data->total;
            $report->open_count = $data->opens;
            $report->click_count = $data->clicks;
        }

        $report->update();

        return $report;
    }

    public function get_date_badge($email) {
        if (!$email->send_on) {
            return '';
        } else {
            return '<span>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $email->send_on)) . '</span>';
        }
    }
}

class TNP_Statistics {

    var $email_id;
    var $total = 0;
    var $open_count = 0;
    var $open_rate = 0;
    var $click_count = 0;
    var $click_rate = 0;
    var $reactivity = 0;

    /**
     * Recomputes the rates using the absolute values already set.
     */
    function update() {
        if ($this->total > 0) {
            $this->open_rate = round($this->open_count / $this->total * 100, 2);
            $this->click_rate = round($this->click_count / $this->total * 100, 2);
        } else {
            $this->open_rate = 0;
            $this->click_rate = 0;
        }

        if ($this->open_count > 0) {
            $this->reactivity = round($this->click_count / $this->open_count * 100, 2);
        }
    }
}
