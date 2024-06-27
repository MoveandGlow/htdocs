<?php

defined('ABSPATH') || exit;

/**
 * Class kept only for cmpatibility with addons.
 */
class NewsletterUsers extends NewsletterModule {

    static $instance;

    /**
     * @return NewsletterUnsubscription
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        parent::__construct('users');
    }

}

NewsletterUsers::instance();
