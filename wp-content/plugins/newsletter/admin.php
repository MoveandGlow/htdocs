<?php

define('NEWSLETTER_ADMIN_HEADER', NEWSLETTER_DIR . '/header.php');
define('NEWSLETTER_ADMIN_FOOTER', NEWSLETTER_DIR . '/tnp-footer.php');

require_once __DIR__ . '/includes/module-admin.php';
require_once __DIR__ . '/includes/addon-admin.php';
require_once __DIR__ . '/main/main-admin.php';
require_once __DIR__ . '/subscription/subscription-admin.php';
require_once __DIR__ . '/unsubscription/unsubscription-admin.php';
require_once __DIR__ . '/users/users-admin.php';
require_once __DIR__ . '/emails/emails-admin.php';
require_once __DIR__ . '/system/system-admin.php';
require_once __DIR__ . '/statistics/statistics-admin.php';
require_once __DIR__ . '/profile/profile-admin.php';

class NewsletterAdmin extends NewsletterModuleAdmin {

    static $instance = null;

    /**
     *
     * @return NewsletterAdmin
     */
    static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        parent::__construct('main');
        add_action('wp_loaded', [$this, 'hook_wp_loaded'], 1);
        add_action('admin_init', [$this, 'hook_admin_init']);
        add_action('admin_head', [$this, 'hook_admin_head']);
        add_action('in_admin_header', [$this, 'hook_in_admin_header'], 1000);
        add_action('admin_menu', [$this, 'hook_admin_menu']);

        // Protection against strange schedule removal on some installations
        if (!wp_next_scheduled('newsletter') && (!defined('WP_INSTALLING') || !WP_INSTALLING)) {
            wp_schedule_event(time() + 30, 'newsletter', 'newsletter');
        }

        //add_action('admin_menu', [$this, 'add_extensions_menu'], 90);

