<?php

/**
 * Addon base class for administration pages of addons
 */
class NewsletterAddonAdmin {

    var $logger;
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

    }


    /**
     * Method to be overridden to initialize the add-on. It is invoked when Newsletter
     * fires the <code>newsletter_init</code> event.
     */
    function init() {
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
            $this->logger = new NewsletterLogger($this->name . '-admin');
        }
        return $this->logger;
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

