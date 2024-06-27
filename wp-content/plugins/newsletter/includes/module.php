<?php

defined('ABSPATH') || exit;

require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/store.php';
require_once __DIR__ . '/composer.php';
require_once __DIR__ . '/addon.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/themes.php';

/**
 * @property array $options For compatibility, the main module options (magic method).
 */
class NewsletterModule extends NewsletterModuleBase {

    static $cache = [];

    function __construct($module) {
        parent::__construct($module);
    }

    function __get($name) {
        if ($name === 'options') {
            return $this->get_options();
        }
    }

    /**
     * Returns the options for a given set name processed to reflect the values for the current
     * language.
     * If $set is empty, the module name of the subclass is used.
     * If $language is not null, the options processed for the specified language are returned
     * (an empty string refers to the main set of options).
     *
     * Note: the get_options() for the frontend and the get_options() for the backend return
     * different values, since in the frontend the full merge (language specific set, main set and default set)
     * is performed.
     *
     * @param string $set Name of the options set
     * @param string $language
     * @return array
     */
    function get_options($set = '', $language = null) {
        if (is_null($language)) {
            $language = $this->language();
        }

        if (empty($set)) {
            $set = $this->module;
        }

        $cache_key = $set . $language;

        if (isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }

        if ($language) {
            $options = array_merge(
                    $this->get_default_options($set),
                    $this->get_option_array($this->get_prefix($set, '')),
                    $this->get_option_array($this->get_prefix($set, $language)));
        } else {
            $options = array_merge(
                    $this->get_default_options($set),
                    $this->get_option_array($this->get_prefix($set, '')));
        }

        self::$cache[$cache_key] = $options;

        return $options;
    }

    function get_main_options($set = '') {
        return $this->get_options($set, '');
    }

    /**
     * Get the option value for a specifc key using the current language.
     *
     * @param string $key
     * @param string $sub
     * @return mixed Returns null if the option is not found
     */
    function get_option($key, $sub = '', $language = null) {
        if (is_null($language)) {
            $language = $this->language();
        }
        if (!$sub) {
            $sub = $this->module;
        }
        $cache_key = $sub . $language;
        if (isset(self::$cache[$cache_key][$key])) {
            return self::$cache[$cache_key][$key];
        }

        $options = $this->get_options($sub, $language);
        if (!isset($options[$key])) {
            return null;
        }
        return $options[$key];
    }

    /**
     * Returns the main value of an option, not considering the current language.
     * Used to get those options which are not language related.
     *
     * @param string $key
     * @param string $sub
     * @return mixed
     */
    function get_main_option($key, $sub = '') {
        return $this->get_option($key, $sub, '');
    }

    function get_last_run($sub = '') {
        return get_option($this->get_prefix($sub) . '_last_run', 0);
    }

    /**
     * Save the module last run value. Used to store a timestamp for some modules,
     * for example the Feed by Mail module.
     *
     * @param int $time Unix timestamp (as returned by time() for example)
     * @param string $sub Sub module name (default empty)
     */
    function save_last_run($time, $sub = '') {
        update_option($this->get_prefix($sub) . '_last_run', $time);
    }

    /**
     * Sums $delta seconds to the last run time.
     * @param int $delta Seconds
     * @param string $sub Sub module name (default empty)
     */
    function add_to_last_run($delta, $sub = '') {
        $time = $this->get_last_run($sub);
        $this->save_last_run($time + $delta, $sub);
    }

    /**
     * Checks if the semaphore of that name (for this module) is still red. If it is active the method
     * returns false. If it is not active, it will be activated for $time seconds.
     *
     * Since this method activate the semaphore when called, it's name is a bit confusing.
     *
     * @param string $name Sempahore name (local to this module)
     * @param int $time Max time in second this semaphore should stay red
     * @return boolean False if the semaphore is red and you should not proceed, true is it was not active and has been activated.
     */
    function check_transient($name, $time) {
        if ($time < 60)
            $time = 60;
        //usleep(rand(0, 1000000));
        if (($value = get_transient($this->get_prefix() . '_' . $name)) !== false) {
            list($t, $v) = explode(';', $value, 2);
            $this->logger->error('Blocked by transient ' . $this->get_prefix() . '_' . $name . ' set ' . (time() - $t) . ' seconds ago by ' . $v);
            return false;
        }
        //$ip = ''; //gethostbyname(gethostname());
        $value = time() . ";" . ABSPATH . ';' . gethostname();
        set_transient($this->get_prefix() . '_' . $name, $value, $time);
        return true;
    }

    function delete_transient($name = '') {
        delete_transient($this->get_prefix() . '_' . $name);
    }

