<?php

class NewsletterUpgrade {

    var $logger;
    var $old_version;

    public function __construct() {
        $this->logger = new NewsletterLogger('upgrade');
        $this->logger->level = NewsletterLogger::INFO;

        $this->old_version = get_option('newsletter_version', '0.0.0');
        // Special patch since older version may not have the version set
        if ($this->old_version === '0.0.0' && get_option('newsletter_main_version')) {
            $this->old_version = '7.0.0';
        }
    }

    function upgrade_query($query) {
        global $wpdb;

        $this->logger->info('Executing ' . $query);
        $suppress_errors = $wpdb->suppress_errors(true);
        $wpdb->query($query);
        if ($wpdb->last_error) {
            $this->logger->error($wpdb->last_error);
        }
        $wpdb->suppress_errors($suppress_errors);
    }

    function db_delta($sql) {
        $res = dbDelta($sql);
        $this->logger->info($res);
    }

    function run() {
        global $wpdb, $charset_collate;

        $this->logger->info('Start upgrade from ' . $this->old_version);

        require_once NEWSLETTER_DIR . '/admin.php';
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        wp_cache_flush();

        if (!get_option('newsletter_install_time')) {
            update_option('newsletter_install_time', time(), false);
        }

        $sql = "CREATE TABLE `" . NEWSLETTER_EMAILS_TABLE . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `language` varchar(10) NOT NULL DEFAULT '',
            `subject` varchar(255) NOT NULL DEFAULT '',
            `message` longtext,
            `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `status` enum('new','sending','sent','paused','error') NOT NULL DEFAULT 'new',
            `total` int(11) NOT NULL DEFAULT '0',
            `last_id` int(11) NOT NULL DEFAULT '0',
            `sent` int(11) NOT NULL DEFAULT '0',
            `track` int(11) NOT NULL DEFAULT '1',
            `list` int(11) NOT NULL DEFAULT '0',
            `type` varchar(50) NOT NULL DEFAULT '',
            `query` longtext,
            `editor` tinyint(4) NOT NULL DEFAULT '0',
            `sex` varchar(20) NOT NULL DEFAULT '',
            `theme` varchar(50) NOT NULL DEFAULT '',
            `message_text` longtext,
            `preferences` longtext,
            `send_on` int(11) NOT NULL DEFAULT '0',
            `token` varchar(10) NOT NULL DEFAULT '',
            `options` longtext,
            `private` tinyint(1) NOT NULL DEFAULT '0',
            `click_count` int(10) unsigned NOT NULL DEFAULT '0',
            `version` varchar(10) NOT NULL DEFAULT '',
            `open_count` int(10) unsigned NOT NULL DEFAULT '0',
            `unsub_count` int(10) unsigned NOT NULL DEFAULT '0',
            `error_count` int(10) unsigned NOT NULL DEFAULT '0',
            `stats_time` int(10) unsigned NOT NULL DEFAULT '0',
            `updated` int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
            ) $charset_collate;";

        $this->db_delta($sql);

// WP does not manage composite primary key when it tries to upgrade a table...
        $suppress_errors = $wpdb->suppress_errors(true);
        $sql = "CREATE TABLE `" . NEWSLETTER_SENT_TABLE . "` (
            email_id int(10) unsigned NOT NULL DEFAULT '0',
            user_id int(10) unsigned NOT NULL DEFAULT '0',
            status tinyint(1) unsigned NOT NULL DEFAULT '0',
            open tinyint(1) unsigned NOT NULL DEFAULT '0',
            time int(10) unsigned NOT NULL DEFAULT '0',
            error varchar(255) NOT NULL DEFAULT '',
            ip varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY (email_id,user_id),
            KEY user_id (user_id),
            KEY email_id (email_id)
            ) $charset_collate;";

        $this->db_delta($sql);

        $wpdb->suppress_errors($suppress_errors);

        $sql = "CREATE TABLE `" . NEWSLETTER_USERS_TABLE . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(100) NOT NULL DEFAULT '',
            `name` varchar(100) NOT NULL DEFAULT '',
            `token` varchar(50) NOT NULL DEFAULT '',
            `language` varchar(10) NOT NULL DEFAULT '',
            `status` varchar(1) NOT NULL DEFAULT 'S',
            `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated` int(11) NOT NULL DEFAULT '0',
            `last_activity` int(11) NOT NULL DEFAULT '0',
            `surname` varchar(100) NOT NULL DEFAULT '',
            `sex` char(1) NOT NULL DEFAULT 'n',
            `feed_time` bigint(20) NOT NULL DEFAULT '0',
            `feed` tinyint(4) NOT NULL DEFAULT '0',
            `referrer` varchar(50) NOT NULL DEFAULT '',
            `ip` varchar(50) NOT NULL DEFAULT '',
            `wp_user_id` int(11) NOT NULL DEFAULT '0',
            `source` varchar(50) NOT NULL DEFAULT '',
            `http_referer` varchar(255) NOT NULL DEFAULT '',
            `geo` tinyint(4) NOT NULL DEFAULT '0',
            `country` varchar(4) NOT NULL DEFAULT '',
            `region` varchar(100) NOT NULL DEFAULT '',
            `city` varchar(100) NOT NULL DEFAULT '',
            `bounce_type` varchar(50) NOT NULL DEFAULT '',
            `bounce_time` int(11) NOT NULL DEFAULT '0',
            `unsub_email_id` int(11) NOT NULL DEFAULT '0',
            `unsub_time` int(11) NOT NULL DEFAULT '0',\n";

        for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
            $sql .= "`list_$i` tinyint(4) NOT NULL DEFAULT '0',\n";
        }

