<?php

defined('ABSPATH') || exit;

class NewsletterEmailsAdmin extends NewsletterModuleAdmin {

    static $instance;
    var $themes;

    /**
     * @return NewsletterEmailsAdmin
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        parent::__construct('emails');
        $this->themes = new NewsletterThemes('emails');
        // Thank you to plugins that add the WP editor on other admin plugin pages...
        if (isset($_GET['page']) && $_GET['page'] == 'newsletter_emails_edit') {
            global $wp_actions;
            $wp_actions['wp_enqueue_editor'] = 1;
        }
    }

    function wp_loaded() {
        if (defined('DOING_AJAX') && DOING_AJAX && $this->is_allowed()) {
            add_action('wp_ajax_tnpc_options', array($this, 'ajax_tnpc_options'));
            add_action('wp_ajax_tnpc_get_all_presets', array($this, 'ajax_get_all_presets'));
            add_action('wp_ajax_tnpc_get_preset', array($this, 'ajax_get_preset'));
            add_action('wp_ajax_tnpc_render', array($this, 'ajax_tnpc_render'));
            add_action('wp_ajax_tnpc_preview', array($this, 'ajax_tnpc_preview'));
            add_action('wp_ajax_tnpc_css', array($this, 'ajax_tnpc_css'));
            add_action('wp_ajax_tnpc_delete_preset', array($this, 'ajax_tnpc_delete_preset'));
            add_action('wp_ajax_tnpc_regenerate_email', array($this, 'ajax_tnpc_regenerate_email'));
        }
    }

    function admin_menu() {
        //$this->add_menu_page('index', 'Newsletters');
        $this->add_admin_page('list', 'Email List');
        $this->add_admin_page('new', 'Email New');
        $this->add_admin_page('edit', 'Email Edit');
        $this->add_admin_page('theme', 'Email Themes');
        $this->add_admin_page('composer', 'The Composer');
        $this->add_admin_page('editorhtml', 'HTML Editor');
        $this->add_admin_page('editortinymce', 'TinyMCE Editor');
        $this->add_admin_page('presets', 'Presets');
        $this->add_admin_page('presets-edit', 'Presets');

        $this->add_admin_page('automated', 'Automated');
        $this->add_admin_page('autoresponder', 'Autoresponder');
    }

    function get_preset_content($preset_id) {
        return NewsletterComposer::instance()->get_preset_content($preset_id);
    }

    /** Returns the correct admin page to edit the newsletter with the correct editor. */
    function get_editor_url($email_id, $editor_type) {
        switch ($editor_type) {
            case NewsletterEmails::EDITOR_COMPOSER:
                return '?page=newsletter_emails_composer&id=' . urlencode($email_id);
            case NewsletterEmails::EDITOR_HTML:
                return '?page=newsletter_emails_editorhtml&id=' . urlencode($email_id);
            case NewsletterEmails::EDITOR_TINYMCE:
                return '?page=newsletter_emails_editortinymce&id=' . urlencode($email_id);
        }
    }

    function get_edit_button($email, $only_icon = true) {

        $editor_type = $this->get_editor_type($email);
        if ($email->status === TNP_Email::STATUS_DRAFT) {
            $edit_url = $this->get_editor_url($email->id, $editor_type);
        } else {
            $edit_url = '?page=newsletter_emails_edit&id=' . urlencode($email->id);
        }

        $icon_class = 'edit';
        if ($only_icon) {
            return '<a class="button-primary tnpc-button" href="' . $edit_url . '" title="' . esc_attr__('Edit', 'newsletter') . '">' .
                    '<i class="fas fa-' . $icon_class . '"></i></a>';
        } else {
            return '<a class="button-primary tnpc-button" href="' . $edit_url . '" title="' . esc_attr__('Edit', 'newsletter') . '">' .
                    '<i class="fas fa-' . $icon_class . '"></i> ' . __('Edit', 'newsletter') . '</a>';
        }
    }