    /**
     * Converts a GMT date from mysql (see the posts table columns) into a timestamp.
     *
     * @param string $s GMT date with format yyyy-mm-dd hh:mm:ss
     * @return int A timestamp
     */
    static function m2t($s) {

        // TODO: use the wordpress function I don't remember the name
        $s = explode(' ', $s);
        $d = explode('-', $s[0]);
        $t = explode(':', $s[1]);
        return gmmktime((int) $t[0], (int) $t[1], (int) $t[2], (int) $d[1], (int) $d[2], (int) $d[0]);
    }

    static function format_date($time) {
        if (empty($time)) {
            return '-';
        }
        return gmdate(get_option('date_format') . ' ' . get_option('time_format'), $time + get_option('gmt_offset') * 3600);
    }

    static function format_time_delta($delta) {
        $days = floor($delta / (3600 * 24));
        $hours = floor(($delta % (3600 * 24)) / 3600);
        $minutes = floor(($delta % 3600) / 60);
        $seconds = floor(($delta % 60));
        $buffer = $days . ' days, ' . $hours . ' hours, ' . $minutes . ' minutes, ' . $seconds . ' seconds';
        return $buffer;
    }

    static function date($time = null, $now = false, $left = false) {
        if (is_null($time)) {
            $time = time();
        }
        if ($time == false) {
            $buffer = 'none';
        } else {
            $buffer = gmdate(get_option('date_format') . ' ' . get_option('time_format'), $time + get_option('gmt_offset') * 3600);
        }
        if ($now) {
            $buffer .= ' (now: ' . gmdate(get_option('date_format') . ' ' .
                            get_option('time_format'), time() + get_option('gmt_offset') * 3600);
            $buffer .= ')';
        }
        if ($left) {
            $buffer .= ', ' . gmdate('H:i:s', $time - time()) . ' left';
        }
        return $buffer;
    }

    /**
     * Return an array of array with on first element the array of recent post and on second element the array
     * of old posts.
     *
     * @param array $posts
     * @param int $time
     */
    static function split_posts(&$posts, $time = 0) {
        if ($time < 0) {
            return array_chunk($posts, ceil(count($posts) / 2));
        }

        $result = array(array(), array());

        if (empty($posts))
            return $result;

        foreach ($posts as &$post) {
            if (self::is_post_old($post, $time))
                $result[1][] = $post;
            else
                $result[0][] = $post;
        }
        return $result;
    }

    static function is_post_old(&$post, $time = 0) {
        return self::m2t($post->post_date_gmt) <= $time;
    }

