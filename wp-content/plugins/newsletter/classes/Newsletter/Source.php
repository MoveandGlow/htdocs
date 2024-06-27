<?php

namespace Newsletter;

defined('ABSPATH') || exit;

class Source {
    var $name;
    var $slug;
    var $plugin;
    var $config_url = '';

    public function __construct($name = '', $plugin = '', $slug = '') {
        $this->name = $name;
        $this->slug = $slug;
        $this->plugin = $plugin;
    }
}
