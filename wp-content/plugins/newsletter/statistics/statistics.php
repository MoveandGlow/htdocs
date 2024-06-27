<?php

defined('ABSPATH') || exit;

/**
 * Manages the clicks and opens tracking.
 */
class NewsletterStatistics extends NewsletterModule {

    static $instance;

    const SENT_NONE = 0;
    const SENT_READ = 1;
    const SENT_CLICK = 2;

    var $relink_email_id;
    var $relink_user_id;
    var $relink_email_token;
    var $relink_key = '';

    /**
     * @return NewsletterStatistics
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        parent::__construct('statistics');
        add_action('wp_loaded', [$this, 'hook_wp_loaded']);
    }

    function hook_wp_loaded() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            add_action('wp_ajax_tnptr', [$this, 'tracking']);
            add_action('wp_ajax_nopriv_tnptr', [$this, 'tracking']);
            return;
        }

        $this->tracking();
    }

    function tracking() {
        if (isset($_GET['nltr'])) {

            // Patch for links with ;
            $parts = explode(';', base64_decode($_GET['nltr']));
            $email_id = (int) array_shift($parts);
            $user_id = (int) array_shift($parts);
            $signature = array_pop($parts);
            $anchor = array_pop($parts); // No more used
            // The remaining elements are the url splitted when it contains ";"
            $url = implode(';', $parts);

            if (empty($url)) {
                $this->dienow('Invalid link', 'The tracking link contains invalid data (missing subscriber or original URL)', 404);
            }

            $host = parse_url($url, PHP_URL_HOST);
            $blog_host = parse_url(home_url(), PHP_URL_HOST);

            $verified = $signature == md5($email_id . ';' . $user_id . ';' . $url . ';' . $anchor . $this->get_main_option('key'));

            // For matching hosts the redirect is safe even without the signature
            if ($host !== $blog_host) {
                // Protection against open-redirect
                if (!$verified) {
                    $this->dienow('Invalid link', 'The link signature (which grants a valid redirection and protects from open redirect attacks) is not valid.', 404);
                }
            }

            // Test emails, anyway the link was signed
            if (empty($email_id) || empty($user_id)) {
                header('Location: ' . esc_url_raw($url));
                die();
            }

            if ($user_id) {
                $user = $this->get_user($user_id);
                if (!$user) {
                    $this->dienow(__('Subscriber not found', 'newsletter'), 'This tracking link contains a reference to a subscriber no more present', 404);
                } else {
                    if ($verified) {
                        $this->set_user_cookie($user);
                    }
                }
            }

            $email = $this->get_email($email_id);
            if (!$email) {
                $this->dienow('Invalid newsletter', 'The link originates from a newsletter not found (it could have been deleted)', 404);
            }
            setcookie('tnpe', $email->id . '-' . $email->token, time() + 60 * 60 * 24 * 365, '/');

            $is_action = strpos($url, '?na=');

            $ip = $this->get_remote_ip();
            $ip = $this->process_ip($ip);

            if ($verified) {
                if (!$is_action) {
                    $url = apply_filters('newsletter_pre_save_url', $url, $email, $user);
                    $this->add_click($url, $user_id, $email_id, $ip);
                    $this->update_open_value(self::SENT_CLICK, $user_id, $email_id, $ip);
                } else {
                    // Track a Newsletter action as an email read and not a click
                    $this->update_open_value(self::SENT_READ, $user_id, $email_id, $ip);
                }
                $this->update_user_ip($user, $ip);
                $this->update_user_last_activity($user);
            }

            $this->reset_stats_time($email_id);

            header('Location: ' . apply_filters('newsletter_redirect_url', $url, $email, $user));
            die();
        }


        if (isset($_GET['noti'])) {

            $this->logger->debug('Open tracking: ' . $_GET['noti']);

            list($email_id, $user_id, $signature) = explode(';', base64_decode($_GET['noti']), 3);

            $email = $this->get_email($email_id);
            if (!$email) {
                $this->logger->error('Open tracking request for unexistant email');
                die();
            }

            $user = $this->get_user($user_id);
            if (!$user) {
                $this->logger->error('Open tracking request for unexistant subscriber');
                die();
            }

            if ($email->token) {
                //$this->logger->debug('Signature: ' . $signature);
                $s = md5($email_id . $user_id . $email->token);
                if ($s != $signature) {
                    $this->logger->error('Open tracking request with wrong signature. Email token: ' . $email->token);
                    die();
                }
            } else {
                $this->logger->info('Email with no token hence not signature to check');
            }

            $ip = $this->get_remote_ip();
            $ip = $this->process_ip($ip);

            $this->add_click('', $user_id, $email_id, $ip);
            $this->update_open_value(self::SENT_READ, $user_id, $email_id, $ip);
            $this->reset_stats_time($email_id);

            $this->update_user_last_activity($user);

            header('Content-Type: image/gif', true);
            echo base64_decode('_R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            die();
        }
    }

    /**
     * Reset the timestamp which indicates the specific email stats must be recalculated.
     *
     * @global wpdb $wpdb
     * @param int $email_id
     */
    function reset_stats_time($email_id) {
        global $wpdb;
        if (!$email_id) {
            return;
        }
        $wpdb->update(NEWSLETTER_EMAILS_TABLE, ['stats_time' => 0], ['id' => $email_id]);
    }

