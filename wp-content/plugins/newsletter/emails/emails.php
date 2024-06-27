<?php

defined('ABSPATH') || exit;

class NewsletterEmails extends NewsletterModule {

    static $instance;

    const PRESET_EMAIL_TYPE = 'composer_template';
    const EDITOR_COMPOSER = 2;
    const EDITOR_HTML = 1;
    const EDITOR_TINYMCE = 0;

    /**
     * @return NewsletterEmails
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function __construct() {
        parent::__construct('emails');
        add_action('newsletter_action', array($this, 'hook_newsletter_action'), 13, 3);
        add_action('newsletter_init', [$this, 'hook_newsletter_init']);
    }

    function hook_newsletter_init() {

    }

    function options_decode($options) {
        return NewsletterComposer::options_decode($options);
    }

    /**
     *
     * @param array $options Options array
     */
    function options_encode($options) {
        return NewsletterComposer::options_encode($options);
    }

    /**
     * Builds and returns the HTML with the form fields of a specific block.
     *
     * @global wpdb $wpdb
     */

    /**
     * Regenerates a saved composed email rendering each block. Regeneration is
     * conditioned (possibly) by the context. The context is usually passed to blocks
     * so they can act in the right manner.
     *
     * $context contains a type and, for automated, the last_run.
     *
     * $email can actually be even a string containing the full newsletter HTML code.
     *
     * @param TNP_Email $email
     * @return string
     */
    function regenerate($email, $context = []) {
        return NewsletterComposer::instance()->regenerate($email, $context);
    }

    function remove_block_data($text) {
        // TODO: Lavorare!
        return $text;
    }

    /** Returns the correct admin page to edit the newsletter with the correct editor. */
    function get_editor_url($email_id, $editor_type) {
        switch ($editor_type) {
            case NewsletterEmails::EDITOR_COMPOSER:
                return admin_url("admin.php") . '?page=newsletter_emails_composer&id=' . $email_id;
            case NewsletterEmails::EDITOR_HTML:
                return admin_url("admin.php") . '?page=newsletter_emails_editorhtml&id=' . $email_id;
            case NewsletterEmails::EDITOR_TINYMCE:
                return admin_url("admin.php") . '?page=newsletter_emails_editortinymce&id=' . $email_id;
        }
    }

