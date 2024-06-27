<?php

/**
 * User by add-ons as base-class.
 */
class NewsletterAddon {

    var $logger;
    var $admin_logger;
    var $name;
    var $options;
    var $version;
    var $labels;
    var $menu_priority = 100;

    public function __construct($name, $version = '0.0.0', $dir = '') {
        $this->name = $name;
        $this->version = $version;
        if (is_admin()) {
            $old_version = get_option('newsletter_' . $name . '_version');
            if ($version !== $old_version) {
                $this->upgrade($old_version === false);
                update_option('newsletter_' . $name . '_version', $version, false);
            }
        }
        add_action('newsletter_init', array($this, 'init'));
        //Load translations from specific addon /languages/ directory
        load_plugin_textdomain('newsletter-' . $this->name, false, 'newsletter-' . $this->name . '/languages/');

        if (is_admin() && !wp_next_scheduled('newsletter_addon_' . $this->name)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'weekly', 'newsletter_addon_' . $this->name);
        }

        add_action('newsletter_addon_' . $this->name, [$this, 'weekly_check']);

        if ($dir) {
            register_deactivation_hook($dir . '/' . $this->name . '.php', [$this, 'deactivate']);
        }
    }

    /**
     * Method to be overridden and invoked on version change or on first install.
     *
     * @param bool $first_install
     */
    function upgrade($first_install = false) {

    }

    /**
     * Method to be overridden to initialize the add-on. It is invoked when Newsletter
     * fires the <code>newsletter_init</code> event.
     */
    function init() {
        if (is_admin()) {
            if ($this->is_allowed()) {
                add_action('admin_menu', [$this, 'admin_menu'], $this->menu_priority);
                if (method_exists($this, 'settings_menu')) {
                    add_filter('newsletter_menu_settings', [$this, 'settings_menu']);
                }
                if (method_exists($this, 'subscribers_menu')) {
                    add_filter('newsletter_menu_subscribers', [$this, 'subscribers_menu']);
                }
            }
        }
    }

    function weekly_check() {
        $logger = $this->get_logger();
        $logger->info('Weekly check for ' . $this->name);
    }

    function deactivate() {
        $logger = $this->get_logger();
        $logger->info($this->name . ' deactivated');

        // The periodic check
        wp_clear_scheduled_hook('newsletter_addon_' . $this->name);
    }

    function admin_menu() {

    }