    /** Returns the correct editor type for the provided newsletter. Contains backward compatibility code. */
    function get_editor_type($email) {
        $email = (object) $email;
        $editor_type = $email->editor;

        // Backward compatibility
        $email_options = maybe_unserialize($email->options);
        if (isset($email_options['composer'])) {
            $editor_type = NewsletterEmails::EDITOR_COMPOSER;
        }
        // End backward compatibility

        return $editor_type;
    }

    function ajax_tnpc_options() {
        global $wpdb;

        $block = NewsletterComposer::instance()->get_block($_REQUEST['id']);
        if (!$block) {
            die('Block not found with id ' . esc_html($_REQUEST['id']));
        }

        if (!class_exists('NewsletterControls')) {
            include NEWSLETTER_INCLUDES_DIR . '/controls.php';
        }

        $options = NewsletterComposer::options_decode(stripslashes_deep($_REQUEST['options']));
        $composer = isset($_POST['composer']) ? $_POST['composer'] : [];

        if (empty($composer['width'])) {
            $composer['width'] = 600;
        }

        $context = array('type' => '');
        if (isset($_REQUEST['context_type'])) {
            $context['type'] = $_REQUEST['context_type'];
        }

        $controls = new NewsletterControls($options);
        $fields = new NewsletterFields($controls);

        $controls->init();
        echo '<input type="hidden" name="action" value="tnpc_render">';
        echo '<input type="hidden" name="id" value="' . esc_attr($_REQUEST['id']) . '">';
        echo '<input type="hidden" name="context_type" value="' . esc_attr($context['type']) . '">';
        $inline_edits = '';
        if (isset($controls->data['inline_edits'])) {
            $inline_edits = $controls->data['inline_edits'];
        }
        echo '<input type="hidden" name="options[inline_edits]" value="', esc_attr(NewsletterComposer::options_encode($inline_edits)), '">';
        echo "<h3>", esc_html($block["name"]), "</h3>";
        include $block['dir'] . '/options.php';
        wp_die();
    }

    /**
     * Retrieves the presets list (no id in GET) or a specific preset id in GET)
     */
    function ajax_get_all_presets() {
        wp_send_json_success($this->get_all_preset());
    }

    /**
     * @todo Improve!
     */
    function ajax_get_preset() {

        if (empty($_REQUEST['id'])) {
            wp_send_json_error([
                'msg' => __('Invalid preset ID')
            ]);
        }

        $preset_id = $_REQUEST['id'];
        $preset_content = $this->get_preset_content($preset_id);

        $global_options = NewsletterComposer::instance()->get_preset_composer_options($preset_id);

        wp_send_json_success([
            'content' => $preset_content,
            'globalOptions' => $global_options,
        ]);
    }

    function ajax_tnpc_preview() {
        $email = $this->get_email($_REQUEST['id']);

        echo $email->message;

        die();
    }

    function ajax_tnpc_css() {
        include NEWSLETTER_DIR . '/emails/tnp-composer/css/newsletter.css';
        wp_die();
    }

    /**
     * Ajax call to render a block with a new set of options after the settings popup
     * has been saved.
     *
     * @param type $block_id
     * @param type $wrapper
     */
    function ajax_tnpc_render() {
        if (!check_ajax_referer('save')) {
            wp_die('Invalid nonce', 403);
        }

        $block_id = $_POST['id'];
        $wrapper = isset($_POST['full']);
        $options = $this->restore_options_from_request();

        NewsletterComposer::instance()->render_block($block_id, $wrapper, $options, [], $_POST['composer']);
        die();
    }

