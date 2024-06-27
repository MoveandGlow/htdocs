<?php

/*
  Plugin Name: Newsletter
  Plugin URI: https://www.thenewsletterplugin.com
  Description: Newsletter is a cool plugin to create your own subscriber list, to send newsletters, to build your business. <strong>Before update give a look to <a href="https://www.thenewsletterplugin.com/category/release">this page</a> to know what's changed.</strong>
  Version: 8.4.1
  Author: Stefano Lissa & The Newsletter Team
  Author URI: https://www.thenewsletterplugin.com
  Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
  Text Domain: newsletter
  License: GPLv2 or later
  Requires at least: 5.1
  Requires PHP: 7.0

  Copyright 2009-2024 The Newsletter Team (email: info@thenewsletterplugin.com, web: https://www.thenewsletterplugin.com)

  Newsletter is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 2 of the License, or
  any later version.

  Newsletter is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Newsletter. If not, see https://www.gnu.org/licenses/gpl-2.0.html.

 */

define('NEWSLETTER_VERSION', '8.4.1');

global $wpdb, $newsletter;

// For acceptance tests, DO NOT CHANGE
if (!defined('NEWSLETTER_DEBUG'))
    define('NEWSLETTER_DEBUG', false);

if (!defined('NEWSLETTER_EXTENSION_UPDATE'))
    define('NEWSLETTER_EXTENSION_UPDATE', true);

if (!defined('NEWSLETTER_EMAILS_TABLE'))
    define('NEWSLETTER_EMAILS_TABLE', $wpdb->prefix . 'newsletter_emails');

if (!defined('NEWSLETTER_USERS_TABLE'))
    define('NEWSLETTER_USERS_TABLE', $wpdb->prefix . 'newsletter');

if (!defined('NEWSLETTER_USERS_META_TABLE'))
    define('NEWSLETTER_USERS_META_TABLE', $wpdb->prefix . 'newsletter_user_meta');

if (!defined('NEWSLETTER_STATS_TABLE'))
    define('NEWSLETTER_STATS_TABLE', $wpdb->prefix . 'newsletter_stats');

if (!defined('NEWSLETTER_SENT_TABLE'))
    define('NEWSLETTER_SENT_TABLE', $wpdb->prefix . 'newsletter_sent');

if (!defined('NEWSLETTER_LOGS_TABLE'))
    define('NEWSLETTER_LOGS_TABLE', $wpdb->prefix . 'newsletter_logs');

if (!defined('NEWSLETTER_SEND_DELAY'))
    define('NEWSLETTER_SEND_DELAY', 0);

if (!defined('NEWSLETTER_USE_POST_GALLERY'))
    define('NEWSLETTER_USE_POST_GALLERY', false);

// Empty or "ajax"
if (!defined('NEWSLETTER_TRACKING_TYPE'))
    define('NEWSLETTER_TRACKING_TYPE', '');

if (!defined('NEWSLETTER_PAGE_WARNING'))
    define('NEWSLETTER_PAGE_WARNING', true);

// Empty or "ajax"
if (!defined('NEWSLETTER_ACTION_TYPE'))
    define('NEWSLETTER_ACTION_TYPE', '');

define('NEWSLETTER_SLUG', 'newsletter');

define('NEWSLETTER_DIR', __DIR__);
define('NEWSLETTER_INCLUDES_DIR', __DIR__ . '/includes');

if (!defined('NEWSLETTER_LIST_MAX'))
    define('NEWSLETTER_LIST_MAX', 40);

if (!defined('NEWSLETTER_PROFILE_MAX'))
    define('NEWSLETTER_PROFILE_MAX', 20);

if (!defined('NEWSLETTER_FORMS_MAX'))
    define('NEWSLETTER_FORMS_MAX', 10);