//    function settings_menu($entries) {
//    }
//    function subscribers_menu($entries) {
//    }

    function get_current_language() {
        return Newsletter::instance()->get_current_language();
    }

    function is_all_languages() {
        return empty(NewsletterAdmin::instance()->language());
    }

    function is_allowed() {
        return Newsletter::instance()->is_allowed();
    }

    function get_languages() {
        return Newsletter::instance()->get_languages();
    }

    function is_multilanguage() {
        return Newsletter::instance()->is_multilanguage();
    }

    /**
     * General logger for this add-on.
     *
     * @return NewsletterLogger
     */
    function get_logger() {
        if (!$this->logger) {
            $this->logger = new NewsletterLogger($this->name);
        }
        return $this->logger;
    }

    /**
     * Specific logger for administrator actions.
     *
     * @return NewsletterLogger
     */
    function get_admin_logger() {
        if (!$this->admin_logger) {
            $this->admin_logger = new NewsletterLogger($this->name . '-admin');
        }
        return $this->admin_logger;
    }

    /**
     * Loads and prepares the options. It can be used to late initialize the options to save some resources on
     * add-ons which do not need to do something on each page load.
     */
    function setup_options() {
        if ($this->options) {
            return;
        }
        $this->options = $this->get_option_array('newsletter_' . $this->name, []);
        if (!is_array($this->options)) {
            $this->options = [];
        }
    }

    function get_option_array($name) {
        $opt = get_option($name, []);
        if (!is_array($opt)) {
            return [];
        }
        return $opt;
    }

    /**
     * Retrieve the stored options, merged with the specified language set.
     *
     * @param string $language
     * @return array
     */
    function get_options($language = '') {
        if ($language) {
            return array_merge($this->get_option_array('newsletter_' . $this->name), $this->get_option_array('newsletter_' . $this->name . '_' . $language));
        } else {
            return $this->get_option_array('newsletter_' . $this->name);
        }
    }

    /**
     * Saved the options under the correct keys and update the internal $options
     * property.
     * @param array $options
     */
    function save_options($options, $language = '') {
        if ($language) {
            update_option('newsletter_' . $this->name . '_' . $language, $options);
        } else {
            update_option('newsletter_' . $this->name, $options);
            $this->options = $options;
        }
    }

    function merge_defaults($defaults) {
        $options = get_option('newsletter_' . $this->name, []);
        $options = array_merge($defaults, $options);
        $this->save_options($options);
    }

    /**
     *
     */
    function setup_labels() {
        if (!$this->labels) {
            $labels = [];
        }
    }

    function get_label($key) {
        if (!$this->options)
            $this->setup_options();

        if (!empty($this->options[$key])) {
            return $this->options[$key];
        }

        if (!$this->labels)
            $this->setup_labels();

        // We assume the required key is defined. If not there is an error elsewhere.
        return $this->labels[$key];
    }

    /**
     * Equivalent to $wpdb->query() but logs the event in case of error.
     *
     * @global wpdb $wpdb
     * @param string $query
     */
    function query($query) {
        global $wpdb;

        $r = $wpdb->query($query);
        if ($r === false) {
            $logger = $this->get_logger();
            $logger->fatal($query);
            $logger->fatal($wpdb->last_error);
        }
        return $r;
    }

    function get_results($query) {
        global $wpdb;
        $r = $wpdb->get_results($query);
        if ($r === false) {
            $this->logger->fatal($query);
            $this->logger->fatal($wpdb->last_error);
        }
        return $r;
    }

    function get_row($query) {
        global $wpdb;
        $r = $wpdb->get_row($query);
        if ($r === false) {
            $this->logger->fatal($query);
            $this->logger->fatal($wpdb->last_error);
        }
        return $r;
    }

    function get_user($id_or_email) {
        return Newsletter::instance()->get_user($id_or_email);
    }

    function show_email_status_label($email) {
        return NewsletterAdmin::instance()->show_email_status_label($email);
    }

    function send_test_email($email, $controls) {
        NewsletterEmailsAdmin::instance()->send_test_email($email, $controls);
    }
}

/**
 * Used by mailer add-ons as base-class. Some specific options collected by the mailer
 * are interpreted automatically.
 *
 * They are:
 *
 * `enabled` if not empty it means the mailer is active and should be registered
 *
 * The options are set up in the constructor, there is no need to setup them later.
 */
class NewsletterMailerAddon extends NewsletterAddon {

    var $enabled = false;
    var $menu_title = null;
    var $menu_description = null;
    var $menu_slug = null;
    var $dir = '';
    var $index_page = null;
    var $logs_page = null;

    function __construct($name, $version = '0.0.0', $dir = '') {
        parent::__construct($name, $version, $dir);
        $this->dir = $dir;
        $this->setup_options();
        $this->enabled = !empty($this->options['enabled']);
        $this->menu_slug = $this->name;
    }

    /**
     * This method must be called as `parent::init()` is overridden.
     */
    function init() {
        parent::init();
        add_action('newsletter_register_mailer', function () {
            if ($this->enabled) {
                Newsletter::instance()->register_mailer($this->get_mailer());
            }
        });

        if (is_admin() && !empty($this->menu_title) && !empty($this->dir) && current_user_can('administrator')) {
            $this->index_page = 'newsletter_' . $this->menu_slug . '_index';
            $this->logs_page = 'newsletter_' . $this->menu_slug . '_logs';
            add_action('admin_menu', [$this, 'hook_admin_menu'], 101);
            add_filter('newsletter_menu_settings', [$this, 'hook_newsletter_menu_settings']);
        }
    }

    function deactivate() {
        parent::deactivate();

        // For delivery services without webkooks
        wp_clear_scheduled_hook('newsletter_' . $this->name . '_bounce');
    }