    function restore_options_from_request() {

        require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
        $controls = new NewsletterControls();
        $options = $controls->data;

        if (isset($_POST['options']) && is_array($_POST['options'])) {
            // Get all block options
            //$options = stripslashes_deep($_POST['options']);
            // Deserialize inline edits when
            // render is preformed on saving block options
            if (isset($options['inline_edits']) && !is_array($options['inline_edits'])) {
                $options['inline_edits'] = NewsletterComposer::options_decode($options['inline_edits']);
            }

            // Restore inline edits from data-json
            // coming from inline editing
            // and merge with current inline edit
            if (isset($_POST['encoded_options'])) {
                $decoded_options = NewsletterComposer::options_decode($_POST['encoded_options']);

                $to_merge_inline_edits = [];

                if (isset($decoded_options['inline_edits'])) {
                    foreach ($decoded_options['inline_edits'] as $decoded_inline_edit) {
                        $to_merge_inline_edits[$decoded_inline_edit['post_id'] . $decoded_inline_edit['type']] = $decoded_inline_edit;
                    }
                }

                //Overwrite with new edited content
                if (isset($options['inline_edits'])) {
                    foreach ($options['inline_edits'] as $inline_edit) {
                        $to_merge_inline_edits[$inline_edit['post_id'] . $inline_edit['type']] = $inline_edit;
                    }
                }

                $options['inline_edits'] = array_values($to_merge_inline_edits);
                $options = array_merge($decoded_options, $options);
            }

            return $options;
        }

        return [];
    }

    function ajax_tnpc_regenerate_email() {

        if (!check_ajax_referer('save')) {
            wp_die('Invalid nonce', 403);
        }

        $content = stripslashes($_POST['content']);
        $content = urldecode(base64_decode($content));
        $composer = stripslashes_deep($_POST['composer']);

        $result = NewsletterComposer::instance()->regenerate_blocks($content, [], $composer);

        wp_send_json_success([
            'content' => $result['content'],
            'message' => __('Successfully updated', 'newsletter')
        ]);
    }

    function ajax_tnpc_delete_preset() {

        if (!check_ajax_referer('preset')) {
            wp_die('Invalid nonce', 403);
        }

        $preset = $this->get_email($_REQUEST['presetId']);

        if ($preset && $preset->type === NewsletterEmails::PRESET_EMAIL_TYPE) {
            $this->delete_email($preset->id);
            wp_send_json_success();
        } else {
            wp_send_json_error('Invalid preset ID');
        }
    }

    private function is_normal_context_request() {
        return empty($_REQUEST['context_type']);
    }

    /**
     * Generated the HTML with the preset lists for the modal used in the composer to create a new
     * email.
     *
     * @return string The presets selection HTML
     */
    function get_all_preset() {

        $content = "<div class='tnpc-preset-container'>";

        if ($this->is_normal_context_request()) {
            $content .= "<div class='tnpc-preset-legacy-themes'><a href='" . $this->get_admin_page_url('theme') . "'>" . __('Looking for legacy themes?', 'newsletter') . "</a></div>";
        }

        // LOAD USER PRESETS
        $user_preset_list = $this->get_emails(NewsletterEmails::PRESET_EMAIL_TYPE);

        foreach ($user_preset_list as $user_preset) {

            $default_icon_url = plugins_url('newsletter') . "/emails/presets/default-icon.png?ver=2";
            $preset_name = $user_preset->subject;

            // esc_js() assumes the string will be in single quote (arghhh!!!)
            $onclick_load = 'tnpc_load_preset(' . ((int) $user_preset->id) . ', \'' . esc_js($preset_name) . '\', event)';

            $content .= "<div class='tnpc-preset' onclick='" . esc_attr($onclick_load) . "'>\n";
            $content .= "<img src='$default_icon_url' title='" . esc_attr($preset_name) . "' alt='" . esc_attr($preset_name) . "'>\n";
            $content .= "<span class='tnpc-preset-label'>" . esc_html($user_preset->subject) . "</span>\n";
            $content .= "</div>";
        }

        // LOAD TNP PRESETS
        foreach (NewsletterComposer::presets as $id) {
            $preset = NewsletterComposer::instance()->get_preset_from_file($id);

            if (!empty($preset->version) && $preset->version == 2) {
                if (empty($preset->name)) {
                    $preset_name = $preset->subject;
                } else {
                    $preset_name = $preset->name;
                }
            } else {
                $preset_name = $preset->name;
            }

            if (!empty($preset->version) && $preset->version == 2) {
                $content .= '<div class="tnpc-preset tnpc-preset2" onclick="tnpc_load_preset(\'' . esc_attr($id) . '\')">';
            } else {
                $content .= '<div class="tnpc-preset" onclick="tnpc_load_preset(\'' . esc_attr($id) . '\')">';
            }
            $content .= '<img src="' . esc_attr($preset->icon) . '" title="' . esc_attr($preset_name) . '" alt="' . esc_attr($preset_name) . '">';
            $content .= '<span class="tnpc-preset-label">' . esc_html($preset_name) . '</span>';
            $content .= '</div>';
        }

        $templates = NewsletterComposer::instance()->get_templates();
        foreach ($templates as $template) {
            $content .= '<div class="tnpc-preset tnpc-preset2" onclick="tnpc_load_preset(\'' . esc_attr($template->id) . '\')">';
            $content .= '<img src="' . esc_attr($template->icon) . '" title="' . esc_attr($preset_name) . '" alt="' . esc_attr($template->name) . '">';
            $content .= '<span class="tnpc-preset-label">' . esc_html($template->name) . '</span>';
            $content .= '</div>';
        }

        if ($this->is_normal_context_request()) {
            $content .= $this->get_automated_spot_element();
            $content .= $this->get_autoresponder_spot_element();
            $content .= $this->get_raw_html_preset_element();
        }

        return $content;
    }

