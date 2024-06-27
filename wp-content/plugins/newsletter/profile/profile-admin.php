<?php

defined('ABSPATH') || exit;

class NewsletterProfileAdmin extends NewsletterModuleAdmin {

    static $instance;

    /**
     * @return NewsletterProfileAdmin
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        parent::__construct('profile');
        add_filter('display_post_states', [$this, 'hook_display_post_states'], 10, 2);
    }

    function admin_menu() {
        $this->add_admin_page('index', __('Profile', 'newsletter'));
    }

    function hook_display_post_states($post_states, $post) {

        $for = [];
        if ($this->is_multilanguage()) {
            $languages = $this->get_languages();
            foreach ($languages as $id => $name) {
                $page_id = $this->get_option('page_id', '', $id);
                if ($page_id == $post->ID) {
                    $for[] = $name;
                }
            }
            if ($post->ID == $this->get_main_option('page_id')) {
                $for[] = 'All languages fallback';
            }
            if ($for) {
                $post_states[] = __('Newsletter custom profile page, keep public and published', 'newsletter')
                        . ' - ' . esc_html(implode(', ', $for));
            }
        } else {

            if ($post->ID == $this->get_main_option('page_id')) {
                $post_states[] = __('Newsletter custom profile page, keep public and published', 'newsletter');
            }
        }

        return $post_states;
    }


}