    static function get_post_image($post_id = null, $size = 'thumbnail', $alternative = null) {
        global $post;

        if (empty($post_id))
            $post_id = $post->ID;
        if (empty($post_id))
            return $alternative;

        $image_id = function_exists('get_post_thumbnail_id') ? get_post_thumbnail_id($post_id) : false;
        if ($image_id) {
            $image = wp_get_attachment_image_src($image_id, $size);
            return $image[0];
        } else {
            $attachments = get_children(array('post_parent' => $post_id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID'));

            if (empty($attachments)) {
                return $alternative;
            }

            foreach ($attachments as $id => $attachment) {
                $image = wp_get_attachment_image_src($id, $size);
                return $image[0];
            }
        }
    }

    function get_email_from_request() {

        if (isset($_REQUEST['nek'])) {
            list($id, $token) = @explode('-', $_REQUEST['nek'], 2);
        } else if (isset($_COOKIE['tnpe'])) {
            list($id, $token) = @explode('-', $_COOKIE['tnpe'], 2);
        } else {
            return null;
        }

        $email = $this->get_email($id);

        // TODO: Check the token? It's really useful?

        return $email;
    }

    /** Searches for a user using the nk parameter or the ni and nt parameters. Tries even with the newsletter cookie.
     * If found, the user object is returned or null.
     * The user is returned without regards to his status that should be checked by caller.
     *
     * DO NOT REMOVE EVEN IF OLD
     *
     * @return TNP_User
     */
    function check_user($context = '') {
        global $wpdb;

        $user = null;

        if (isset($_REQUEST['nk'])) {
            list($id, $token) = @explode('-', $_REQUEST['nk'], 2);
        } else if (isset($_COOKIE['newsletter'])) {
            list ($id, $token) = @explode('-', $_COOKIE['newsletter'], 2);
        }

        if (isset($id)) {
            $user = $this->get_user($id);
            if ($user) {
                if ($context == 'preconfirm') {
                    if ($token != md5($user->token)) {
                        $user = null;
                    }
                } else {
                    if ($token != $user->token) {
                        $user = null;
                    }
                }
            }
        }
        return apply_filters('newsletter_current_user', $user);
    }

    /**
     * Accepts a user ID or a TNP_User object. Does not check if the user really exists.
     *
     * @param type $user
     */
    function get_user_edit_url($user) {
        $id = $this->to_int_id($user);
        return admin_url('admin.php') . '?page=newsletter_users_edit&id=' . $id;
    }

    function get_user_status_label($user, $html = false) {
        if (is_string($user)) {
            $x = $user;
            $user = new stdClass();
            $user->status = $x;
        }
        if (!$html) {
            return TNP_User::get_status_label($user->status);
        }

        $label = TNP_User::get_status_label($user->status);
        $class = 'unknown';
        switch ($user->status) {
            case TNP_User::STATUS_NOT_CONFIRMED: $class = 'not-confirmed';
                break;
            case TNP_User::STATUS_CONFIRMED: $class = 'confirmed';
                break;
            case TNP_User::STATUS_UNSUBSCRIBED: $class = 'unsubscribed';
                break;
            case TNP_User::STATUS_BOUNCED: $class = 'bounced';
                break;
            case TNP_User::STATUS_COMPLAINED: $class = 'complained';
                break;
        }
        return '<span class="tnp-status tnp-user-status tnp-user-status--' . $class . '">' . esc_html($label) . '</span>';
    }

    /**
     * Return the user identified by the "nk" parameter (POST or GET).
     * If no user can be found or the token is not matching, returns null.
     * If die_on_fail is true it dies instead of return null.
     *
     * @param bool $die_on_fail
     * @return TNP_User
     */
    function get_user_from_request($die_on_fail = false, $context = '') {
        $id = 0;
        if (isset($_REQUEST['nk'])) {
            list($id, $token) = @explode('-', $_REQUEST['nk'], 2);
        }
        $user = $this->get_user($id);

        if ($user == null) {
            if ($die_on_fail) {
                die(__('No subscriber found.', 'newsletter'));
            } else {
                return $this->get_user_from_logged_in_user();
            }
        }

        if ($token != $user->token && $token != md5($user->token)) {
            if ($die_on_fail) {
                die(__('No subscriber found.', 'newsletter'));
            } else {
                return $this->get_user_from_logged_in_user();
            }
        }
        return $user;
    }

    function set_user_cookie($user) {
        setcookie('newsletter', $this->get_user_key($user), time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
    }

    function delete_user_cookie() {
        setcookie('newsletter', '', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
    }

    function is_current_user_dummy() {
        if (!current_user_can('administrator'))
            return false;

        if (isset($_REQUEST['nk'])) {
            list($id, $token) = explode('-', $_REQUEST['nk'], 2);
        } else if (isset($_COOKIE['newsletter'])) {
            list ($id, $token) = explode('-', $_COOKIE['newsletter'], 2);
        } else {
            return false;
        }

        return $id === '0';
    }

    /**
     *
     * @return TNP_User
     */
    function get_current_user() {

        $id = 0;
        $user = null;

        if (isset($_REQUEST['nk'])) {
            list($id, $token) = explode('-', $_REQUEST['nk'], 2);
            if (current_user_can('administrator') && $id === '0') {
                $user = $this->get_dummy_user();
                return $user;
            }
        } else if (isset($_COOKIE['newsletter'])) {
            list ($id, $token) = explode('-', $_COOKIE['newsletter'], 2);
        }

        if ($id) {
            $user = $this->get_user($id);
            if ($user) {
                $user->_dummy = false;
                $token_md5 = md5($user->token);
                if ($token !== $user->token && $token !== $token_md5) {
                    $user = null;
                } else {
                    $user->_trusted = $token === $user->token;
                }
            }
        }

        $user = apply_filters('newsletter_current_user', $user);

        return $user;
    }

    /**
     * Managed by WP Users Addon
     * @deprecated since version 7.6.7
     * @return TNP_User
     */
    function get_user_from_logged_in_user() {
        return null;
    }

    function get_user_count($refresh = false) {
        global $wpdb;
        $user_count = get_transient('newsletter_user_count');
        if ($user_count === false || $refresh) {
            $user_count = $wpdb->get_var("select count(*) from " . NEWSLETTER_USERS_TABLE . " where status='C'");
            set_transient('newsletter_user_count', $user_count, DAY_IN_SECONDS);
        }
        return $user_count;
    }

    /**
     * Returns all configured custom fields using the current language.
     *
     * @return TNP_Profile[]
     */
    function get_customfields() {

        //static $customfields = null;
        //if (is_null($customfields)) {
        $customfields = [];
        $options = $this->get_options('customfields');
        $main_options = $this->get_options('customfields', '');
        for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
            $prefix = 'profile_' . $i;
            if (!empty($main_options[$prefix])) {
                $name = empty($options[$prefix]) ? $main_options[$prefix] : $options[$prefix];
                $field = new TNP_Profile($i, $name);
                $field->type = $main_options[$prefix . '_type'];
                $values = empty($options[$prefix . '_options']) ? $main_options[$prefix . '_options'] : $options[$prefix . '_options'];
                $items = array_map('trim', explode(',', $values));
                $items = array_combine($items, $items);
                $field->options = $items;
                $field->placeholder = empty($options[$prefix . '_placeholder']) ? $main_options[$prefix . '_placeholder'] : $options[$prefix . '_placeholder'];
                $field->rule = $options[$prefix . '_rules'];
                $field->status = (int) $options[$prefix . '_status'];
                $customfields['' . $i] = $field;
            }
        }
        //}
        return $customfields;
    }

    /**
     * Returns the specified custom field (onlyif configured, null otherwise).
     *
     * @param TNP_Profile $id
     * @return TNP_Profile[]
     */
    function get_customfield($id) {
        $customfields = $this->get_customfields();
        if (isset($customfields[$id])) {
            return $customfields[$id];
        } else {
            return null;
        }
    }

    /**
     * Returns an array (id => object) with all custom fields that can be used
     * on frontend.
     *
     * @staticvar TNP_Profile[] $customfields Internal cache
     * @return TNP_Profile[]
     */
    function get_customfields_public() {
        //static $customfields = null;
        //if (is_null($customfields)) {
        $customfields = [];
        foreach ($this->get_customfields() as $customfield) {
            if ($customfield->is_public()) {
                $customfields['' . $customfield->id] = $customfield;
            }
        }
        //}

        return $customfields;
    }

    /**
     * Returns a list of public custom fields.
     *
     * @return TNP_Profile[]
     * @deprecated since version 7.8.0
     */
    function get_profiles_public() {
        return $this->get_customfields_public();
    }

    /**
     * Return a custom field.
     * @param int $id
     * @return TNP_Profile
     * @deprecated since version 7.8.0
     */
    function get_profile($id) {
        return $this->get_customfield($id);
    }

    /**
     * @param string $language The language for the list labels (it does not affect the lists returned)
     * @return TNP_Profile[]
     * @deprecated since version 7.8.0
     */
    function get_profiles() {
        return $this->get_customfields();
    }

    /**
     * @param string $language The language for the list labels (it does not affect the lists returned)
     * @return TNP_List[]
     */
    function get_lists() {
        static $lists = null;

        if (is_null($lists)) {
            $options = $this->get_options('lists');
            $lists = TNP_List::build($options);
        }

        return $lists;
    }

    /**
     * Lists to be shown on subscription form.
     *
     * @return TNP_List[]
     * @deprecated since version 7.8.0
     */
    function get_lists_for_subscription($language = '') {
        return $this->get_lists_public();
    }

    /**
     * Returns the lists to be shown in the profile page. The list is associative with
     * the list ID as key.
     *
     * @return TNP_List[]
     * @deprecated since version 7.8.0
     */
    function get_lists_for_profile($language = '') {
        return $this->get_lists_public();
    }

    /**
     * Returns the list object or null if not found.
     *
     * @param int $id
     * @return TNP_List
     */
    function get_list($id, $language = '') {
        $lists = $this->get_lists($language);
        if (!isset($lists['' . $id])) {
            return null;
        }

        return $lists['' . $id];
    }

    /**
     * Updates the user last activity timestamp.
     *
     * @global wpdb $wpdb
     * @param TNP_User $user
     */
    function update_user_last_activity($user) {
        global $wpdb;
        if (!$user) {
            return;
        }
        $this->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set last_activity=%d where id=%d limit 1", time(), $user->id));
    }

    function update_user_ip($user, $ip) {
        global $wpdb;
        if (!$user) {
            return;
        }
        if (!$ip) {
            return;
        }
// Only if changed
        $r = $this->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set ip=%s, geo=0 where ip<>%s and id=%d limit 1", $ip, $ip, $user->id));
    }

    /**
     * Finds single style blocks and adds a style attribute to every HTML tag with a class exactly matching the rules in the style
     * block. HTML tags can use the attribute "inline-class" to exact match a style rules if they need a composite class definition.
     *
     * @param string $content
     * @param boolean $strip_style_blocks
     * @return string
     */
    function inline_css($content, $strip_style_blocks = false) {
        $matches = array();
        // "s" skips line breaks
        $styles = preg_match('|<style>(.*?)</style>|s', $content, $matches);
        if (isset($matches[1])) {
            $style = str_replace(array("\n", "\r"), '', $matches[1]);
            $rules = array();
            preg_match_all('|\s*\.(.*?)\{(.*?)\}\s*|s', $style, $rules);
            for ($i = 0; $i < count($rules[1]); $i++) {
                $class = trim($rules[1][$i]);
                $value = trim($rules[2][$i]);
                $value = preg_replace('|\s+|', ' ', $value);
                //$content = str_replace(' class="' . $class . '"', ' class="' . $class . '" style="' . $value . '"', $content);
                $content = str_replace(' inline-class="' . $class . '"', ' style="' . $value . '"', $content);
            }
        }

        if ($strip_style_blocks) {
            return trim(preg_replace('|<style>.*?</style>|s', '', $content));
        } else {
            return $content;
        }
    }

    /**
     * Add to a destination URL the parameters to identify the user, the email and to show
     * an alert message, if required. The parameters are then managed by the [newsletter] shortcode.
     *
     * @param string $url If empty the standard newsletter page URL is used (usually it is empty, but sometime a custom URL has been specified)
     * @param string $message_key The message identifier
     * @param TNP_User|int $user
     * @param TNP_Email|int $email
     * @param string $alert An optional alter message to be shown. Does not work with custom URLs
     * @return string The final URL with parameters
     */
    function build_message_url($url = '', $message_key = '', $user = null, $email = null, $alert = '') {
        $params = 'nm=' . rawurlencode($message_key);
        $language = '';
        if ($user) {
            if (!is_object($user)) {
                $user = $this->get_user($user);
            }

            $params .= '&nk=' . rawurlencode($this->get_user_key($user));

            $language = $this->get_user_language($user);
        }

        if ($email) {
            if (!is_object($email)) {
                $email = $this->get_email($email);
            }
            $params .= '&nek=' . rawurlencode($this->get_email_key($email));
        }

        if ($alert) {
            $alert = wp_strip_all_tags($alert, true);
            $params .= '&alert=' . rawurlencode($alert);
        }

        if (empty($url)) {
            $url = Newsletter::instance()->get_newsletter_page_url($language);
        }

        return self::add_qs($url, $params, false);
    }

    function get_subscribe_url() {
        return $this->build_action_url('s');
    }

    /**
     * Returns the user language IF there is a supported mutilanguage plugin installed.
     * @param TNP_User $user
     * @return string Language code or empty
     */
    function get_user_language($user) {
        if ($user && $this->is_multilanguage()) {
            return $user->language;
        }
        return '';
    }

    /**
     *
     * @global wpdb $wpdb
     * @param string $text
     * @param TNP_User $user
     * @param type $email
     * @return string
     */
    function replace_for_email($text, $user = null, $email = null) {
        global $wpdb;

        if (empty($text)) {
            return $text;
        }

        // When sending email, the subscriber key needs to be the trusted one, since it is sent to the
        // subscriber mailbox, and it can be accessed only by the subscriber.
        $trusted = $user->_trusted ?? true;
        $user->_trusted = true;

        $html = strpos($text, '<p') !== false;
        $home_url = home_url('/');
        Newsletter::instance()->switch_language($user->language);

        $text = apply_filters('newsletter_replace', $text, $user, $email, $html, null);

        $text = $this->replace_url($text, 'blog_url', $home_url);
        $text = $this->replace_url($text, 'home_url', $home_url);

        $text = str_replace('{blog_title}', html_entity_decode(get_bloginfo('name')), $text);
        $text = str_replace('{blog_description}', get_option('blogdescription'), $text);

        if ($email) {
            $text = $this->replace_date($text, $email->send_on);
        }

        $nk = $this->get_user_key($user);
        $token = $user->token;

        $text = str_replace('{email}', esc_html($user->email), $text);

        $name = $this->sanitize_name($user->name);
        if (empty($name)) {
            $text = str_replace(' {name}', '', $text);
            $text = str_replace('{name}', '', $text);
            $text = str_replace(' {first_name}', '', $text);
            $text = str_replace('{first_name}', '', $text);
        } else {
            $text = str_replace('{name}', esc_html($name), $text);
            $text = str_replace('{first_name}', esc_html($name), $text);
        }

        $surname = $this->sanitize_name($user->surname);
        $text = str_replace('{surname}', esc_html($surname), $text);
        $text = str_replace('{last_name}', esc_html($surname), $text);

        $full_name = trim($name . ' ' . $surname);
        if (empty($full_name)) {
            $text = str_replace(' {full_name}', '', $text);
            $text = str_replace('{full_name}', '', $text);
        } else {
            $text = str_replace('{full_name}', esc_html($full_name), $text);
        }

        switch ($user->sex) {
            case 'm': $text = str_replace('{title}', esc_html($this->get_text('title_male', 'form')), $text);
                break;
            case 'f': $text = str_replace('{title}', esc_html($this->get_text('title_female', 'form')), $text);
                break;
            default:
                $text = str_replace('{title}', esc_html($this->get_text('title_none', 'form')), $text);
        }

        for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
            $p = 'profile_' . $i;
            $value = $this->sanitize_user_field($user->$p);
            $text = str_replace('{profile_' . $i . '}', esc_html($value), $text);
        }

        $text = str_replace('{token}', $token, $text);
        $text = str_replace('%7Btoken%7D', $token, $text);
        $text = str_replace('{id}', $user->id, $text);
        $text = str_replace('%7Bid%7D', $user->id, $text);
        $text = str_replace('{ip}', esc_html($user->ip), $text);
        $text = str_replace('{key}', $nk, $text);
        $text = str_replace('%7Bkey%7D', $nk, $text);

        // Links
        $text = $this->replace_url($text, 'subscription_confirm_url', $this->build_action_url('c', $user));
        $text = $this->replace_url($text, 'activation_url', $this->build_action_url('c', $user));
        // To be moved to the "content lock" addon
        $text = $this->replace_url($text, 'unlock_url', $this->build_action_url('ul', $user));

        if ($email) {
            $nek = $this->get_email_key($email);
            $text = str_replace('{email_id}', $email->id, $text);
            $text = str_replace('{email_key}', $nek, $text);
            $text = str_replace('{email_subject}', $email->subject, $text);
            // Deprecated
            $text = str_replace('{subject}', $email->subject, $text);
            $text = $this->replace_url($text, 'email_url', $this->build_action_url('v', $user) . '&id=' . $email->id);
        } else {
            $text = $this->replace_url($text, 'email_url', '#');
        }

        $options = Newsletter::instance()->get_options('info');
        // Do not escape HTML here since it's a free text
        $text = str_replace('{company_address}', wp_kses_post($options['footer_contact']), $text);
        $text = str_replace('{company_name}', wp_kses_post($options['footer_title']), $text);
        $text = str_replace('{company_legal}', wp_kses_post($options['footer_legal']), $text);

        Newsletter::instance()->restore_language();
        $user->_trusted = $trusted;

        return $text;
    }

    /**
     * Replaces every possible Newsletter tag ({...}) in a piece of text or HTML.
     *
     * @global wpdb $wpdb
     * @param string $text
     * @param mixed $user Can be an object, associative array or id
     * @param mixed $email Can be an object, associative array or id
     * @param string $context (set to 'page' when used for the public page)
     * @return type
     */
    function replace($text, $user = null, $email = null, $context = null) {
        global $wpdb;

        if (empty($text))
            return $text;

        if (strpos($text, '<p') !== false) {
            $html = true;
        } else {
            $html = false;
        }

        static $home_url = false;

        if (!$home_url) {
            $home_url = home_url('/');
        }

        if ($user !== null && !is_object($user)) {
            if (is_array($user)) {
                $user = (object) $user;
            } else if (is_numeric($user)) {
                $user = $this->get_user($user);
            } else {
                $user = null;
            }
        }

        if ($email !== null && !is_object($email)) {
            if (is_array($email)) {
                $email = (object) $email;
            } else if (is_numeric($email)) {
                $email = $this->get_email($email);
            } else {
                $email = null;
            }
        }

        if ($user && $user->language) {
            Newsletter::instance()->switch_language($user->language);
        }

        $text = apply_filters('newsletter_replace', $text, $user, $email, $html, $context);

        $text = $this->replace_url($text, 'blog_url', $home_url);
        $text = $this->replace_url($text, 'home_url', $home_url);

        $text = str_replace('{blog_title}', html_entity_decode(get_bloginfo('name')), $text);
        $text = str_replace('{blog_description}', get_option('blogdescription'), $text);

        if ($email) {
            $text = $this->replace_date($text, $email->send_on);
        }

        if ($user) {
            $trusted = $user->_trusted ?? true;
            $nk = $this->get_user_key($user);
            $token = $trusted ? $user->token : md5($user->token);

            $text = str_replace('{email}', $user->email, $text);

            $name = apply_filters('newsletter_replace_name', $user->name, $user);
            $name = $this->sanitize_user_field($name);

            if (empty($name)) {
                $text = str_replace(' {name}', '', $text);
                $text = str_replace('{name}', '', $text);
            } else {
                $text = str_replace('{name}', esc_html($name), $text);
            }

            switch ($user->sex) {
                case 'm': $text = str_replace('{title}', $this->get_text('title_male', 'form'), $text);
                    break;
                case 'f': $text = str_replace('{title}', $this->get_text('title_female', 'form'), $text);
                    break;
                default:
                    $text = str_replace('{title}', $this->get_text('title_none', 'form'), $text);
            }

            $surname = $this->sanitize_user_field($user->surname);
            $text = str_replace('{surname}', esc_html($surname), $text);
            $text = str_replace('{last_name}', esc_html($surname), $text);

            $full_name = esc_html(trim($name . ' ' . $surname));
            if (empty($full_name)) {
                $text = str_replace(' {full_name}', '', $text);
                $text = str_replace('{full_name}', '', $text);
            } else {
                $text = str_replace('{full_name}', $full_name, $text);
            }

            $text = str_replace('{token}', $token, $text);
            $text = str_replace('%7Btoken%7D', $token, $text);
            $text = str_replace('{id}', $user->id, $text);
            $text = str_replace('%7Bid%7D', $user->id, $text);
            $text = str_replace('{ip}', $user->ip, $text);
            $text = str_replace('{key}', $nk, $text);
            $text = str_replace('%7Bkey%7D', $nk, $text);

            for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
                $p = 'profile_' . $i;
                $value = $this->sanitize_user_field($user->$p);
                $text = str_replace('{profile_' . $i . '}', $value, $text);
            }

            $base = (empty($this->options_main['url']) ? get_option('home') : $this->options_main['url']);
            $id_token = '&amp;ni=' . $user->id . '&amp;nt=' . $token;

            $text = $this->replace_url($text, 'subscription_confirm_url', $this->build_action_url('c', $user));
            $text = $this->replace_url($text, 'activation_url', $this->build_action_url('c', $user));

// Obsolete.
            $text = $this->replace_url($text, 'FOLLOWUP_SUBSCRIPTION_URL', self::add_qs($base, 'nm=fs' . $id_token));
            $text = $this->replace_url($text, 'FOLLOWUP_UNSUBSCRIPTION_URL', self::add_qs($base, 'nm=fu' . $id_token));
            $text = $this->replace_url($text, 'UNLOCK_URL', $this->build_action_url('ul', $user));
        } else {
            //$this->logger->debug('Replace without user');
            $text = $this->replace_url($text, 'subscription_confirm_url', '#');
            $text = $this->replace_url($text, 'activation_url', '#');
        }

        if ($email) {
            //$this->logger->debug('Replace with email ' . $email->id);
            $nek = $this->get_email_key($email);
            $text = str_replace('{email_id}', $email->id, $text);
            $text = str_replace('{email_key}', $nek, $text);
            $text = str_replace('{email_subject}', $email->subject, $text);
            // Deprecated
            $text = str_replace('{subject}', $email->subject, $text);
            $text = $this->replace_url($text, 'email_url', $this->build_action_url('v', $user) . '&id=' . $email->id);
        } else {
            //$this->logger->debug('Replace without email');
            $text = $this->replace_url($text, 'email_url', '#');
        }

        // Company info
        // TODO: Move to another module
        $options = Newsletter::instance()->get_options('info');
        $text = str_replace('{company_address}', $options['footer_contact'], $text);
        $text = str_replace('{company_name}', $options['footer_title'], $text);
        $text = str_replace('{company_legal}', $options['footer_legal'], $text);

        if ($user && $user->language) {
            Newsletter::instance()->restore_language();
        }
        return $text;
    }

    function replace_date($text, $timestamp = 0) {
        if (!$timestamp) {
            $timestamp = time();
        }

        $timestamp += (int) (get_option('gmt_offset') * 3600);

        $text = str_replace('{date}', date_i18n(get_option('date_format'), $timestamp), $text);

        // Date processing
        $x = 0;
        while (($x = strpos($text, '{date_', $x)) !== false) {
            $y = strpos($text, '}', $x);
            if ($y === false) {
                continue;
            }
            $f = substr($text, $x + 6, $y - $x - 6);
            $text = substr($text, 0, $x) . date_i18n($f, $timestamp) . substr($text, $y + 1);
        }
        return $text;
    }

    function replace_url($text, $tag, $url) {
        static $home = false;
        if (!$home) {
            $home = trailingslashit(home_url());
        }
        $tag_lower = strtolower($tag);
        $text = str_replace('http://{' . $tag_lower . '}', $url, $text);
        $text = str_replace('https://{' . $tag_lower . '}', $url, $text);
        $text = str_replace($home . '{' . $tag_lower . '}', $url, $text);
        $text = str_replace($home . '%7B' . $tag_lower . '%7D', $url, $text);
        $text = str_replace('{' . $tag_lower . '}', $url, $text);
        $text = str_replace('%7B' . $tag_lower . '%7D', $url, $text);

        $url_encoded = rawurlencode($url);
        $text = str_replace('%7B' . $tag_lower . '_encoded%7D', $url_encoded, $text);
        $text = str_replace('{' . $tag_lower . '_encoded}', $url_encoded, $text);

// for compatibility
        $text = str_replace($home . $tag, $url, $text);

        return $text;
    }

    public static function antibot_form_check($captcha = false) {

        if (is_user_logged_in()) {
            return true;
        }

        if (defined('NEWSLETTER_ANTIBOT') && !NEWSLETTER_ANTIBOT) {
            return true;
        }

        if (strtolower($_SERVER['REQUEST_METHOD']) != 'post') {
            return false;
        }

        if (!isset($_POST['ts']) || time() - $_POST['ts'] > 60) {
            return false;
        }

        // Cookie
        if (!isset($_COOKIE['tnpab'])) {
            return false;
        }

        if ($captcha) {
            $n1 = (int) $_POST['n1'];
            if (empty($n1)) {
                return false;
            }
            $n2 = (int) $_POST['n2'];
            if (empty($n2)) {
                return false;
            }
            $n3 = (int) $_POST['n3'];
            if ($n1 + $n2 != $n3) {
                return false;
            }
        }

        return true;
    }

    public static function request_to_antibot_form($submit_label = 'Continue...', $captcha = false) {
        require __DIR__ . '/antibot-subscription.php';
        die();
    }

    public static function antibot_subscription($submit_label = 'Continue...', $captcha = false) {
        require __DIR__ . '/antibot-subscription.php';
        die();
    }

    public static function antibot_unsubscription($submit_label = 'Continue...', $captcha = false) {
        require __DIR__ . '/antibot-unsubscription.php';
        die();
    }

    static function extract_body($html) {
        $x = stripos($html, '<body');
        if ($x !== false) {
            $x = strpos($html, '>', $x);
            $y = strpos($html, '</body>');
            return substr($html, $x + 1, $y - $x - 1);
        } else {
            return $html;
        }
    }

    /** Returns a percentage as string */
    static function percent($value, $total) {
        if ($total == 0)
            return '-';
        return sprintf("%.2f", $value / $total * 100) . '%';
    }

    /** Returns a percentage as integer value */
    static function percentValue($value, $total) {
        if ($total == 0)
            return 0;
        return round($value / $total * 100);
    }

    /**
     * Takes in a variable and checks if object, array or scalar and return the integer representing
     * a database record id.
     *
     * @param mixed $var
     * @return in
     */
    static function to_int_id($var) {
        if (is_object($var)) {
            return (int) $var->id;
        }
        if (is_array($var)) {
            return (int) $var['id'];
        }
        return (int) $var;
    }

    static function to_array($text) {
        $text = trim($text);
        if (empty($text)) {
            return array();
        }
        $text = preg_split("/\\r\\n/", $text);
        $text = array_map('trim', $text);
        $text = array_map('strtolower', $text);
        $text = array_filter($text);

        return $text;
    }

    static function sanitize_ip($ip) {
        if (empty($ip)) {
            return '';
        }
        $ip = preg_replace('/[^0-9a-fA-F:., ]/', '', trim($ip));
        if (strlen($ip) > 50)
            $ip = substr($ip, 0, 50);

        // When more than one IP is present due to firewalls, proxies, and so on. The first one should be the origin.
        if (strpos($ip, ',') !== false) {
            list($ip, $tail) = explode(',', $ip, 2);
        }
        return $ip;
    }

    static function get_remote_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return self::sanitize_ip($ip);
    }