    private function get_automated_spot_element() {
        $result = "<div class='tnpc-preset'>";
        if (class_exists('NewsletterAutomated')) {
            $result .= "<a href='?page=newsletter_automated_index'>";
        } else {
            $result .= "<a href='https://www.thenewsletterplugin.com/automated?utm_source=composer&utm_campaign=plugin&utm_medium=automated'>";
        }
        $result .= "<img src='" . plugins_url('newsletter') . "/emails/images/automated.png' title='Automated addon' alt='Automated'/>";
        $result .= "<span class='tnpc-preset-label'>Daily, weekly and monthly newsletters</span></a>";
        $result .= "</div>";

        return $result;
    }

    private function get_autoresponder_spot_element() {
        $result = "<div class='tnpc-preset'>";
        if (class_exists('NewsletterAutoresponder')) {
            $result .= "<a href='?page=newsletter_autoresponder_index'>";
        } else {
            $result .= "<a href='https://www.thenewsletterplugin.com/autoresponder?utm_source=composer&utm_campaign=plugin&utm_medium=autoresponder' target='_blank'>";
        }
        $result .= "<img src='" . plugins_url('newsletter') . "/emails/images/autoresponder.png' title='Autoresponder addon' alt='Autoresponder'/>";
        $result .= "<span class='tnpc-preset-label'>Autoresponders</span></a>";
        $result .= "</div>";

        return $result;
    }

    private function get_raw_html_preset_element() {

        $result = "<div class='tnpc-preset tnpc-preset-html' onclick='location.href=\"" . wp_nonce_url('admin.php?page=newsletter_emails_new&id=rawhtml', 'newsletter-new') . "\"'>";
        $result .= "<img src='" . plugins_url('newsletter') . "/emails/images/rawhtml.png' title='RAW HTML' alt='RAW'/>";
        $result .= "<span class='tnpc-preset-label'>Raw HTML</span>";
        $result .= "</div>";

        $result .= "<div class='clear'></div>";
        $result .= "</div>";

        return $result;
    }

    private function set_test_subject_to($email) {
        if ($email->subject == '') {
            $email->subject = '[TEST] Dummy subject, it was empty (remember to set it)';
        } else {
            $email->subject = $email->subject . ' (TEST)';
        }
    }

    private function make_dummy_subscriber() {
        $dummy_user = new TNP_User();
        $dummy_user->id = 0;
        $dummy_user->email = 'john.doe@example.org';
        $dummy_user->name = 'John';
        $dummy_user->surname = 'Doe';
        $dummy_user->sex = 'n';
        $dummy_user->language = '';
        $dummy_user->ip = '';

        for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
            $profile_key = "profile_$i";
            $dummy_user->$profile_key = 'Dummy profile ' . $i;
        }

