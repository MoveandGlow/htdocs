<?php

defined('ABSPATH') || exit;

class NewsletterMainAdmin extends NewsletterModuleAdmin {

    static $instance;

    /**
     * @return NewsletterMainAdmin
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        parent::__construct('main');
        add_filter('display_post_states', [$this, 'hook_display_post_states'], 10, 2);
    }

    function wp_loaded() {
        if ($this->is_admin_page()) {

            // Dismiss messages
            if (isset($_GET['dismiss'])) {
                $dismissed = get_option('newsletter_dismissed');
                if (!is_array($dismissed)) {
                    $dismissed = [];
                }
                $dismissed[$_GET['dismiss']] = 1;
                update_option('newsletter_dismissed', $dismissed);
                wp_safe_redirect(remove_query_arg(['dismiss', 'noheader', 'debug']));
                exit();
            }

            // Dismiss news
            if (isset($_GET['news'])) {
                $dismissed = get_option('newsletter_news_dismissed');
                if (!is_array($dismissed)) {
                    $dismissed = [];
                }
                $dismissed[] = strip_tags($_GET['news']);
                update_option('newsletter_news_dismissed', $dismissed);
                wp_safe_redirect(remove_query_arg('news'));
                exit();
            }
        }
    }

    function admin_notices() {
        if ($this->get_option('debug')) {
            echo '<div class="notice notice-warning"><p>The Newsletter plugin is in <strong>debug mode</strong>. When done change it on Newsletter <a href="admin.php?page=newsletter_main_main"><strong>main settings</strong></a>. Do not keep the debug mode active on production sites.</p></div>';
        }
    }

    function admin_menu() {
        //$this->add_menu_page('index', __('Dashboard', 'newsletter'));
        $this->add_admin_page('info', esc_html__('Company info', 'newsletter'));

        if (current_user_can('administrator')) {
            $this->add_admin_page('welcome', esc_html__('Welcome', 'newsletter'));
            //$this->add_menu_page('main', __('Settings', 'newsletter'));
            // Pages not on menu
            $this->add_admin_page('cover', 'Cover');
            //$this->add_admin_page('setup', 'Setup');
            $this->add_admin_page('flow', 'Flow');
        }
    }

    /**
     * Special entry for the addons management.
     *
     */
    function admin_after_menu() {
        if (!class_exists('NewsletterExtensions')) {
            $this->add_menu_page('extensions', '<span style="color:#27AE60; font-weight: bold;">' . __('Addons', 'newsletter') . '</span>');
        } else {
            // Grants access to the original page
            $this->add_admin_page('extensions', __('Addons', 'newsletter'));
        }

//        if (!class_exists('NewsletterAutomated') && !class_exists('NewsletterAutoresponder')) {
//            $this->add_menu_page('automation', 'Automation <span class="tnp-sidemenu-badge">Pro</span>');
//        }

        if (NEWSLETTER_DEBUG || !class_exists('NewsletterAutomated')) {
            $this->add_menu_page('automated', 'Automated <span class="tnp-sidemenu-badge">Pro</span>');
            $this->add_admin_page('automatedindex', 'Automated');
            $this->add_admin_page('automatededit', 'Automated edit');
            $this->add_admin_page('automatednewsletters', 'Automated newsletters');
            $this->add_admin_page('automatedtemplate', 'Automated template');
        }

        if (NEWSLETTER_DEBUG || !class_exists('NewsletterAutoresponder')) {
            $this->add_menu_page('autoresponder', 'Autoresponder <span class="tnp-sidemenu-badge">Pro</span>');
            $this->add_admin_page('autoresponderindex', 'Autoresponder');
            $this->add_admin_page('autoresponderedit', 'Automated edit');
            $this->add_admin_page('autorespondermessages', 'Automated newsletters');
            $this->add_admin_page('autoresponderstatistics', 'Automated template');
        }
    }

    function hook_display_post_states($post_states, $post) {

        $for = [];
        if ($this->is_multilanguage()) {
            $languages = $this->get_languages();
            foreach ($languages as $id => $name) {
                $page_id = $this->get_option('page', '', $id);
                if ($page_id == $post->ID) {
                    $for[] = $name;
                }
            }
            if ($post->ID == $this->get_main_option('page')) {
                $for[] = 'All languages fallback';
            }
            if ($for) {
                $post_states[] = __('Newsletter public page, keep public and published', 'newsletter')
                        . ' - ' . esc_html(implode(', ', $for));
            }
        } else {

            if ($post->ID == $this->get_main_option('page')) {
                $post_states[] = __('Newsletter public page, keep public and published', 'newsletter');
            }
        }

        return $post_states;
    }

    function get_news() {
        $news = $this->get_option_array('newsletter_news');
        $updated = (int) get_option('newsletter_news_updated');
        if ($updated > time() - DAY_IN_SECONDS) {

        } else {
            // Introduce asynch...
            if (NEWSLETTER_DEBUG) {
                $url = "http://www.thenewsletterplugin.com/wp-content/news-test.json?ver=" . NEWSLETTER_VERSION;
            } else {
                $url = "http://www.thenewsletterplugin.com/wp-content/news.json?ver=" . NEWSLETTER_VERSION;
            }
            $response = wp_remote_get($url);
            if (is_wp_error($response)) {
                update_option('newsletter_news_updated', time());
                return [];
            }
            if (wp_remote_retrieve_response_code($response) !== 200) {
                update_option('newsletter_news_updated', time());
                return [];
            }
            $news = json_decode(wp_remote_retrieve_body($response), true);

            // Firewall returns an invalid response
            if (!$news || !is_array($news)) {
                $news = [];
            }

            update_option('newsletter_news', $news);
            update_option('newsletter_news_updated', time());
        }

        $news_dismissed = $this->get_option_array('newsletter_news_dismissed');
        $today = date('Y-m-d');
        $list = [];
        foreach ($news as $n) {
            if ($today < $n['start'] || $today > $n['end'])
                continue;
            if (in_array($n['id'], $news_dismissed))
                continue;
            $list[] = $n;
        }
        return $list;
    }

    /* Wrappers */

    function get_license_key() {
        Newsletter::instance()->get_license_key();
    }

    function get_license_data($refresh = false) {
        return Newsletter::instance()->get_license_data($refresh);
    }

    function getTnpExtensions() {
        return Newsletter::instance()->getTnpExtensions();
    }

    function set_completed_step($step) {
        $steps = $this->get_option_array('newsletter_main_steps');
        $steps[$step] = 1;
        update_option('newsletter_main_steps', $steps);
    }
}