    /**
     * Returns the button linked to the correct "edit" page for the passed newsletter. The edit page can be an editor
     * or the targeting page (it depends on newsletter status).
     *
     * @param TNP_Email $email
     */
    function get_edit_button($email, $only_icon = false) {

        $editor_type = $this->get_editor_type($email);
        if ($email->status == 'new') {
            $edit_url = $this->get_editor_url($email->id, $editor_type);
        } else {
            $edit_url = 'admin.php?page=newsletter_emails_edit&id=' . $email->id;
        }
        switch ($editor_type) {
            case NewsletterEmails::EDITOR_COMPOSER:
                $icon_class = 'th-large';
                break;
            case NewsletterEmails::EDITOR_HTML:
                $icon_class = 'code';
                break;
            default:
                $icon_class = 'edit';
                break;
        }
        if ($only_icon) {
            return '<a class="button-primary" href="' . $edit_url . '" title="' . esc_attr__('Edit', 'newsletter') . '">' .
                    '<i class="fas fa-' . $icon_class . '"></i></a>';
        } else {
            return '<a class="button-primary" href="' . $edit_url . '" title="' . esc_attr__('Edit', 'newsletter') . '">' .
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

    /**
     *
     * @param type $action
     * @param type $user
     * @param type $email
     * @return type
     * @global wpdb $wpdb
     */
    function hook_newsletter_action($action, $user, $email) {
        global $wpdb;

        switch ($action) {
            case 'v':
            case 'view':
                $id = $_GET['id'];
                if ($id == 'last') {
                    $email = $wpdb->get_row("select * from " . NEWSLETTER_EMAILS_TABLE . " where private=0 and type='message' and status='sent' order by send_on desc limit 1");
                } else {
                    $email = $this->get_email($id);
                }
                if (empty($email)) {
                    header("HTTP/1.0 404 Not Found");
                    die('Email not found');
                }

                if (!$this->is_allowed()) {

                    if ($email->status == 'new') {
                        header("HTTP/1.0 404 Not Found");
                        die('Not sent yet');
                    }

                    if ($email->private == 1) {
                        if (!$user) {
                            header("HTTP/1.0 404 Not Found");
                            die('No available for online view');
                        }
                        $sent = $wpdb->get_row($wpdb->prepare("select * from " . NEWSLETTER_SENT_TABLE . " where email_id=%d and user_id=%d limit 1", $email->id, $user->id));
                        if (!$sent) {
                            header("HTTP/1.0 404 Not Found");
                            die('No available for online view');
                        }
                    }
                }


                header('Content-Type: text/html;charset=UTF-8');
                header('X-Robots-Tag: noindex,nofollow,noarchive');
                header('Cache-Control: no-cache,no-store,private');

                $message = $this->replace($email->message, $user, $email);
                if (Newsletter::instance()->get_option('do_shortcodes')) {
                    $message = do_shortcode($message);
                }
                echo apply_filters('newsletter_view_message', $message);

                die();
                break;

            case 'json':
                if (!current_user_can('administrator')) {
                    header("HTTP/1.0 403 Not Found");
                    die('Not allowed');
                }
                $email = $this->get_email((int)$_GET['id']);

                if (empty($email)) {
                    header("HTTP/1.0 404 Not Found");
                    die('Email not found');
                }

                header('Content-Type: application/json;charset=UTF-8');
                header('X-Robots-Tag: noindex,nofollow,noarchive');
                header('Cache-Control: no-cache,no-store,private');

                echo NewsletterComposer::instance()->to_json($email);

                die();
                break;

            case 'emails-css':
                $email_id = (int) $_GET['id'];

                $body = $this->get_email_field($email_id, 'message');

                $x = strpos($body, '<style');
                if ($x === false)
                    return;

                $x = strpos($body, '>', $x);
                $y = strpos($body, '</style>');

                header('Content-Type: text/css;charset=UTF-8');

                echo substr($body, $x + 1, $y - $x - 1);

                die();
                break;

            case 'emails-composer-css':
                header('Cache: no-cache');
                header('Content-Type: text/css');
                echo $this->get_composer_css();
                die();
                break;

            case 'emails-preview':
                require_once NEWSLETTER_DIR . '/admin.php';
                if (!$this->is_allowed()) {
                    die('Not enough privileges');
                }

                if (!check_admin_referer('view')) {
                    die();
                }

                $theme_id = $_GET['id'];
                $theme = NewsletterEmailsAdmin::instance()->themes->get_theme($theme_id);

                // Used by theme code
                $theme_options = NewsletterEmailsAdmin::instance()->themes->get_options($theme_id);

                $theme_url = $theme['url'];

                header('Content-Type: text/html;charset=UTF-8');

                include $theme['dir'] . '/theme.php';

                die();
                break;

            case 'emails-preview-text':
                header('Content-Type: text/plain;charset=UTF-8');
                if (!$this->is_allowed()) {
                    die('Not enough privileges');
                }

                if (!check_admin_referer('view')) {
                    die();
                }

                // Used by theme code
                $theme_options = $this->get_current_theme_options();

                $file = include $theme['dir'] . '/theme-text.php';

                if (is_file($file)) {
                    include $file;
                }

                die();
                break;

            case 'emails-create':
                require_once NEWSLETTER_DIR . '/admin.php';
                // Newsletter from themes are created on frontend context because sometime WP themes change the way the content,
                // excerpt, thumbnail are extracted.
                if (!$this->is_allowed()) {
                    die('Not enough privileges');
                }

                require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
                $controls = new NewsletterControls();

                if (!$controls->is_action('create')) {
                    die('Wrong call');
                }

                $theme_id = $controls->data['id'];
                $theme = NewsletterEmailsAdmin::instance()->themes->get_theme($theme_id);

                if (!$theme) {
                    die('invalid theme');
                }

                NewsletterEmailsAdmin::instance()->themes->save_options($theme_id, $controls->data);

                $email = array();
                $email['status'] = 'new';
                $email['subject'] = ''; //__('Here the email subject', 'newsletter');
                $email['track'] = Newsletter::instance()->get_option('track');
                $email['send_on'] = time();
                $email['editor'] = NewsletterEmails::EDITOR_TINYMCE;
                $email['type'] = 'message';

                $theme_options = NewsletterEmailsAdmin::instance()->themes->get_options($theme_id);

                $theme_url = $theme['url'];
                $theme_subject = '';

                ob_start();
                include $theme['dir'] . '/theme.php';
                $email['message'] = ob_get_clean();

                if (!empty($theme_subject)) {
                    $email['subject'] = $theme_subject;
                }

                if (file_exists($theme['dir'] . '/theme-text.php')) {
                    ob_start();
                    include $theme['dir'] . '/theme-text.php';
                    $email['message_text'] = ob_get_clean();
                } else {
                    $email['message_text'] = 'You need a modern email client to read this email. Read it online: {email_url}.';
                }

                $email = $this->save_email($email);

                $edit_url = $this->get_editor_url($email->id, $email->editor);

                header('Location: ' . $edit_url);

                die();
                break;
        }
    }

    function get_blocks() {
        return NewsletterComposer::instance()->get_blocks();
    }

    function get_block($id) {
        return NewsletterComposer::instance()->get_block($id);
    }

    function get_composer_css() {
        return NewsletterComposer::instance()->get_composer_css();
    }

    function get_composer_backend_css() {
        return NewsletterComposer::instance()->get_composer_backend_css();
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
                $options['inline_edits'] = $this->options_decode($options['inline_edits']);
            }

            // Restore inline edits from data-json
            // coming from inline editing
            // and merge with current inline edit
            if (isset($_POST['encoded_options'])) {
                $decoded_options = $this->options_decode($_POST['encoded_options']);

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

    /**
     * Used by Instasend
     *
     * @deprecated since version 7.8.0
     *
     * @param type $block_id
     * @param type $wrapper
     * @param type $options
     * @return type
     */
    function render_block($block_id, $wrapper, $options) {
        return NewsletterComposer::instance()->render_block($block_id, $wrapper, $options);
    }
}

NewsletterEmails::instance();