    function relink($text, $email_id, $user_id, $email_token = '') {
        $this->relink_email_id = $email_id;
        $this->relink_user_id = $user_id;
        $this->relink_email_token = $email_token;
        if (empty($this->relink_key)) {
            if (defined('NEWSLETTER_RELINK_KEY')) {
                $this->relink_key = NEWSLETTER_RELINK_KEY;
            } else {
                $this->relink_key = $this->get_main_option('key');
            }
        }
        $text = preg_replace_callback('/(<[aA][^>]+href[\s]*=[\s]*["\'])([^>"\']+)(["\'][^>]*>)(.*?)(<\/[Aa]>)/is', array($this, 'relink_callback'), $text);

        $signature = md5($email_id . $user_id . $email_token);

        switch (NEWSLETTER_TRACKING_TYPE) {
            case 'ajax':
                $url = admin_url('admin-ajax.php?action=tnptr&noti=') . urlencode(base64_encode($email_id . ';' . $user_id . ';' . $signature));
                break;
            default:
                $url = home_url('/') . '?noti=' . urlencode(base64_encode($email_id . ';' . $user_id . ';' . $signature));
        }

        $text = str_replace('</body>', '<img width="1" height="1" alt="" src="' . $url . '"/></body>', $text);
        return $text;
    }

    function relink_callback($matches) {
        $href = trim(str_replace('&amp;', '&', $matches[2]));

        //$this->logger->debug('Relink ' . $href);
        // Do not replace URL which are tags (special case for ElasticEmail)
        if (strpos($href, '{') === 0) {
            return $matches[0];
        }

        // Do not relink anchors
        if (substr($href, 0, 1) == '#') {
            return $matches[0];
        }
        // Do not relink mailto:
        if (substr($href, 0, 7) == 'mailto:') {
            return $matches[0];
        }

        // This is the link text which is added to the tracking data
        $anchor = '';
        $r = $this->relink_email_id . ';' . $this->relink_user_id . ';' . $href . ';' . $anchor;
        $r = $r . ';' . md5($r . $this->relink_key);
        $r = base64_encode($r);
        $r = urlencode($r);
        switch (NEWSLETTER_TRACKING_TYPE) {
            case 'ajax':
                $url = admin_url('admin-ajax.php?action=tnptr&nltr=') . $r;
                break;
            default:
                $url = home_url('/') . '?nltr=' . $r;
        }

        return $matches[1] . $url . $matches[3] . $matches[4] . $matches[5];
    }

    function update_stats($email) {
        global $wpdb;

        $wpdb->query($wpdb->prepare("update " . NEWSLETTER_SENT_TABLE . " s1 join " . $wpdb->prefix . "newsletter_stats s2 on s1.user_id=s2.user_id and s1.email_id=s2.email_id and s1.email_id=%d set s1.open=1, s1.ip=s2.ip", $email->id));
        $wpdb->query($wpdb->prepare("update " . NEWSLETTER_SENT_TABLE . " s1 join " . $wpdb->prefix . "newsletter_stats s2 on s1.user_id=s2.user_id and s1.email_id=s2.email_id and s2.url<>'' and s1.email_id=%d set s1.open=2, s1.ip=s2.ip", $email->id));
    }

    function reset_stats($email) {
        global $wpdb;
        $email_id = $this->to_int_id($email);
        $this->query("delete from " . NEWSLETTER_SENT_TABLE . " where email_id=" . $email_id);
        $this->query("delete from " . NEWSLETTER_STATS_TABLE . " where email_id=" . $email_id);
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

    function update_open_value($value, $user_id, $email_id, $ip = null) {
        global $wpdb;
        if (is_null($ip)) {
            $ip = $this->get_remote_ip();
        }
        $ip = $this->process_ip($ip);
        $this->query($wpdb->prepare("update " . NEWSLETTER_SENT_TABLE . " set open=%d, ip=%s where email_id=%d and user_id=%d and open<%d limit 1", $value, $ip, $email_id, $user_id, $value));
    }

    /** For compatibility */
    function get_statistics_url($email_id) {
        $page = apply_filters('newsletter_statistics_view', 'newsletter_statistics_view');
        return 'admin.php?page=' . $page . '&amp;id=' . $email_id;
    }

    /** For compatibility */
    function get_index_url() {
        $page = apply_filters('newsletter_statistics_index', 'newsletter_statistics_index');
        return 'admin.php?page=' . $page;
    }

    /**
     * Used by Automated.
     *
     * @deprecated
     *
     * @param type $email_id
     * @return type
     */
    function get_total_count($email_id) {
        $report = $this->get_statistics($email_id);
        return $report->total;
    }

    function get_statistics($email) {
        return NewsletterStatisticsAdmin::instance()->get_statistics($email);
    }

    /**
     * Used by Automated.
     *
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
     * Used by Automated.
     *
     * @deprecated
     *
     * @param type $email_id
     * @return type
     */
    function get_click_count($email_id) {
        $report = $this->get_statistics($email_id);
        return $report->click_count;
    }
}

NewsletterStatistics::instance();

