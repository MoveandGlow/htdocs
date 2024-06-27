<?php

defined('ABSPATH') || exit;

class NewsletterUsersAdmin extends NewsletterModuleAdmin {

    static $instance;

    /**
     * @return NewsletterUnsubscriptionAdmin
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new NewsletterUsersAdmin();
        }
        return self::$instance;
    }

    function __construct() {
        parent::__construct('users', '1.0.7');
    }

    function admin_menu() {
        //$this->add_menu_page('index', __('Subscribers', 'newsletter'));
        $this->add_admin_page('new', __('New subscriber', 'newsletter'));
        $this->add_admin_page('edit', __('Subscriber Edit', 'newsletter'));
        $this->add_admin_page('logs', __('Logs', 'newsletter'));
        $this->add_admin_page('newsletters', __('Newsletters', 'newsletter'));
        $this->add_admin_page('autoresponders', __('Autoresponders', 'newsletter'));
        $this->add_admin_page('massive', __('Subscribers Maintenance', 'newsletter'));
        //$this->add_admin_page('export', __('Export', 'newsletter'));
        $this->add_admin_page('import', __('Import/Export', 'newsletter'));
        $this->add_admin_page('statistics', __('Statistics', 'newsletter'));
    }
}

class TNP_Subscribers_Stats {

    var $total;
    var $confirmed;
    var $unconfirmed;
    var $bounced;

}