    function hook_newsletter_menu_settings($entries) {
        $entries[] = ['label' => $this->menu_title, 'url' => '?page=' . $this->index_page];
        return $entries;
    }

    function hook_admin_menu() {

        add_submenu_page('newsletter_main_index', $this->menu_title, '<span class="tnp-side-menu">' . esc_html($this->menu_title) . '</span>', 'manage_options', $this->index_page,
                function () {
                    /** @since 8.4.0 */
                    require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
                    $controls = new NewsletterControls();
                    if (file_exists($this->dir . '/admin/index.php')) {
                        require $this->dir . '/admin/index.php';
                    } else {
                        require $this->dir . '/index.php';
                    }
                }
        );

        if (file_exists($this->dir . '/admin/logs.php')) {
            add_submenu_page('admin.php', __('Logs', 'newsletter'), __('Logs', 'newsletter'), 'manage_options', $this->logs_page,
                    function () {
                        /** @since 8.4.0 */
                        require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
                        $controls = new NewsletterControls();
                        require $this->dir . '/admin/logs.php';
                    }
            );
        }
    }

    function set_warnings($controls) {
//        if (!$this->enabled) {
//            $controls->warnings[] = 'Enable to send with this service.';
//        }

        $current_mailer = Newsletter::instance()->get_mailer();
        if ($current_mailer && $this->enabled && get_class($current_mailer) != get_class($this->get_mailer())) {
            $controls->warnings[] = 'Another delivery addon is active: ' . esc_html($current_mailer->get_description());
        }

        if ($this->enabled && class_exists('NewsletterBounce')) {
            $controls->warnings[] = 'The Bounce addon is active and should be disabled (bounces are managed by this addon)';
        }
    }

    function get_status_badge() {
        if ($this->enabled) {
            return '<span class="tnp-badge-green">' . esc_html__('Enabled', 'newsletter') . '</span>';
        } else {
            return '<span class="tnp-badge-orange">' . esc_html__('Disabled', 'newsletter') . '</span>';
        }
    }

    /** @since 8.4.0 */
    function echo_status_badge() {
        if ($this->enabled) {
            echo '<span class="tnp-badge-green">', esc_html__('Enabled', 'newsletter'), '</span>';
        } else {
            echo '<span class="tnp-badge-orange">', esc_html__('Disabled', 'newsletter'), '</span>';
        }
    }

    function get_title() {
        return esc_html($this->menu_title) . $this->get_status_badge();
    }

    /** @since 8.4.0 */
    function echo_title() {
        echo esc_html($this->menu_title);
        $this->echo_status_badge();
    }

    function set_bounced($email) {
        global $wpdb;
        $logger = $this->get_logger();
        $logger->info($email . ' bounced');
        $this->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set status=%s where email=%s limit 1", TNP_User::STATUS_BOUNCED, $email));
    }

    function set_complained($email) {
        global $wpdb;
        $logger = $this->get_logger();
        $logger->info($email . ' complained');
        $this->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set status=%s where email=%s limit 1", TNP_User::STATUS_COMPLAINED, $email));
    }

    function set_unsubscribed($email) {
        global $wpdb;
        $logger = $this->get_logger();
        $logger->info($email . ' unsubscribed');
        $this->query($wpdb->prepare("update " . NEWSLETTER_USERS_TABLE . " set status=%s where email=%s limit 1", TNP_User::STATUS_UNSUBSCRIBED, $email));
    }

    /**
     * Must return an implementation of NewsletterMailer.
     * @return NewsletterMailer
     */
    function get_mailer() {
        return null;
    }

    function get_last_run() {
        return get_option('newsletter_' . $this->name . '_last_run', 0);
    }

    function save_last_run($time) {
        update_option('newsletter_' . $this->name . '_last_run', $time);
    }

    function save_options($options, $language = '') {
        parent::save_options($options, $language);
        $this->enabled = !empty($options['enabled']);
    }