spl_autoload_register(function ($class) {
    static $prefix = 'Newsletter\\';
    static $dir = __DIR__ . '/classes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $file = $dir . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once NEWSLETTER_INCLUDES_DIR . '/defaults.php';
require_once NEWSLETTER_INCLUDES_DIR . '/classes.php';
require_once NEWSLETTER_INCLUDES_DIR . '/module-base.php';
require_once NEWSLETTER_INCLUDES_DIR . '/module.php';
require_once NEWSLETTER_INCLUDES_DIR . '/TNP.php';
require_once NEWSLETTER_INCLUDES_DIR . '/cron.php';
require_once NEWSLETTER_INCLUDES_DIR . '/composer-class.php';

class Newsletter extends NewsletterModule {

    // Limits to respect to avoid memory, time or provider limits
    var $time_start;
    var $time_limit = 0;
    var $max_emails = null;
    var $mailer = null;
    var $action = '';
    static $instance;

    /**
     * @return Newsletter
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {

        // Grab it before a plugin decides to remove it.
        if (isset($_GET['na'])) {
            $this->action = sanitize_key($_GET['na']);
        }
        if (isset($_POST['na'])) {
            $this->action = sanitize_key($_POST['na']);
        }

        $this->time_start = time();

        parent::__construct('main');

        // The main actions of WP during the inizialization phase, in order
        add_action('plugins_loaded', [$this, 'hook_plugins_loaded']);
        add_action('init', [$this, 'hook_init'], 1);
        add_action('wp_loaded', [$this, 'hook_wp_loaded'], 1);

        add_action('newsletter', [$this, 'hook_newsletter'], 1);

        add_action('wp_ajax_tnp', [$this, 'action']);
        add_action('wp_ajax_nopriv_tnp', [$this, 'action']);

        if (is_admin()) {
            add_action('wp_ajax_newsletter-log', function () {
                check_ajax_referer('newsletter-log');
                if (!current_user_can('administrator')) {
                    die('no admin');
                }
                $log = Newsletter\Logs::get((int) $_GET['id']);
                header('Content-Type: text/plain;charset=utf-8');
                if (empty($log->data))
                    echo '[no data]';
                else
                    echo $log->data;
                die();
            });
        }

        register_activation_hook(__FILE__, [$this, 'hook_activate']);
        register_deactivation_hook(__FILE__, [$this, 'hook_deactivate']);
    }

    /**
     * Action request via AJAX.
     */
    function action() {
        if (isset($_REQUEST['na'])) {
            $this->action = sanitize_key($_REQUEST['na']);
        }
    }

    /**
     * When all plugins have been loaded (but not initialized).
     */
    function hook_plugins_loaded() {

        $this->setup_language();

        // Used to load dependant modules
        do_action('newsletter_loaded', NEWSLETTER_VERSION);

        if (function_exists('load_plugin_textdomain')) {
            load_plugin_textdomain('newsletter', false, plugin_basename(__DIR__) . '/languages');
        }
    }

    /**
     * Plugins initialization.
     *
     * @global wpdb $wpdb
     */
    function hook_init() {

        // Here since there are still newsletter actions used by the admin modules
        if (current_user_can('administrator')) {
            self::$is_allowed = true;
        } else {
            $roles = $this->get_main_option('roles');
            if (!empty($roles)) {
                foreach ($roles as $role) {
                    if (current_user_can($role)) {
                        self::$is_allowed = true;
                        break;
                    }
                }
            }
        }

        if ($this->get_option('debug')) {
            ini_set('log_errors', 1);
            ini_set('error_log', WP_CONTENT_DIR . '/logs/newsletter/php-' . date('Y-m') . '-' . get_option('newsletter_logger_secret') . '.txt');
        }

        if (!is_admin() || defined('DOING_AJAX') && DOING_AJAX) {
            // Shortcode for the Newsletter page
            add_shortcode('newsletter', array($this, 'shortcode_newsletter'));
            add_shortcode('newsletter_replace', [$this, 'shortcode_newsletter_replace']);
        }

        add_filter('site_transient_update_plugins', [$this, 'hook_site_transient_update_plugins']);

        add_action('wp_enqueue_scripts', [$this, 'hook_wp_enqueue_scripts']);

        do_action('newsletter_init');

        if (is_admin() && !wp_next_scheduled('newsletter_clean')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'weekly', 'newsletter_clean');
        }
        add_action('newsletter_clean', [$this, 'newsletter_clean']);
    }

    function newsletter_clean() {
        Newsletter\Logs::clean();
    }

    function hook_wp_loaded() {

        // After everything has been loaded, since the plugin url could be changed (usually for multidomain installations)
        self::$plugin_url = plugins_url('newsletter');

        $this->setup_language();

        // Avoid upgrade during AJAX
        if (!defined('DOING_AJAX')) {
            $old_version = get_option('newsletter_version', '0.0.0');
            if ($old_version !== NEWSLETTER_VERSION) {
                include_once NEWSLETTER_INCLUDES_DIR . '/upgrade.php';
                update_option('newsletter_version', NEWSLETTER_VERSION);
            }
        }

        if (empty($this->action)) {
            return;
        }

        if ($this->action === 'test') {
            // This response is tested, do not change it!
            echo 'ok';
            die();
        }

        if ($this->action === 'nul') {
            $this->dienow('This link is not active on newsletter preview', 'You can send a test message to test subscriber to have the real working link.');
        }

        $user = $this->get_current_user();

        if ($user && $user->_dummy) {
            $email = $this->get_email_from_request();
            do_action('newsletter_action_dummy', $this->action, $user, $email);
            return;
        }

        if ($user && !empty($user->language)) {
            $this->switch_language($user->language);
        }

        $email = $this->get_email_from_request();
        do_action('newsletter_action', $this->action, $user, $email);
    }

    function hook_activate() {
        include_once NEWSLETTER_INCLUDES_DIR . '/upgrade.php';
        update_option('newsletter_version', NEWSLETTER_VERSION);
    }

    function first_install() {
        parent::first_install();
        update_option('newsletter_show_welcome', '1', false);
    }

    function is_allowed() {
        return self::$is_allowed;
    }

    /**
     * Sets the internal language used by admin panels to extract the language-related
     * values.
     *
     * @return type
     */
    function setup_language() {

        if (defined('NEWSLETTER_LANGUAGE')) {
            self::$is_multilanguage = true;
        } else {
            self::$is_multilanguage = apply_filters('newsletter_is_multilanguage', class_exists('SitePress') || function_exists('pll_default_language') || class_exists('TRP_Translate_Press'));
        }

        if (self::$is_multilanguage) {
            self::$language = self::_get_current_language();
            self::$locale = self::get_locale(self::$language);
        }
    }

    static function _get_current_language() {
        if (defined('NEWSLETTER_LANGUAGE')) {
            return NEWSLETTER_LANGUAGE;
        }

        // WPML
        if (class_exists('SitePress')) {
            $current_language = apply_filters('wpml_current_language', '');
            if ($current_language == 'all') {
                $current_language = '';
            }
            return $current_language;
        }

        // Polylang
        if (function_exists('pll_current_language')) {
            return pll_current_language();
        }

        // Trnslatepress and/or others
        $current_language = apply_filters('newsletter_current_language', '');

        return $current_language;
    }

    /**
     * Public CSS for subscription forms and profile form and widgets.
     */
    function hook_wp_enqueue_scripts() {
        $css = $this->get_option('css');

        if (empty($this->get_option('css_disabled')) && apply_filters('newsletter_enqueue_style', true)) {
            wp_enqueue_style('newsletter', $this->plugin_url() . '/style.css', [], NEWSLETTER_VERSION);

            if (!empty($css)) {
                wp_add_inline_style('newsletter', $css);
            }
        } else {
            if (!empty($css)) {
                add_action('wp_head', function () {
                    echo '<style>', $this->get_option('css'), '</style>';
                });
            }
        }
    }

    function get_message_key_from_request() {
        if (empty($_GET['nm'])) {
            return 'subscription';
        }
        $key = $_GET['nm'];
        switch ($key) {
            case 's': return 'confirmation';
            case 'c': return 'confirmed';
            case 'u': return 'unsubscription';
            case 'uc': return 'unsubscribed';
            case 'p':
            case 'pe':
                return 'profile';
            default: return $key;
        }
    }

    /**
     * The main shortcode to be used in the reserved page.
     * @todo This shortcode is not related only to subscription, move it away
     * @todo Separate below the code for the shortcode and the one for the "subscription" content
     *
     * @global wpdb $wpdb
     * @param array $attrs
     * @param string $content
     * @return string
     */
    function shortcode_newsletter($attrs, $content) {
        static $executing = false;

        // To avoid loops
        if ($executing) {
            return '';
        }

        $executing = true;

        $message_key = $this->get_message_key_from_request();

        $user = $this->get_current_user();

        // Lets modules to provie its own text
        $message = apply_filters('newsletter_page_text', '', $message_key, $user);
        $message = do_shortcode($message);

        $email = $this->get_email_from_request();
        $message = $this->replace($message, $user, $email, 'page');

        if (isset($_REQUEST['alert'])) {
            // slashes are already added by wordpress!
            $message .= '<script>alert("' . esc_js(strip_tags($_REQUEST['alert'])) . '");</script>';
        }
        $executing = false;

        return $message;
    }

    function shortcode_newsletter_replace($attrs, $content) {
        $content = do_shortcode($content);
        $content = $this->replace($content, $this->get_current_user(), $this->get_email_from_request(), 'page');
        return $content;
    }

    function relink($text, $email_id, $user_id, $email_token = '') {
        return NewsletterStatistics::instance()->relink($text, $email_id, $user_id, $email_token);
    }

    /**
     *
     * @global wpdb $wpdb
     * @param TNP_Email $email
     */
    function update_email_total($email) {
        global $wpdb;
        $total = (int) $wpdb->get_var(str_replace('*', 'count(*)', $email->query));
        if ($total > $email->total) {
            $wpdb->update(NEWSLETTER_EMAILS_TABLE, ['total' => $total], ['id' => $email->id], ['%d', '%d'], ['%d']);
            $email->total = $total;
        }
    }

    /**
     * Runs every 5 minutes and look for emails that need to be processed.
     */
    function hook_newsletter() {

        $this->logger->debug(__METHOD__ . '> Start');

        if (!$this->set_lock('engine', NEWSLETTER_CRON_INTERVAL * 2)) {
            $this->logger->fatal('Delivery engine lock already set: can be due to concurrente executions or fatal error during delivery');
            return;
        }

        $emails = $this->get_results("select * from " . NEWSLETTER_EMAILS_TABLE . " where status='sending' and send_on<=" . time() . " order by send_on asc");

        $this->logger->debug(__METHOD__ . '> ' . count($emails) . ' newsletter to be processed');

        foreach ($emails as $email) {

            $email->options = maybe_unserialize($email->options);
            $this->update_email_total($email);
            $r = $this->send($email);

            if (!$r) {
                break;
            }
        }

        $this->reset_lock('engine');

        $this->logger->debug(__METHOD__ . '> End');
    }

    function get_send_speed($email = null) {
        $this->logger->debug(__METHOD__ . '> Computing delivery speed');
        $mailer = $this->get_mailer();
        $speed = (int) $mailer->get_speed();
        if (!$speed) {
            $this->logger->debug(__METHOD__ . '> Speed not set by mailer, use the default');
            $speed = (int) $this->get_main_option('scheduler_max');
        } else {
            $this->logger->debug(__METHOD__ . '> Speed set by mailer');
        }

        $speed = max($speed, (int) (3600 / NEWSLETTER_CRON_INTERVAL));

        $this->logger->debug(__METHOD__ . '> Speed: ' . $speed);
        return $speed;
    }

    /**
     * Returns the delay in milliseconds between emails to respect a per second max speed.
     *
     * @return int Milliseconds
     */
    function get_send_delay() {
        if (NEWSLETTER_SEND_DELAY) {
            return NEWSLETTER_SEND_DELAY;
        }
        $max = (float) $this->get_main_option('max_per_second');
        if ($max > 0) {
            return (int) (1000 / $max);
        }
        return 0;
    }

    function skip_this_run($email = null) {
        return (boolean) apply_filters('newsletter_send_skip', false, $email);
    }

    function get_runs_per_hour() {
        return (int) (3600 / NEWSLETTER_CRON_INTERVAL);
    }

    /**
     * Used by Autoresponder.
     *
     * @return int
     */
    function get_emails_per_run() {
        $speed = $this->get_send_speed();
        $max = (int) ($speed / $this->get_runs_per_hour());

        return $max;
    }

    function get_max_emails($email) {
        // Obsolete, here from Speed Control Addon
        $max = (int) apply_filters('newsletter_send_max_emails', $this->max_emails, $email);

        return min($max, $this->max_emails);
    }

    function fix_email($email) {
        if (empty($email->query)) {
            $email->query = "select * from " . NEWSLETTER_USERS_TABLE . " where status='C'";
        }
        if (empty($email->id)) {
            $email->id = 0;
        }
    }

    function send_setup() {
        $this->logger->debug(__METHOD__ . '> Setup delivery engine');
        if (is_null($this->max_emails)) {
            $this->max_emails = $this->get_emails_per_run();
            $this->logger->debug(__METHOD__ . '> Max emails: ' . $this->max_emails);
            ignore_user_abort(true);

            @set_time_limit(NEWSLETTER_CRON_INTERVAL + 30);

            $max_time = (int) (@ini_get('max_execution_time') * 0.95);
            if ($max_time == 0 || $max_time > NEWSLETTER_CRON_INTERVAL) {
                $max_time = (int) (NEWSLETTER_CRON_INTERVAL * 0.95);
            }

            $this->time_limit = $this->time_start + $max_time;

            $this->logger->debug(__METHOD__ . '> Max time set to ' . $max_time);
        } else {
            $this->logger->debug(__METHOD__ . '> Already setup');
        }
    }

    function time_exceeded() {
        if ($this->time_limit && time() > $this->time_limit) {
            $this->logger->info(__METHOD__ . '> Max execution time limit reached');
            return true;
        }
    }

    /**
     * Sends an email to targeted users or to given users. If a list of users is given (usually a list of test users)
     * the query inside the email to retrieve users is not used.
     *
     * @global wpdb $wpdb
     * @global type $newsletter_feed
     * @param TNP_Email $email
     * @param array $users
     * @return boolean|WP_Error True if the process completed, false if limits was reached. On false the caller should no continue to call it with other emails.
     */
    function send($email, $users = null, $test = false) {
        global $wpdb;

        if (is_array($email)) {
            $email = (object) $email;
        }

        $this->logger->info(__METHOD__ . '> Start newsletter ' . $email->id);

        $this->send_setup();

        if ($this->max_emails <= 0) {
            $this->logger->info(__METHOD__ . '> No more capacity');
            return false;
        }

        $this->fix_email($email);

        // This stops the update of last_id and sent fields since
        // it's not a scheduled delivery but a test or something else (like an autoresponder)
        $supplied_users = $users != null;

        if (!$supplied_users) {

            if ($this->skip_this_run($email)) {
                $this->logger->info(__METHOD__ . '> Asked to skip this run');
                return true;
            }

            // Speed change for specific email by Speed Control Addon
            $max_emails = $this->get_max_emails($email);
            if ($max_emails <= 0) {
                $this->logger->info(__METHOD__ . '> Reached max emails for this newsletter');
                return true;
            }

            $query = $email->query;
            $query .= " and id>" . $email->last_id . " order by id limit " . $max_emails;

            $this->logger->debug(__METHOD__ . '> Query: ' . $query);

            $users = $this->get_results($query);

            if ($users === false) {
                $this->logger->fatal(__METHOD__ . '> Database error (see logs)');
                $this->set_error_state_of_email($email, 'Database error (see logs)');
                return true; // Continue with the next newsletter
            }

            $this->logger->debug(__METHOD__ . '> Loaded subscribers: ' . count($users));

            if (empty($users)) {
                $this->logger->info(__METHOD__ . '> No more users, set as sent');
                $wpdb->query("update " . NEWSLETTER_EMAILS_TABLE . " set status='sent', total=sent where id=" . $email->id . " limit 1");
                do_action('newsletter_ended_sending_newsletter', $email);
                return true;
            }
        } else {
            $this->logger->info(__METHOD__ . '> Subscribers supplied externally');
        }

        $start_time = microtime(true);
        $count = 0;
        $result = true;

        $mailer = $this->get_mailer();

        $batch_size = $mailer->get_batch_size();

        $delay = $this->get_send_delay();

        $this->logger->debug(__METHOD__ . '> Delay set to ' . $delay);

        //$this->logger->debug(__METHOD__ . '> Batch size: ' . $batch_size);
        // For batch size == 1 (normal condition) we optimize
        if ($batch_size == 1) {

            foreach ($users as $user) {

                if ($this->logger->is_debug)
                    $this->logger->debug(__METHOD__ . '> Processing user ID: ' . $user->id);

                $user = apply_filters('newsletter_send_user', $user);
                if (!$user) {
                    continue;
                }

                if (!$this->is_email($user->email)) {
                    $this->logger->error('Subscriber ' . $user->id . ' with invalid email, skipped');
                    if (!$test) {
                        $this->query("update " . NEWSLETTER_EMAILS_TABLE . " set sent=sent+1, last_id=" . $user->id . " where id=" . $email->id . " limit 1");
                    }
                    continue;
                }

                $message = $this->build_message($email, $user);

                // Save even test emails since people wants to see some stats even for test emails. Stats are reset upon the real "send" of a newsletter
                $this->save_sent_message($message);

                //Se non è un test incremento il contatore delle email spedite. Perchè incremento prima di spedire??
                if (!$test) {
                    $this->query("update " . NEWSLETTER_EMAILS_TABLE . " set sent=sent+1, last_id=" . $user->id . " where id=" . $email->id . " limit 1");
                }

                $r = $mailer->send($message);

                $this->max_emails--;
                $count++;

                if ($delay) {
                    usleep($delay * 1000);
                }

                if (!empty($message->error)) {
                    $this->logger->error($message);
                    $this->save_sent_message($message);
                }

                if (is_wp_error($r)) {
                    $this->logger->error($r);

                    // For fatal error, the newsletter status i changed to error (and the delivery stopped)
                    if (!$test && $r->get_error_code() == NewsletterMailer::ERROR_FATAL) {
                        $this->set_error_state_of_email($email, $r->get_error_message());
                        return $r;
                    }
                }

                if (!$supplied_users && !$test && $this->time_exceeded()) {
                    $result = false;
                    break;
                }
            }
        } else {

            $chunks = array_chunk($users, $batch_size);

            foreach ($chunks as $chunk) {

                $messages = [];

                // Peeparing a batch of messages
                foreach ($chunk as $user) {

                    $this->logger->debug(__METHOD__ . '> Processing user ID: ' . $user->id);
                    $user = apply_filters('newsletter_send_user', $user);
                    if (!$this->is_email($user->email)) {
                        $this->logger->error('Subscriber ' . $user->id . ' with invalid email');
                        continue;
                    }
                    $message = $this->build_message($email, $user);
                    $this->save_sent_message($message);
                    $messages[] = $message;

                    if (!$test) {
                        $wpdb->query("update " . NEWSLETTER_EMAILS_TABLE . " set sent=sent+1, last_id=" . $user->id . " where id=" . $email->id . " limit 1");
                    }
                    $this->max_emails--;
                    $count++;
                }

                $r = $mailer->send_batch($messages);

                // Updating the status of the sent messages
                foreach ($messages as $message) {
                    if (!empty($message->error)) {
                        $this->save_sent_message($message);
                    }
                }

                // The batch went in error
                if (is_wp_error($r)) {
                    $this->logger->error($r);

                    if (!$test && $r->get_error_code() == NewsletterMailer::ERROR_FATAL) {
                        $this->set_error_state_of_email($email, $r->get_error_message());
                        return $r;
                    }
                }

                if (!$supplied_users && !$test && $this->time_exceeded()) {
                    $result = false;
                    break;
                }
            }
        }

        $end_time = microtime(true);

        // Stats only for newsletter with enough emails in a batch (we exclude the Autoresponder since it send one email per call)
        if (!$test && !$supplied_users && $count > 5) {
            $this->update_send_stats($start_time, $end_time, $count, $result);
        }

        // Cached general statistics are reset
        if (!$test) {
            NewsletterStatistics::instance()->reset_stats_time($email->id);
        }

        $this->logger->info(__METHOD__ . '> End run for email ' . $email->id);

        return $result;
    }

    function update_send_stats($start_time, $end_time, $count, $result) {
        $send_calls = get_option('newsletter_diagnostic_send_calls', []);
        if (!is_array($send_calls))
            $send_calls = [];
        $send_calls[] = [$start_time, $end_time, $count, $result];

        if (count($send_calls) > 100) {
            array_shift($send_calls);
        }

        update_option('newsletter_diagnostic_send_calls', $send_calls, false);
    }

    /**
     * @param TNP_Email $email
     */
    private function set_error_state_of_email($email, $message = '') {
        // Handle only message type at the moment
        if ($email->type !== 'message') {
            return;
        }

        do_action('newsletter_error_on_sending', $email, $message);

        $edited_email = new TNP_Email();
        $edited_email->id = $email->id;
        $edited_email->status = TNP_Email::STATUS_ERROR;
        $edited_email->options = $email->options;
        $edited_email->options['error_message'] = $message;

        $this->save_email($edited_email);
    }

    /**
     *
     * @param TNP_Email $email
     * @param TNP_User $user
     * @return \TNP_Mailer_Message
     */
    function build_message($email, $user) {

        $message = new TNP_Mailer_Message();

        $message->to = $user->email;

        $message->headers = [
            'Precedence' => 'bulk',
            'X-Newsletter-Email-Id' => $email->id,
            'X-Auto-Response-Suppress' => 'OOF, AutoReply'
        ];

        $message->headers = apply_filters('newsletter_message_headers', $message->headers, $email, $user);

        $message->body = preg_replace('/data-json=".*?"/is', '', $email->message);
        $message->body = preg_replace('/  +/s', ' ', $message->body);
        $message->body = $this->replace_for_email($message->body, $user, $email);
        $message->body = do_shortcode($message->body);

        $message->body = apply_filters('newsletter_message_html', $message->body, $email, $user);

        $message->body_text = $this->replace($email->message_text, $user, $email);
        $message->body_text = apply_filters('newsletter_message_text', $message->body_text, $email, $user);

        if ($email->track == 1) {
            $message->body = $this->relink($message->body, $email->id, $user->id, $email->token);
        }

        if (empty($email->subject)) {
            $message->subject = '[no subject]';
        } else {
            $message->subject = $this->replace($email->subject, $user, $email);
        }

        $message->subject = apply_filters('newsletter_message_subject', $message->subject, $email, $user);

        if (!empty($email->options['sender_email'])) {
            $message->from = $email->options['sender_email'];
        } else {
            $message->from = $this->get_sender_email();
        }

        if (!empty($email->options['sender_name'])) {
            $message->from_name = $email->options['sender_name'];
        } else {
            $message->from_name = $this->get_sender_name();
        }

        $message->email_id = $email->id;
        $message->user_id = $user->id;

        return apply_filters('newsletter_message', $message, $email, $user);
    }

    /**
     *
     * @param TNP_Mailer_Message $message
     * @param int $status
     * @param string $error
     */
    function save_sent_message($message) {
        global $wpdb;

        if (!$message->user_id || !$message->email_id) {
            return;
        }
        $status = empty($message->error) ? 0 : 1;

        $error = mb_substr($message->error, 0, 250);

        $this->query($wpdb->prepare("insert into " . $wpdb->prefix . 'newsletter_sent (user_id, email_id, time, status, error) values (%d, %d, %d, %d, %s) on duplicate key update time=%d, status=%d, error=%s',
                        $message->user_id, $message->email_id, time(), $status, $error, time(), $status, $error));
    }

    /**
     * @deprecated since version 7.3.0
     */
    function limits_exceeded() {
        return false;
    }

    /**
     * @deprecated since version 6.0.0
     */
    function register_mail_method($callable) {

    }

    function register_mailer($mailer) {
        if ($mailer instanceof NewsletterMailer) {
            $this->mailer = $mailer;
        }
    }

    /**
     * Returns the current registered mailer which must be used to send emails.
     *
     * @return NewsletterMailer
     */
    function get_mailer() {
        if ($this->mailer) {
            return $this->mailer;
        }

        do_action('newsletter_register_mailer');

        if (!$this->mailer) {
            $this->mailer = new NewsletterDefaultMailer();
        }
        return $this->mailer;
    }

    /**
     *
     * @param TNP_Mailer_Message $message
     * @return type
     */
    function deliver($message) {
        $mailer = $this->get_mailer();
        if (empty($message->from)) {
            $message->from = $this->get_sender_email();
        }
        if (empty($message->from_name)) {
            $mailer->from_name = $this->get_sender_name();
        }
        return $mailer->send($message);
    }

    /**
     *
     * @param type $to
     * @param type $subject
     * @param string|array $message If string is considered HTML, is array should contains the keys "html" and "text"
     * @param type $headers
     * @param type $enqueue
     * @param type $from
     * @return boolean
     */
    function mail($to, $subject, $message, $headers = array(), $enqueue = false, $from = false) {

        if (empty($subject)) {
            $this->logger->error('mail> Subject empty, skipped');
            return true;
        }

        $mailer_message = new TNP_Mailer_Message();
        $mailer_message->to = $to;
        $mailer_message->subject = $subject;
        $mailer_message->from = $this->get_option('sender_email');
        $mailer_message->from_name = $this->get_option('sender_name');

        if (!empty($headers)) {
            $mailer_message->headers = $headers;
        }
        $mailer_message->headers['X-Auto-Response-Suppress'] = 'OOF, AutoReply';

        // Message carrige returns and line feeds clean up
        if (!is_array($message)) {
            $mailer_message->body = $this->clean_eol($message);
        } else {
            if (!empty($message['text'])) {
                $mailer_message->body_text = $this->clean_eol($message['text']);
            }

            if (!empty($message['html'])) {
                $mailer_message->body = $this->clean_eol($message['html']);
            }
        }

        $this->logger->debug($mailer_message);

        $mailer = $this->get_mailer();

        $r = $mailer->send($mailer_message);

        return !is_wp_error($r);
    }

    function hook_deactivate() {
        wp_clear_scheduled_hook('newsletter');
    }

    function find_file($file1, $file2) {
        if (is_file($file1))
            return $file1;
        return $file2;
    }

    function hook_site_transient_update_plugins($value) {
        static $extra_response = array();

        //$this->logger->debug('Update plugins transient called');

        if (!$value || !is_object($value)) {
            //$this->logger->info('Empty object');
            return $value;
        }

        if (!isset($value->response) || !is_array($value->response)) {
            $value->response = array();
        }

        // Already computed? Use it! (this filter is called many times in a single request)
        if ($extra_response) {
            //$this->logger->debug('Already updated');
            $value->response = array_merge($value->response, $extra_response);
            return $value;
        }

        $extensions = $this->getTnpExtensions();

        // Ops...
        if (!$extensions) {
            return $value;
        }

        foreach ($extensions as $extension) {
            unset($value->response[$extension->wp_slug]);
            unset($value->no_update[$extension->wp_slug]);
        }

        // Someone doesn't want our addons updated, let respect it (this constant should be defined in wp-config.php)
        if (!NEWSLETTER_EXTENSION_UPDATE) {
            //$this->logger->info('Updates disabled');
            return $value;
        }

        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        // Ok, that is really bad (should we remove it? is there a minimum WP version?)
        if (!function_exists('get_plugin_data')) {
            //$this->logger->error('No get_plugin_data function available!');
            return $value;
        }

        $license_key = $this->get_license_key();

        // Here we prepare the update information BUT do not add the link to the package which is privided
        // by our Addons Manager (due to WP policies)
        foreach ($extensions as $extension) {

            // Patch for names convention
            $extension->plugin = $extension->wp_slug;

            //$this->logger->debug('Processing ' . $extension->plugin);
            //$this->logger->debug($extension);

            $plugin_data = false;
            if (file_exists(WP_PLUGIN_DIR . '/' . $extension->plugin)) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $extension->plugin, false, false);
            } else if (file_exists(WPMU_PLUGIN_DIR . '/' . $extension->plugin)) {
                $plugin_data = get_plugin_data(WPMU_PLUGIN_DIR . '/' . $extension->plugin, false, false);
            }

            if (!$plugin_data) {
                //$this->logger->debug('Seems not installed');
                continue;
            }

            $plugin = new stdClass();
            $plugin->id = $extension->id;
            $plugin->slug = $extension->slug;
            $plugin->plugin = $extension->plugin;
            $plugin->new_version = $extension->version;
            $plugin->url = $extension->url;
            if (class_exists('NewsletterExtensions')) {
                // NO filters here!
                $plugin->package = NewsletterExtensions::$instance->get_package($extension->id, $license_key);
            } else {
                $plugin->package = '';
            }
//            [banners] => Array
//                        (
//                            [2x] => https://ps.w.org/wp-rss-aggregator/assets/banner-1544x500.png?rev=2040548
//                            [1x] => https://ps.w.org/wp-rss-aggregator/assets/banner-772x250.png?rev=2040548
//                        )
//            [icons] => Array
//                        (
//                            [2x] => https://ps.w.org/advanced-custom-fields/assets/icon-256x256.png?rev=1082746
//                            [1x] => https://ps.w.org/advanced-custom-fields/assets/icon-128x128.png?rev=1082746
//                        )
            if (version_compare($extension->version, $plugin_data['Version']) > 0) {
                //$this->logger->debug('There is a new version');
                $extra_response[$extension->plugin] = $plugin;
            } else {
                // Maybe useless...
                //$this->logger->debug('There is NOT a new version');
                $value->no_update[$extension->plugin] = $plugin;
            }
            //$this->logger->debug('Added');
        }

        $value->response = array_merge($value->response, $extra_response);

        return $value;
    }

    /**
     * @deprecated since version 6.1.9
     */
    function get_extension_version($extension_id) {
        return null;
    }

    /**
     * @deprecated since version 6.1.9
     */
    function set_extension_update_data($value, $extension) {
        return $value;
    }

    /**
     * Retrieve the extensions form the tnp site
     * @return array
     */
    function getTnpExtensions() {

        $extensions_json = get_transient('tnp_extensions_json');

        if (empty($extensions_json)) {
            $url = "http://www.thenewsletterplugin.com/wp-content/extensions.json?ver=" . NEWSLETTER_VERSION;
            $extensions_response = wp_remote_get($url);

            if (is_wp_error($extensions_response)) {
                // Cache anyway for blogs which cannot connect outside
                $extensions_json = '[]';
                set_transient('tnp_extensions_json', $extensions_json, 72 * 60 * 60);
                $this->logger->error($extensions_response);
            } else {

                $extensions_json = wp_remote_retrieve_body($extensions_response);

                // Not clear cases
                if (empty($extensions_json) || !json_decode($extensions_json)) {
                    $this->logger->error('Invalid json from thenewsletterplugin.com: retrying in 72 hours');
                    $this->logger->error('JSON: ' . $extensions_json);
                    $extensions_json = '[]';
                }
                set_transient('tnp_extensions_json', $extensions_json, 72 * 60 * 60);
            }
        }

        $extensions = json_decode($extensions_json);

        return $extensions;
    }

    function clear_extensions_cache() {
        delete_transient('tnp_extensions_json');
    }

    /**
     * @deprecated
     */
    function add_panel($key, $panel) {

    }

    function has_license() {
        return !empty($this->get_main_option('contract_key'));
    }

    function get_sender_name() {
        return $this->get_main_option('sender_name');
    }

    function get_sender_email() {
        return $this->get_main_option('sender_email');
    }

    function get_reply_to() {
        return $this->get_main_option('reply_to');
    }

    /**
     *
     * @return int
     */
    function get_newsletter_page_id() {
        return (int) $this->get_option('page');
    }

    /**
     *
     * @return WP_Post
     */
    function get_newsletter_page() {
        $page_id = $this->get_newsletter_page_id();
        if (!$page_id) {
            return false;
        }
        return get_post($page_id);
    }

    /**
     * Returns the Newsletter public page URL or an alternative URL if that page if not
     * configured or not available.
     *
     * @staticvar string $url
     * @return string
     */
    function get_newsletter_page_url($language = '') {

        $page = $this->get_newsletter_page();

        if (!$page || $page->post_status !== 'publish') {
//            if (current_user_can('administrator')) {
//                $this->dienow('Public page not available. This message is shown only to administrators, user will see the home page.'
//                        . 'Please review the "public page" setting on the Newsletter\'s main configuration.');
//            }
            return home_url();
        }

        $url = get_permalink($page->ID);

        return $url;
    }

    function get_license_key() {
        if (defined('NEWSLETTER_LICENSE_KEY')) {
            return NEWSLETTER_LICENSE_KEY;
        } else {
            if (!empty($this->options['contract_key'])) {
                return trim($this->options['contract_key']);
            }
        }
        return false;
    }

    /**
     * Get the data connected to the specified license code on man settings.
     *
     * - false if no license is present
     * - WP_Error if something went wrong if getting the license data
     * - object with expiration and addons list
     *
     * @param boolean $refresh
     * @return \WP_Error|boolean|object
     */
    function get_license_data($refresh = false) {

        $license_key = $this->get_license_key();
        if (empty($license_key)) {
            delete_transient('newsletter_license_data');
            return false;
        }

        if (!$refresh) {
            $license_data = get_transient('newsletter_license_data');
            if ($license_data !== false && is_object($license_data)) {
                return $license_data;
            }
        }

        $license_data_url = 'https://www.thenewsletterplugin.com/wp-content/plugins/file-commerce-pro/get-license-data.php';

        $response = wp_remote_post($license_data_url, [
            'body' => ['k' => $license_key]
        ]);

        // Fall back to http...
        if (is_wp_error($response)) {
            $license_data_url = str_replace('https', 'http', $license_data_url);
            $response = wp_remote_post($license_data_url, array(
                'body' => array('k' => $license_key)
            ));
            if (is_wp_error($response)) {
                set_transient('newsletter_license_data', $response, DAY_IN_SECONDS);
                return $response;
            }
        }

        $download_message = 'You can download all addons from www.thenewsletterplugin.com if your license is valid.';

        if (wp_remote_retrieve_response_code($response) != '200') {
            $data = new WP_Error(wp_remote_retrieve_response_code($response), 'License validation service error. <br>' . $download_message);
            set_transient('newsletter_license_data', $data, DAY_IN_SECONDS);
            return $data;
        }

        $json = wp_remote_retrieve_body($response);
        $data = json_decode($json);

        if (!is_object($data)) {
            $data = new WP_Error(1, 'License validation service error. <br>' . $download_message);
            set_transient('newsletter_license_data', $data, DAY_IN_SECONDS);
            return $data;
        }

        if (isset($data->message)) {
            $data = new WP_Error(1, $data->message . ' (check the license on Newsletter main settings)');
            set_transient('newsletter_license_data', $data, DAY_IN_SECONDS);
            return $data;
        }

        $expiration = WEEK_IN_SECONDS;
        // If the license expires in few days, make the transient live only few days, so it will be refreshed
        if ($data->expire > time() && $data->expire - time() < WEEK_IN_SECONDS) {
            $expiration = $data->expire - time();
        }
        set_transient('newsletter_license_data', $data, $expiration);

        return $data;
    }

    /**
     * @deprecated
     * @param type $license_key
     * @return \WP_Error
     */
    public static function check_license($license_key) {
        $response = wp_remote_get('http://www.thenewsletterplugin.com/wp-content/plugins/file-commerce-pro/check.php?k=' . urlencode($license_key), array('sslverify' => false));
        if (is_wp_error($response)) {
            /* @var $response WP_Error */
            return new WP_Error(-1, 'It seems that your blog cannot contact the license validator. Ask your provider to unlock the HTTP/HTTPS connections to www.thenewsletterplugin.com<br>'
                    . esc_html($response->get_error_code()) . ' - ' . esc_html($response->get_error_message()));
        } else if ($response['response']['code'] != 200) {
            return new WP_Error(-1, '[' . $response['response']['code'] . '] The license seems expired or not valid, please check your <a href="https://www.thenewsletterplugin.com/account">license code and status</a>, thank you.'
                    . '<br>You can anyway download the professional extension from https://www.thenewsletterplugin.com.');
        } elseif ($expires = json_decode(wp_remote_retrieve_body($response))) {
            return array('expires' => $expires->expire, 'message' => 'Your license is valid and expires on ' . esc_html(date('Y-m-d', $expires->expire)));
        } else {
            return new WP_Error(-1, 'Unable to detect the license expiration. Debug data to report to the support: <code>' . esc_html(wp_remote_retrieve_body($response)) . '</code>');
        }
    }
}

$newsletter = Newsletter::instance();

// Frontend modules
require_once NEWSLETTER_DIR . '/users/users.php';
require_once NEWSLETTER_DIR . '/subscription/subscription.php';
require_once NEWSLETTER_DIR . '/emails/emails.php';
require_once NEWSLETTER_DIR . '/statistics/statistics.php';
require_once NEWSLETTER_DIR . '/unsubscription/unsubscription.php';
require_once NEWSLETTER_DIR . '/profile/profile.php';
require_once NEWSLETTER_DIR . '/widget/standard.php';
require_once NEWSLETTER_DIR . '/widget/minimal.php';

if (is_admin()) {
    require_once NEWSLETTER_DIR . '/admin.php';
}



