<?php

class NewsletterAntispam {

    var $options;
    var $logger;

    public static function instance() {
        static $instance;
        if (!$instance) {
            $instance = new NewsletterAntispam();
        }
        return $instance;
    }

    public function __construct() {
        $this->options = Newsletter::instance()->get_options('antispam');
        $this->logger = new NewsletterLogger('antispam');
    }

    /**
     * $email must be cleaned using the is_email() function.
     *
     * @param TNP_Subscription $subscription
     */
    function is_spam($subscription, $return_wp_error = false) {

        $email = $subscription->data->email;
        $ip = $subscription->data->ip;

        $full_name = $subscription->data->name . ' ' . $subscription->data->surname;
        if ($this->is_spam_text($full_name)) {
            $this->logger->fatal($email . ' - ' . $ip . ' - Name with http: ' . $full_name);
            if ($return_wp_error)
                return new WP_Error('spam-text', 'Spam text detected on name: ' . $full_name);
            else
                return true;
        }

//        if ($this->is_ip_blacklisted($ip)) {
//            $this->logger->fatal($email . ' - ' . $ip . ' - IP blacklisted');
//            if ($return_wp_error)
//                return new WP_Error('ip-blacklist', 'The IP is blacklisted: ' . $ip);
//            else
//                return true;
//        }

        if ($this->is_address_blacklisted($email)) {
            $this->logger->fatal($email . ' - ' . $ip . ' - Address blacklisted');
            if ($return_wp_error)
                return new WP_Error('email-blacklist', 'The email is blacklisted: ' . $email);
            else
                return true;
        }

        // Keep the values as-is for spam check

        // phpcs:ignore:WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $user_agent = wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '');
        // phpcs:ignore:WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $referrer = wp_unslash($_SERVER['HTTP_REFERER'] ?? '');
        if ($this->is_spam_by_akismet($email, $full_name, $ip, $user_agent, $referrer)) {
            $this->logger->fatal($email . ' - ' . $ip . ' - Akismet blocked');

            if ($return_wp_error)
                return new WP_Error('akismet', 'Spam detected by Akismet');
            else
                return true;
        }

        // Flood check
        if ($this->is_flood($email, $ip)) {
            $this->logger->fatal($email . ' - ' . $ip . ' - Antiflood triggered');
            if ($return_wp_error)
                return new WP_Error('flood', 'Flood detected for: ' . $ip . ' or ' . $email);
            else
                return true;
        }

//        if ($this->is_missing_domain_mx($email)) {
//            $this->logger->fatal($email . ' - ' . $ip . ' - MX check failed');
//            header("HTTP/1.0 404 Not Found");
//            return true;
//        }

        return false;
    }

    function is_address_blacklisted($email) {

        if (empty($this->options['address_blacklist'])) {
            return false;
        }

        $rev_email = strrev($email);
        foreach ($this->options['address_blacklist'] as $item) {
            if (strpos($rev_email, strrev($item)) === 0) {
                return true;
            }
        }
        return false;
    }

    function is_ip_blacklisted($ip) {

        // Not enough reliable, removed.
        return false;

        if ('::1' === $ip || '127.0.0.1' === $ip) {
            return false;
        }

        if (empty($this->options['ip_blacklist'])) {
            return false;
        }
        foreach ($this->options['ip_blacklist'] as $item) {
            if ('#' === substr($item, 0, 1)) {
                continue;
            }
            if ($this->ip_match($ip, $item)) {
                return true;
            }
        }
        return false;
    }

    function is_missing_domain_mx($email) {

        if (empty($this->options['domain_check'])) {
            return false;
        }

        list($local, $domain) = explode('@', $email);

        $hosts = array();
        if (!getmxrr($domain, $hosts)) {
            return true;
        }
        return false;
    }

    function is_flood($email, $ip) {
        global $wpdb;

        if (empty($this->options['antiflood'])) {
            return false;
        }

        $updated = $wpdb->get_var($wpdb->prepare("select updated from " . NEWSLETTER_USERS_TABLE . " where ip=%s or email=%s order by updated desc limit 1", $ip, $email));

        if ($updated && time() - $updated < $this->options['antiflood']) {
            return true;
        }

        return false;
    }

    function is_spam_text($text) {
        if (stripos($text, 'http:') !== false || stripos($text, 'https:') !== false) {
            return true;
        }
        if (stripos($text, 'www.') !== false) {
            return true;
        }
        if (preg_match('|[^\s\.]+\.[^\s\.]+\.[^\s\.]{2,}|', $text)) {
            return true;
        }

        return false;
    }

    function is_spam_by_akismet($email, $name, $ip, $agent, $referrer) {

        if (!class_exists('Akismet')) {
            return false;
        }

        if (empty($this->options['akismet'])) {
            return false;
        }

        $request = 'blog=' . rawurlencode(home_url()) . '&referrer=' . rawurlencode($referrer) .
                '&user_agent=' . rawurlencode($agent) .
                '&comment_type=signup' .
                '&comment_author_email=' . rawurlencode($email) .
                '&user_ip=' . rawurlencode($ip);
        if (!empty($name)) {
            $request .= '&comment_author=' . rawurlencode($name);
        }

        $response = Akismet::http_post($request, 'comment-check');

        if ($response && $response[1] == 'true') {
            return true;
        }
        return false;
    }

    function ip_match($ip, $range) {
        if (empty($ip)) {
            return false;
        }
        if (strpos($range, '/')) {
            list ($subnet, $bits) = explode('/', $range);
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
            return ($ip & $mask) == $subnet;
        } else {
            return strpos($range, $ip) === 0;
        }
    }
}