        for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
            $sql .= "`profile_$i` varchar(255) NOT NULL DEFAULT '',\n";
        }

// Leave as last
        $sql .= "`test` tinyint(4) NOT NULL DEFAULT '0',\n";
        $sql .= "PRIMARY KEY (`id`),\nUNIQUE KEY `email` (`email`),\nKEY `wp_user_id` (`wp_user_id`)\n) $charset_collate;";

        $this->db_delta($sql);

        $sql = "CREATE TABLE `" . $wpdb->prefix . "newsletter_user_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL DEFAULT 0,
            `ip` varchar(50) NOT NULL DEFAULT '',
            `source` varchar(50) NOT NULL DEFAULT '',
            `data` longtext,
            `created` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
            ) $charset_collate;";

        $this->db_delta($sql);

        $sql = "CREATE TABLE `" . $wpdb->prefix . "newsletter_user_meta` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL DEFAULT 0,
            `meta_key` varchar(100) NOT NULL DEFAULT '',
            `value` longtext,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`)
            ) $charset_collate;";

        $this->db_delta($sql);

        $suppress_errors = $wpdb->suppress_errors(true);
        $wpdb->query("ALTER TABLE `" . $wpdb->prefix . "newsletter_user_meta` DROP INDEX `user_id_key`");
        $wpdb->query("ALTER TABLE `" . $wpdb->prefix . "newsletter_user_meta` 	DROP COLUMN `key`");
        $wpdb->suppress_errors($suppress_errors);

        $sql = "CREATE TABLE `" . NEWSLETTER_STATS_TABLE . "` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `url` varchar(255) NOT NULL DEFAULT '',
            `user_id` int(11) NOT NULL DEFAULT '0',
            `email_id` varchar(10) NOT NULL DEFAULT '0',
            `ip` varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`),
            KEY `email_id` (`email_id`),
            KEY `user_id` (`user_id`)
            ) $charset_collate;";

        $this->db_delta($sql);

        $sql = "CREATE TABLE `" . $wpdb->prefix . "newsletter_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `status` int NOT NULL DEFAULT 0,
            `source` varchar(100) NOT NULL DEFAULT '',
            `description` varchar(255) NOT NULL DEFAULT '',
            `data` longtext,
            `created` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
            ) $charset_collate;";

        $this->db_delta($sql);

        delete_option('newsletter_system_warnings');
        delete_option('newsletter_promotion');
        delete_option('newsletter_main_status');
        delete_option('newsletter_statistics_available_version');
        delete_option('newsletter_statistics_secret');

        if (!wp_next_scheduled('newsletter')) {
            wp_schedule_event(time() + 30, 'newsletter', 'newsletter');
        }

        if ($this->old_version === '0.0.0') {
            update_option('newsletter_show_welcome', '1');
        }

        // Delete old backup
        $items = $wpdb->get_results("select option_name from {$wpdb->options} where option_name like 'newsletter_backup_%' order by option_name");
        if ($items) {
            for ($i = 0; $i < count($items) - 5; $i++) {
                $this->logger->info('Deleting settings backup ' . $items[$i]->option_name);
                delete_option($items[$i]->option_name);
            }
        }

        // Do not overwrite an existing backup
        if (!get_option('newsletter_backup_' . $this->old_version)) {
            $this->logger->info('Backing up options of ' . $this->old_version);
            $items = $wpdb->get_results("select option_name from {$wpdb->options} where option_name like 'newsletter%'");

            $backup = [];
            foreach ($items as $item) {
                if (strpos($item->option_name, 'newsletter_backup') === 0) {
                    continue;
                }
                if (strpos($item->option_name, 'newsletter_diagnostic') === 0) {
                    continue;
                }
                $options = get_option($item->option_name);
                $backup[$item->option_name] = $options;
            }

            update_option('newsletter_backup_' . $this->old_version, $backup, false);
        }

        $opt = $this->get_option_array('newsletter_statistics');
        if (empty($opt['key'])) {
            $opt['key'] = md5(__DIR__ . rand(100000, 999999) . time());
            update_option('newsletter_statistics', $opt, false);
        }

        if ($this->old_version < '8.0.8') {
            $opt = get_option('newsletter_subscription');
            if (!empty($opt['confirmed_disabled'])) {
                $opt['welcome_email'] = '2';
                update_option('newsletter_subscription', $opt);
            }
        }

        if ($this->old_version === '7.8.0' || $this->old_version === '7.8.1') {
            $opt = get_option('newsletter_forms');
            if ($opt !== false) {
                update_option('newsletter_htmlforms', $opt, false);
            }
        }

        // This code should run only once!
        if ($this->old_version <= '7.7.0' && $this->old_version !== '0.0.0') {

            $this->logger->info('Migration to 7.8.0');

            // Create the new antispam options

            $opt = get_option('newsletter_subscription_antibot');
            if ($opt !== false) {
                update_option('newsletter_antispam', $opt, false);
            }


            // Create the new HTML forms options

            $opt = get_option('newsletter_forms');
            if ($opt !== false) {
                update_option('newsletter_htmlforms', $opt, false);
            }

            // Some original options we need and that will be overwritten
            $lists = get_option('newsletter_subscription_lists');
            $profile = get_option('newsletter_profile');

            $languages = array_keys(Newsletter::instance()->get_languages());

            // Create the new form options (originally in the "profile" option set)

            if (!get_option('newsletter_form')) {

                $form = get_option('newsletter_profile');

                if ($form) {

                    // Fix values
                    if (isset($form['name_status']) && $form['name_status'] == '1') { // Visible on profile page only
                        $form['name_status'] = 0;
                    }
                    if (isset($form['surname_status']) && $form['surname_status'] == '1') { // Visible on profile page only
                        $form['surname_status'] = 0;
                    }
                    if (isset($form['sex_status']) && $form['sex_status'] == '1') { // Visible on profile page only
                        $form['sex_status'] = 0;
                    }

                    // Custom fields
                    $form['customfields'] = [];
                    for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
                        if (isset($profile['profile_' . $i . '_status']) && $profile['profile_' . $i . '_status'] == '2') { // Visible on the subscription form
                            $form['customfields'][] = $i;
                        }
                    }

                    // Remove the profile_* keys
                    foreach (array_keys($form) as $key) {
                        if (strpos($key, 'profile_') === 0) {
                            unset($form[$key]);
                        }
                    }

                    // From the original lists, check which ones should be shown in the subscription form
                    $form['lists'] = [];
                    $form['lists_checked'] = [];
                    for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
                        if (isset($lists['list_' . $i . '_subscription']) && $lists['list_' . $i . '_subscription']) {
                            $form['lists'][] = $i;
                        }
                        if (isset($lists['list_' . $i . '_checked']) && $lists['list_' . $i . '_checked']) {
                            $form['lists_checked'][] = $i;
                        }
                    }
                } else {
                    $form = [];
                }
                update_option('newsletter_form', $form, true);

                foreach ($languages as $language) {
                    $form = get_option('newsletter_profile_' . $language);
                    if (!empty($form)) {
                        // Remove the profile_* keys
                        foreach (array_keys($form) as $key) {
                            if (strpos($key, 'profile_') === 0) {
                                unset($form[$key]);
                            }
                            if (strpos($key, '_status') > 0) {
                                unset($form[$key]);
                            }
                            if (strpos($key, '_rules') > 0) {
                                unset($form[$key]);
                            }
                        }
                        update_option('newsletter_form_' . $language, $form, true);
                    }
                }
            }


            // Generate the new customfields options, if they don't exist
            if (!get_option('newsletter_customfields')) {

                // Main options
                $customfields = get_option('newsletter_profile');
                if (!empty($customfields)) {
                    foreach (array_keys($customfields) as $key) {
                        if (strpos($key, 'profile_') !== 0) {
                            unset($customfields[$key]);
                        }
                    }
                } else {
                    $customfields = [];
                }
                update_option('newsletter_customfields', $customfields, true);

                foreach ($languages as $language) {
                    $customfields = get_option('newsletter_profile_' . $language);
                    if (!empty($customfields)) {

                        // Remove all but the profile_* keys and non language related keys
                        foreach (array_keys($customfields) as $key) {
                            if (strpos($key, 'profile_') !== 0) {
                                unset($customfields[$key]);
                            }
                            if (strpos($key, '_type') > 0 || strpos($key, '_status') > 0) {
                                unset($customfields[$key]);
                            }
                        }
                        update_option('newsletter_customfields_' . $language, $customfields, true);
                    }
                }
            }


            // Generate the new profile options related to the profile page and not anymore to the "form";
            // this is a delicate overwrite!
            $opt = get_option('newsletter_profile_main');
            if ($opt) {
                $opt['lists'] = [];
                for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
                    if (isset($lists['list_' . $i . '_profile']) && $lists['list_' . $i . '_profile']) {
                        $opt['lists'][] = $i;
                    }
                }

                $opt['customfields'] = [];
                for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
                    // If the profile field should be shown on the profile page
                    if (!empty($profile['profile_' . $i . '_status'])) {
                        $opt['customfields'][] = $i;
                    }
                }

                update_option('newsletter_profile', $opt, true);

                foreach ($languages as $language) {
                    $opt = get_option('newsletter_profile_main_' . $language, []);
                    update_option('newsletter_profile_' . $language, $opt, true);
                }
            }

            $opt = get_option('newsletter');
            if ($opt !== false) {
                update_option('newsletter_subscription', $opt, true);
                foreach ($languages as $language) {

                    // Original subscription options
                    $opt = get_option('newsletter_' . $language);
                    if ($opt !== false) {
                        update_option('newsletter_subscription_' . $language, $opt, true);
                    }
                }
            }

            $opt = get_option('newsletter_main_info');
            if ($opt !== false) {
                update_option('newsletter_info', $opt, false);
            }


            // Lists migration

            $opt = get_option('newsletter_subscription_lists');
            if ($opt !== false) {
                foreach (array_keys($opt) as $key) {
                    if (strpos($key, '_subscription') > 0) {
                        unset($opt[$key]);
                    }
                    if (strpos($key, '_profile') > 0) {
                        unset($opt[$key]);
                    }
                    if (strpos($key, '_checked') > 0) {
                        unset($opt[$key]);
                    }
                }
                update_option('newsletter_lists', $opt, true);
                delete_option('newsletter_subscription_lists');
                foreach ($languages as $language) {
                    // Original lists options
                    $opt = get_option('newsletter_subscription_lists_' . $language);
                    if ($opt !== false) {
                        update_option('newsletter_lists_' . $language, $opt, true);
                        delete_option('newsletter_subscription_lists_' . $language);
                    }
                }
            }

            $opt = get_option('newsletter_subscription_template');
            if ($opt !== false) {
                update_option('newsletter_template', $opt, true);
                delete_option('newsletter_subscription_template');
                foreach ($languages as $language) {
                    // Original lists options
                    $opt = get_option('newsletter_subscription_template_' . $language);
                    if ($opt !== false) {
                        update_option('newsletter_template_' . $language, $opt, true);
                        delete_option('newsletter_subscription_template_' . $language);
                    }
                }
            }

            delete_option('newsletter_system_status');
            delete_option('newsletter');
            delete_option('newsletter_profile_main');
            delete_option('newsletter_main_info');
            delete_option('newsletter_subscription_antibot');

            foreach ($languages as $language) {

                delete_option('newsletter_system_status_' . $language);
                delete_option('newsletter_' . $language);
                delete_option('newsletter_profile_main_' . $language);
                delete_option('newsletter_main_info_' . $language);
                delete_option('newsletter_subscription_antibot_' . $language);

                // Dev versions
                delete_option('newsletter_subscription_customfields_' . $language);
                delete_option('newsletter_subscription_form_' . $language);

                delete_option('newsletter_info_' . $language);
                delete_option('newsletter_system_' . $language);
                delete_option('newsletter_users_' . $language);
                delete_option('newsletter_main_' . $language);
                delete_option('newsletter_statistics_' . $language);
            }

            // Unused options
            delete_option('newsletter_main_smtp');
            delete_option('newsletter_subscription_version');
            delete_option('newsletter_subscription_first_install_time');
            delete_option('newsletter_unsubscription_version');
            delete_option('newsletter_unsubscription_first_install_time');
            delete_option('newsletter_users_version');
            delete_option('newsletter_users_first_install_time');
            delete_option('newsletter_profile_version');
            delete_option('newsletter_profile_first_install_time');
            delete_option('newsletter_emails_version');
            delete_option('newsletter_emails_first_install_time');
            delete_option('newsletter_system_version');
            delete_option('newsletter_system_first_install_time');
            delete_option('newsletter_main_version');
            delete_option('newsletter_main_first_install_time');
            delete_option('newsletter_statistics_version');
            delete_option('newsletter_statistics_first_install_time');
            delete_option('newsletter_wp');
        }

        delete_transient('newsletter_license_data');
        delete_transient('tnp_extensions_json');
        touch(NEWSLETTER_LOG_DIR . '/index.html');

        $this->logger->info('End');
    }

    function get_option_array($name) {
        $opt = get_option($name, []);
        if (!is_array($opt)) {
            return [];
        }
        return $opt;
    }
}

(new NewsletterUpgrade())->run();