        add_action('admin_enqueue_scripts', [$this, 'hook_admin_enqueue_scripts'], 999);
    }

    function hook_wp_loaded() {

        self::$is_admin_page = isset($_GET['page']) && strpos($_GET['page'], 'newsletter_') === 0;

        if (!class_exists('NewsletterExtensions')) {

            add_filter('plugin_row_meta', function ($plugin_meta, $plugin_file) {

                static $slugs = array();
                if (empty($slugs)) {
                    $addons = Newsletter::instance()->getTnpExtensions();
                    if ($addons) {
                        foreach ($addons as $addon) {
                            $slugs[] = $addon->wp_slug;
                        }
                    }
                }
                if (array_search($plugin_file, $slugs) !== false) {

                    $plugin_meta[] = '<a href="admin.php?page=newsletter_main_extensions" style="font-weight: bold">Newsletter Addons Manager required</a>';
                }
                return $plugin_meta;
            }, 10, 2);
        }

        NewsletterMainAdmin::instance()->wp_loaded();
        NewsletterEmailsAdmin::instance()->wp_loaded();

        if (self::$is_admin_page) {

            // Specpal header for HTML coded forms
            if (isset($_GET['page']) && $_GET['page'] === 'newsletter_subscription_forms') {
                header('X-XSS-Protection: 0');
            }
        }

        add_action('admin_bar_menu', [$this, 'hook_admin_bar_menu'], 500);
    }

    /**
     *
     * @global wpdb $wpdb
     * @param WP_Admin_Bar $admin_bar
     * @return type
     *
     * https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     */
    function hook_admin_bar_menu(WP_Admin_Bar $admin_bar) {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            return;
        }

        $data = get_transient('newsletter_topbar');
        if (!$data || !is_array($data)) {
            $data = $wpdb->get_row($wpdb->prepare("select count(*) as count, sum(total) as total, sum(sent) as sent from " . NEWSLETTER_EMAILS_TABLE . " where status='sending' and send_on<%d", time()), ARRAY_A);
            if (!$data) {
                $data = ['count' => 0];
            }
            //$data['updated_at'] = time();
            set_transient('newsletter_topbar', $data, 600);
        }

        if (!$data['count']) {
            return;
        }

        $admin_bar->add_menu(array(
            'id' => 'newsletter',
            'parent' => null,
            'group' => null,
            'title' => '<span class="ab-icon"></span>' . ((int) $data['count']) . ' sending (' . ((int) $data['sent']) . '/' . ((int) $data['total']) . ')',
            'href' => admin_url('admin.php?page=newsletter_system_delivery#sending'),
            'meta' => [
                'title' => __('Newsletters sending (updated every 10 minutes)', 'newsletter'),
            ]
        ));
    }

    function hook_in_admin_header() {
        if (self::$is_admin_page) {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
        add_action('admin_notices', [$this, 'hook_admin_notices']);
    }

    function hook_admin_notices() {
        NewsletterMainAdmin::instance()->admin_notices();
    }

    function hook_admin_init() {
        // Verificare il contesto
        if (isset($_GET['page']) && $_GET['page'] === 'newsletter_main_welcome')
            return;
        if (get_option('newsletter_show_welcome')) {
            delete_option('newsletter_show_welcome');
            wp_redirect(admin_url('admin.php?page=newsletter_main_welcome'));
        }

        if (self::$is_admin_page) {
            // Remove the emoji replacer to save to database the original emoji characters (see even woocommerce for the same problem)
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
        }
    }

    function hook_admin_head() {

    }

    function hook_admin_enqueue_scripts() {

        $url = plugins_url('newsletter');

        // Styles for the left side menu
        wp_enqueue_style('tnp-admin-global', $url . '/admin/css/global.css', [], NEWSLETTER_VERSION);

        // Styles and scripts only for our admin pages
        if (self::is_admin_page()) {
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_media();

            wp_enqueue_style('tnp-admin-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap');

            wp_enqueue_script('tnp-admin', $url . '/admin/js/all.js', ['jquery'], NEWSLETTER_VERSION);

            wp_enqueue_style('tnp-select2', $url . '/vendor/select2/css/select2.min.css', [], NEWSLETTER_VERSION);
            wp_enqueue_script('tnp-select2', $url . '/vendor/select2/js/select2.min.js', ['jquery'], NEWSLETTER_VERSION);

            wp_enqueue_style('tnp-admin-fontawesome', $url . '/vendor/fa/css/all.min.css', [], NEWSLETTER_VERSION);
            wp_enqueue_style('tnp-admin-jquery-ui', $url . '/vendor/jquery-ui/jquery-ui.min.css', [], NEWSLETTER_VERSION);

            wp_enqueue_script('tnp-jquery-modal', $url . '/vendor/jquery-modal/jquery.modal.min.js', ['jquery'], NEWSLETTER_VERSION);
            wp_enqueue_style('tnp-jquery-modal', $url . '/vendor/jquery-modal/jquery.modal.min.css', [], NEWSLETTER_VERSION);

            wp_enqueue_style('tnp-admin', $url . '/admin/css/all.css', ['tnp-jquery-modal'], NEWSLETTER_VERSION);

            $translations_array = array(
                'save_to_update_counter' => __('Save the newsletter to update the counter!', 'newsletter')
            );
            wp_localize_script('tnp-admin', 'tnp_translations', $translations_array);

            wp_enqueue_script('tnp-jquery-vmap', $url . '/vendor/jqvmap/jquery.vmap.min.js', ['jquery'], NEWSLETTER_VERSION);
            wp_enqueue_script('tnp-jquery-vmap-world', $url . '/vendor/jqvmap/jquery.vmap.world.js', ['tnp-jquery-vmap'], NEWSLETTER_VERSION);
            wp_enqueue_style('tnp-jquery-vmap', $url . '/vendor/jqvmap/jqvmap.min.css', [], NEWSLETTER_VERSION);

            wp_register_script('tnp-chart', $url . '/vendor/chartjs/Chart.min.js', ['jquery'], NEWSLETTER_VERSION);

            wp_enqueue_script('tnp-color-picker', $url . '/vendor/spectrum-prefixed/spectrum.js', ['jquery'], NEWSLETTER_VERSION);
            wp_enqueue_style('tnp-color-picker', $url . '/vendor/spectrum-prefixed/spectrum.css', [], NEWSLETTER_VERSION);

            wp_enqueue_script('tnp-toastify', $url . '/vendor/toastify/toastify.js', [], NEWSLETTER_VERSION);
            wp_enqueue_style('tnp-toastify', $url . '/vendor/toastify/toastify.css', [], NEWSLETTER_VERSION);
        }
    }

    public function hook_admin_menu() {

        if (!Newsletter::instance()->is_allowed()) {
            return;
        }

        add_menu_page('Newsletter', 'Newsletter', 'exist', 'newsletter_main_index', '', plugins_url('newsletter') . '/admin/images/menu-icon.png', '40');

        NewsletterMainAdmin::instance()->admin_menu();
        NewsletterSubscriptionAdmin::instance()->admin_menu();
        NewsletterUsersAdmin::instance()->admin_menu();
        NewsletterEmailsAdmin::instance()->admin_menu();
        NewsletterSystemAdmin::instance()->admin_menu();
        NewsletterStatisticsAdmin::instance()->admin_menu();
        NewsletterProfileAdmin::instance()->admin_menu();
        NewsletterUnsubscriptionAdmin::instance()->admin_menu();

        // Main pages (to get them in the right order
        NewsletterMainAdmin::instance()->add_menu_page('index', __('Dashboard', 'newsletter'));
        NewsletterSubscriptionAdmin::instance()->add_menu_page('options', __('Subscription', 'newsletter'));
        NewsletterSubscriptionAdmin::instance()->add_menu_page('sources', __('Forms', 'newsletter'));
        NewsletterEmailsAdmin::instance()->add_menu_page('index', __('Newsletters', 'newsletter'));
        NewsletterUsersAdmin::instance()->add_menu_page('index', __('Subscribers', 'newsletter'));
        NewsletterSubscriptionAdmin::instance()->add_menu_page('lists', __('Lists', 'newsletter'));
        if (current_user_can('administrator')) {
            NewsletterMainAdmin::instance()->add_menu_page('main', __('Settings', 'newsletter'));
        }

        NewsletterMainAdmin::instance()->admin_after_menu(); // Special entries
    }
}

NewsletterAdmin::instance();
NewsletterEmailsAdmin::instance(); // Required to activate the newsletter_action hook
NewsletterUsersAdmin::instance();
