<?php

defined('ABSPATH') || exit;

class NewsletterSubscriptionAdmin extends NewsletterModuleAdmin {

    static $instance;

    /**
     * @return NewsletterSubscriptionAdmin
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        parent::__construct('subscription');

        add_action('admin_init', [$this, 'hook_admin_init']);
        add_filter('display_post_states', [$this, 'hook_display_post_states'], 10, 2);
    }

    function hook_admin_init() {
        if (function_exists('register_block_type')) {
            // Add custom blocks to Gutenberg
            wp_register_script('tnp-blocks', plugins_url('newsletter') . '/includes/tnp-blocks.js', array('wp-block-editor', 'wp-blocks', 'wp-element', 'wp-components'), NEWSLETTER_VERSION);
            register_block_type('tnp/minimal', array('editor_script' => 'tnp-blocks'));
        }
    }

    function admin_menu() {

//        $this->add_menu_page('options', __('Subscription', 'newsletter'));
//        $this->add_menu_page('lists', __('Lists', 'newsletter'));

        $this->add_admin_page('form', __('Subscription', 'newsletter'));
        $this->add_admin_page('profile', __('Subscription', 'newsletter'));
        $this->add_admin_page('antispam', __('Security', 'newsletter'));
        $this->add_admin_page('forms', __('Forms', 'newsletter'));
        $this->add_admin_page('sources', __('Sources', 'newsletter'));
        $this->add_admin_page('inject', __('Injection', 'newsletter'));
        $this->add_admin_page('popup', __('Popup', 'newsletter'));
        $this->add_admin_page('shortcodes', __('Shortcodes', 'newsletter'));
        $this->add_admin_page('template', __('Template', 'newsletter'));
        $this->add_admin_page('index', __('Overview', 'newsletter'));
        $this->add_admin_page('customfields', __('Custom fields', 'newsletter'));
        $this->add_admin_page('welcome', 'Welcome email');
        $this->add_admin_page('autoresponder', 'Welcome series');
        $this->add_admin_page('debug', 'Debug');
    }

    function get_form_options() {
        return $this->get_options('form');
    }

    function get_form_option($key) {
        return $this->get_option($key, 'form');
    }

    function get_form_text($key) {
        return $this->get_text($key, 'form');
    }

    function hook_display_post_states($post_states, $post) {

        $for = [];
        if ($this->is_multilanguage()) {
            $languages = $this->get_languages();
            foreach ($languages as $id => $name) {
                $page_id = $this->get_option('confirmed_id', '', $id);
                if ($page_id == $post->ID) {
                    $for[] = $name;
                }
            }
            if ($post->ID == $this->get_main_option('confirmed_id')) {
                $for[] = 'All languages fallback';
            }
            if ($for) {
                $post_states[] = __('Newsletter custom profile page, keep public and published', 'newsletter')
                        . ' - ' . esc_html(implode(', ', $for));
            }
        } else {

            if ($post->ID == $this->get_main_option('confirmed_id')) {
                $post_states[] = __('Newsletter custom welcome page, keep public and published', 'newsletter');
            }
        }

        return $post_states;
    }

}