    static function get_signature($text) {
        $key = NewsletterStatistics::instance()->options['key'];
        return md5($text . $key);
    }

    static function check_signature($text, $signature) {
        if (empty($signature)) {
            return false;
        }
        $key = NewsletterStatistics::instance()->options['key'];
        return md5($text . $key) === $signature;
    }

    static function get_home_url() {
        static $url = false;
        if (!$url) {
            $url = home_url('/');
        }
        return $url;
    }

    static function clean_eol($text) {
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);
        $text = str_replace("\n", "\r\n", $text);
        return $text;
    }

    function set_current_language($language) {
        $this->switch_language($language);
    }

    function get_default_language() {
        if (class_exists('SitePress')) {
            return $current_language = apply_filters('wpml_current_language', '');
        } else if (function_exists('pll_default_language')) {
            return pll_default_language();
        } else if (class_exists('TRP_Translate_Press')) {
// TODO: Find the default language
        }
        return '';
    }

    function is_default_language() {
        return $this->get_current_language() == $this->get_default_language();
    }

    protected function generate_admin_notification_message($user) {

        $message = file_get_contents(__DIR__ . '/notification.html');

        $message = $this->replace($message, $user);
        $message = str_replace('{user_admin_url}', admin_url('admin.php?page=newsletter_users_edit&id=' . $user->id), $message);

        return $message;
    }

    protected function generate_admin_notification_subject($subject) {
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        return '[' . $blogname . '] ' . $subject;
    }

    /**
     * For compatibility.
     *
     * @deprecated since version 7.8.0
     */
    function is_admin_page() {
        NewsletterAdmin::instance()->is_admin_page();
    }
}

/**
 * Kept for compatibility.
 *
 * @param type $post_id
 * @param type $size
 * @param type $alternative
 * @return type
 */
function nt_post_image($post_id = null, $size = 'thumbnail', $alternative = null) {
    return NewsletterModule::get_post_image($post_id, $size, $alternative);
}

function newsletter_get_post_image($post_id = null, $size = 'thumbnail', $alternative = null) {
    echo NewsletterModule::get_post_image($post_id, $size, $alternative);
}

/**
 * Accepts a post or a post ID.
 *
 * @param WP_Post $post
 */
function newsletter_the_excerpt($post, $words = 30) {
    $post = get_post($post);
    $excerpt = $post->post_excerpt;
    if (empty($excerpt)) {
        $excerpt = $post->post_content;
        $excerpt = strip_shortcodes($excerpt);
        $excerpt = wp_strip_all_tags($excerpt, true);
    }
    echo '<p>' . wp_trim_words($excerpt, $words) . '</p>';
}