        return $dummy_user;
    }

    /**
     * Send an email to the test subscribers.
     *
     * @param TNP_Email $email Could be any object with the TNP_Email attributes
     * @param NewsletterControls $controls
     */
    function send_test_email($email, $controls) {
        if (!$email) {
            $controls->errors = __('Newsletter should be saved before send a test', 'newsletter');
            return;
        }

        $original_subject = $email->subject;
        $this->set_test_subject_to($email);

        $users = NewsletterUsersAdmin::instance()->get_test_users();
        if (count($users) == 0) {
            $controls->errors = '' . __('There are no test subscribers to send to', 'newsletter') .
                    '. <a href="https://www.thenewsletterplugin.com/plugins/newsletter/subscribers-module#test" target="_blank"><strong>' .
                    __('Read more', 'newsletter') . '</strong></a>.';
        } else {
            $r = Newsletter::instance()->send($email, $users, true);
            $emails = [];
            foreach ($users as $user) {
                $emails[] = '<a href="admin.php?page=newsletter_users_edit&id=' . $user->id . '" target="_blank">' . $user->email . '</a>';
            }
            if (is_wp_error($r)) {
                $controls->errors = 'Something went wrong. Check the error logs on status page.<br>';
                $controls->errors .= __('Test subscribers:', 'newsletter');
                $controls->errors .= ' ' . implode(', ', $emails);
                $controls->errors .= '<br>';
                $controls->errors .= '<strong>' . esc_html($r->get_error_message()) . '</strong><br>';
                $controls->errors .= '<a href="https://www.thenewsletterplugin.com/documentation/email-sending-issues" target="_blank"><strong>' . __('Read more about delivery issues', 'newsletter') . '</strong></a>.';
            } else {
                $controls->messages = __('Test sent to:', 'newsletter');
                //$controls->messages .= __('Test subscribers:', 'newsletter');

                $controls->messages .= ' ' . implode(', ', $emails);
                $controls->messages .= '.<br>';
                $controls->messages .= 'If the message does not shows up on the mailbox, check the spam folder and run a test from the '
                        . '<a href="?page=newsletter_system_delivery" target="_blank"><strong>System/Delivery panel</strong></a>.<br>';

                $controls->messages .= '<a href="https://www.thenewsletterplugin.com/documentation/subscribers#test" target="_blank"><strong>' .
                        __('Read more about test subscribers', 'newsletter') . '</strong></a>.<br>';
                $controls->messages .= '<a href="https://www.thenewsletterplugin.com/documentation/email-sending-issues" target="_blank"><strong>' . __('Read more about delivery issues', 'newsletter') . '</strong></a>.';
            }
        }
        $email->subject = $original_subject;
    }

    /**
     * Send an email to the test subscribers.
     *
     * @param TNP_Email $email Could be any object with the TNP_Email attributes
     * @param string $email_address
     *
     * @throws Exception
     */
    function send_test_newsletter_to_email_address($email, $email_address) {

        if (!$email) {
            throw new Exception(__('Newsletter should be saved before send a test', 'newsletter'));
        }

        $this->set_test_subject_to($email);

        $dummy_subscriber = $this->get_user($email_address);

        if (!$dummy_subscriber) {
            $dummy_subscriber = $this->get_dummy_user();
            $dummy_subscriber->email = $email_address;
        }

        $result = Newsletter::instance()->send($email, [$dummy_subscriber], true);

        if (is_wp_error($result)) {
            $error_message = 'Something went wrong. Check the error logs on the System/Logs page.<br>';
            $error_message .= '<br>';
            $error_message .= '<strong>' . esc_html($result->get_error_message()) . '</strong><br>';
            $error_message .= '<a href="https://www.thenewsletterplugin.com/documentation/email-sending-issues" target="_blank"><' . __('Read more about delivery issues', 'newsletter') . '</a>.';
            throw new Exception($error_message);
        }

        $messages = __('Test sent to:', 'newsletter');

        $messages .= ' ' . esc_html($email_address);
        $messages .= '.<br>';
        $messages .= 'If the message does not shows up on the mailbox, check the spam folder and run a test from the '
                . '<a href="?page=newsletter_system_delivery" target="_blank"><strong>System/Delivery panel</strong></a>.<br>';
        $messages .= '<a href="https://www.thenewsletterplugin.com/documentation/email-sending-issues" target="_blank">' . __('Read more about delivery issues', 'newsletter') . '</a>.';

        return $messages;
    }
}
