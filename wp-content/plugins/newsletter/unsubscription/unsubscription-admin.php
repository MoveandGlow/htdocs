<?php

defined('ABSPATH') || exit;

/**
 * @property NewsletterUnsubscription $frontend
 */
class NewsletterUnsubscriptionAdmin extends NewsletterModuleAdmin {

    static $instance;

    /**
     * @return NewsletterUnsubscriptionAdmin
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        parent::__construct('unsubscription');
    }

    function admin_menu() {
        $this->add_admin_page('index', 'Unsubscribe');
    }


}