    /**
     * Returns a TNP_Mailer_Message built to send a test message to the <code>$to</code>
     * email address.
     *
     * @param string $to
     * @param string $subject
     * @return TNP_Mailer_Message
     */
    static function get_test_message($to, $subject = '', $type = '') {
        $message = new TNP_Mailer_Message();
        $message->to = $to;
        $message->to_name = '';
        if (empty($type) || $type == 'html') {
            $message->body = file_get_contents(NEWSLETTER_DIR . '/includes/test-message.html');
            $message->body = str_replace('{plugin_url}', Newsletter::plugin_url(), $message->body);
        }

        if (empty($type) || $type == 'text') {
            $message->body_text = 'This is the TEXT version of a test message. You should see this message only if you email client does not support the rich text (HTML) version.';
        }

        //$message->headers['X-Newsletter-Email-Id'] = '0';

        if (empty($subject)) {
            $message->subject = '[' . get_option('blogname') . '] Test message from Newsletter (' . date(DATE_ISO8601) . ')';
        } else {
            $message->subject = $subject;
        }

        if ($type) {
            $message->subject .= ' - ' . $type . ' only';
        }

        $message->from = Newsletter::instance()->get_sender_email();
        $message->from_name = Newsletter::instance()->get_sender_name();
        $message->headers['X-Newsletter'] = 'test';
        return $message;
    }

    /**
     * Returns a set of test messages to be sent to the specified email address. Used for
     * turbo mode tests. Each message has a different generated subject.
     *
     * @param string $to The destination mailbox
     * @param int $count Number of message objects to create
     * @return TNP_Mailer_Message[]
     */
    function get_test_messages($to, $count, $type = '') {
        $messages = array();
        for ($i = 0; $i < $count; $i++) {
            $messages[] = self::get_test_message($to, '[' . get_option('blogname') . '] Test message ' . ($i + 1) . ' from Newsletter (' . date(DATE_ISO8601) . ')', $type);
        }
        return $messages;
    }
}

class NewsletterFormManagerAddon extends NewsletterAddon {

    var $menu_title = null;
    var $menu_description = null;
    var $menu_slug = null;
    var $index_page = null;
    var $edit_page = null;
    var $welcome_page = null;
    var $logs_page = null;
    var $dir = '';
    var $forms = null; // For caching

    function __construct($name, $version, $dir, $menu_slug = null) {
        parent::__construct($name, $version, $dir);
        $this->dir = $dir;
        $this->menu_slug = $menu_slug;
        if (empty($this->menu_slug)) {
            $this->menu_slug = $this->name;
        }
        $this->setup_options();
    }

    function init() {
        parent::init();

        if (is_admin() && $this->is_allowed()) {

            $this->index_page = 'newsletter_' . $this->menu_slug . '_index';
            $this->edit_page = 'newsletter_' . $this->menu_slug . '_edit';
            $this->welcome_page = 'newsletter_' . $this->menu_slug . '_welcome';
            $this->logs_page = 'newsletter_' . $this->menu_slug . '_logs';

            // Auto add a menu entry
            if (!empty($this->menu_title) && !empty($this->dir)) {
                add_action('admin_menu', [$this, 'hook_admin_menu'], 101);
                add_filter('newsletter_menu_subscription', [$this, 'hook_newsletter_menu_subscription']);
            }
            add_filter('newsletter_lists_notes', array($this, 'hook_newsletter_lists_notes'), 10, 2);
        }
    }

    function hook_newsletter_lists_notes($notes, $list_id) {
        if (!$this->forms) {
            $this->forms = $this->get_forms();
        }
        foreach ($this->forms as $form) {
            $ok = false;
            $form_options = $this->get_form_options($form->id);
            // Too many years of development
            if (!empty($form_options['lists']) && is_array($form_options['lists']) && in_array($list_id, $form_options['lists'])) {
                $ok = true;
            } elseif (!empty($form_options['preferences_' . $list_id])) {
                $ok = true;
            } elseif (!empty($form_options['preferences']) && is_array($form_options['preferences']) && in_array($list_id, $form_options['preferences'])) {
                $ok = true;
            }
            if ($ok) {
                $notes[] = 'Linked to form "' . $form->title . '"';
            }
        }

        return $notes;
    }

    function get_default_subscription($form_options) {
        $subscription = NewsletterSubscription::instance()->get_default_subscription();
        if (!empty($form_options['welcome_email'])) {
            if ($form_options['welcome_email'] == '1') {
                $subscription->welcome_email_id = (int) $form_options['welcome_email_id'];
            } else {
                $subscription->welcome_email_id = -1;
            }
        }
        if (!empty($form_options['status'])) {
            $subscription->optin = $form_options['status'];
        }

        if (!empty($form_options['lists'])) {
            $subscription->data->add_lists($form_options['lists']);
        }

        return $subscription;
    }

    function hook_newsletter_menu_subscription($entries) {
        $entries[] = ['label' => $this->menu_title, 'url' => '?page=' . $this->index_page];
        return $entries;
    }

    function hook_admin_menu() {
        add_submenu_page('newsletter_main_index', $this->menu_title, '<span class="tnp-side-menu">' . $this->menu_title . '</span>', 'exist', $this->index_page,
                function () {
                    require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
                    $controls = new NewsletterControls();
                    require $this->dir . '/admin/index.php';
                }
        );
        add_submenu_page('admin.php', $this->menu_title, '<span class="tnp-side-menu">' . $this->menu_title . '</span>', 'exist', $this->edit_page,
                function () {
                    require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
                    $controls = new NewsletterControls();

                    $form = $this->get_form(sanitize_key($_GET['id'] ?? ''));
                    if (!$form) {
                        echo 'Form not found';
                        return;
                    }
                    require $this->dir . '/admin/edit.php';
                }
        );

        if (file_exists($this->dir . '/admin/welcome.php')) {
            add_submenu_page('admin.php', $this->menu_title, '<span class="tnp-side-menu">' . $this->menu_title . '</span>', 'exist', $this->welcome_page,
                    function () {
                        /** @since 8.3.9 */
                        require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
                        $controls = new NewsletterControls();

                        /** @since 8.3.9 */
                        $form = $this->get_form(sanitize_key($_GET['id'] ?? ''));
                        if (!$form) {
                            echo 'Form not found';
                            return;
                        }

                        require $this->dir . '/admin/welcome.php';
                    }
            );
        }

        if (file_exists($this->dir . '/admin/logs.php')) {
            add_submenu_page('admin.php', $this->menu_title, $this->menu_title, 'exist', $this->logs_page,
                    function () {
                        require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
                        $controls = new NewsletterControls();

                        $form = $this->get_form(sanitize_key($_GET['id'] ?? ''));
                        if (!$form) {
                            echo 'Form not found';
                            return;
                        }
                        require $this->dir . '/admin/logs.php';
                    }
            );
        }
    }

    /**
     * Returns a lists of representations of forms available in the plugin subject of integration.
     * Usually the $fields is not set up on returned objects.
     * Must be implemented.
     *
     * @return TNP_FormManager_Form[] List of forms by 3rd party plugin
     */
    function get_forms() {
        return [];
    }

    /**
     * Build a form general representation of a real form from a form manager plugin extracting
     * only the data required to integrate. The form id is domain of the form manager plugin, so it can be
     * anything.
     * Must be implemented.
     *
     * @param mixed $form_id
     * @return TNP_FormManager_Form
     */
    function get_form($form_id) {
        return null;
    }

    /**
     * Saves the form mapping and integration settings.
     * @param mixed $form_id
     * @param array $data
     */
    public function save_form_options($form_id, $data) {
        update_option('newsletter_' . $this->name . '_' . $form_id, $data, false);
    }

    /**
     * Gets the form mapping and integration settings. Returns an empty array if the dataset is missing.
     * @param mixed $form_id
     * @return array
     */
    public function get_form_options($form_id) {
        return Newsletter::get_option_array('newsletter_' . $this->name . '_' . $form_id);
    }
}

class TNP_FormManager_Form {

    var $id = null;
    var $title = '';
    var $fields = [];
    var $connected = false;
}
