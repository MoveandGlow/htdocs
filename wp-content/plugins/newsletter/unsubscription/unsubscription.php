<?php

defined('ABSPATH') || exit;

class NewsletterUnsubscription extends NewsletterModule {

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
        parent::__construct('unsubscription');

        add_filter('newsletter_replace', [$this, 'hook_newsletter_replace'], 10, 4);
        add_filter('newsletter_page_text', [$this, 'hook_newsletter_page_text'], 10, 3);
        add_filter('newsletter_message', [$this, 'hook_newsletter_message'], 9, 3);

        add_action('newsletter_action', [$this, 'hook_newsletter_action'], 11, 3);
        add_action('newsletter_action_dummy', [$this, 'hook_newsletter_action_dummy'], 11, 3);

        if (!is_admin() || defined('DOING_AJAX') && DOING_AJAX) {
            add_shortcode('newsletter_unsubscribe_button', [$this, 'shortcode_newsletter_unsubscribe_button']);
            add_shortcode('newsletter_resubscribe_button', [$this, 'shortcode_newsletter_resubscribe_button']);
        }
    }

    /**
     *
     * @param arrays $attrs
     * @param string $content
     * @return string
     */
    function shortcode_newsletter_unsubscribe_button($attrs, $content = '') {
        $user = $this->get_current_user();

        if (!$user || !$user->_trusted) {
            return '';
        }

        $label = empty($attrs['label']) ? __('Unsubscribe', 'newsletter') : $attrs['label'];

        $b = '<form action="' . esc_attr($this->build_action_url('uc')) . '" method="post" class="tnp-unsubscribe">';
        $b .= '<input type="hidden" name="nk" value="' . esc_attr($this->get_user_key($user)) . '">';
        $b .= '<button class="tnp-submit">' . esc_html($label) . '</button>';
        $b .= '</form>';
        return $b;
    }

    function shortcode_newsletter_resubscribe_button($attrs, $content = '') {
        $user = $this->get_current_user();

        if (!$user || !$user->_trusted) {
            return '';
        }

        $label = empty($attrs['label']) ? __('Resubscribe', 'newsletter') : $attrs['label'];
        $b = '<form action="' . esc_attr($this->build_action_url('reactivate')) . '" method="post" class="tnp-reactivate">';
        $b .= '<input type="hidden" name="nk" value="' . esc_attr($this->get_user_key($user)) . '">';
        $b .= '<button class="tnp-submit">' . esc_html($label) . '</button>';
        $b .= '</form>';
        return $b;
    }

    function hook_newsletter_action_dummy($action, $user, $email) {
        if (!in_array($action, ['u', 'uc', 'ocu', 'reactivate'])) {
            return;
        }

        switch ($action) {
            case 'u':
                $url = $this->build_message_url(null, 'unsubscribe', $user, $email);
                $this->redirect($url);
                break;

            case 'uc':
                $this->send_unsubscribed_email($user);
                $url = $this->build_message_url(null, 'unsubscribed', $user, $email);
                $this->redirect($url);
                break;

            case 'reactivate':
                $url = $this->build_message_url(null, 'reactivated', $user);
                $this->redirect($url);
                break;
        }
    }

    /**
     * @param string $action
     * @param TNP_User $user
     * @param TNP_Email $email
     */
    function hook_newsletter_action($action, $user, $email) {

        if (!in_array($action, ['u', 'uc', 'ocu', 'reactivate'])) {
            return;
        }

        if (!$user || !$user->_trusted) {
            $this->dienow(__('Subscriber not found', 'newsletter'), 'From a test newsletter or already deleted or using the wrong subscriber key in the URL', 404);
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
//            if (strpos($agent, 'yahoomailproxy') !== false) {
//                return;
//            }
            if (strpos($agent, 'googlebot') !== false) {
                die();
            }
            if (strpos($agent, 'yandexbot') !== false) {
                die();
            }
            if (strpos($agent, 'bingbot') !== false) {
                die();
            }
            if (strpos($agent, 'bingpreview') !== false) {
                die();
            }
            if (strpos($agent, 'microsoftpreview') !== false) {
                die();
            }
            if (strpos($agent, 'bytespider') !== false) {
                die();
            }
            if (strpos($agent, 'headlesschrome') !== false) {
                die();
            }
        }

        //if ($this->get_main_option('mode') == '1' && $action === 'u') {
        //    $action = 'uc';
        //}
        // Action conversion from old links from the email headers
        if (isset($_POST['List-Unsubscribe']) && 'One-Click' === $_POST['List-Unsubscribe']) {
            $action = 'ocu';
        }

        // Show the antibot and stop
        if (in_array($action, ['u', 'uc', 'reactivate'])) {
            if (!$this->antibot_form_check(false)) {
                $this->antibot_unsubscription('');
            }
        }

        switch ($action) {
            case 'u':
                $url = $this->build_message_url(null, 'unsubscribe', $user, $email);
                $this->redirect($url);
                break;

            case 'uc':
                $this->unsubscribe($user, $email);
                $url = $this->build_message_url(null, 'unsubscribed', $user, $email);
                setcookie('newsletter', '', 0, '/');
                $this->redirect($url);
                break;

            case 'ocu': // One Click Unsubscribe rfc8058
                if (isset($_POST['List-Unsubscribe']) && 'One-Click' === $_POST['List-Unsubscribe']) {
                    $this->unsubscribe($user, $email, 'unsubscribe-rfc8058');
                    die('ok');
                }
                die('ko');
                break;

            case 'reactivate':
                $this->reactivate($user);
                setcookie('newsletter', $user->id . '-' . $user->token, time() + 60 * 60 * 24 * 365, '/');
                $url = $this->build_message_url(null, 'reactivated', $user);
                $this->redirect($url);
                break;
        }
    }

    /**
     * Unsubscribes the subscriber from the request. Die on subscriber extraction failure.
     *
     * @return TNP_User
     */
    function unsubscribe($user, $email = null, $type = 'unsubscribe') {
        global $wpdb;

        if ($user->status === TNP_User::STATUS_UNSUBSCRIBED) {
            return $user;
        }

        $this->set_user_status($user, TNP_User::STATUS_UNSUBSCRIBED);

        $this->add_user_log($user, $type);

        do_action('newsletter_user_unsubscribed', $user);

        if ($email) {
            $wpdb->update(NEWSLETTER_USERS_TABLE, array('unsub_email_id' => (int) $email->id, 'unsub_time' => time()), array('id' => $user->id));
        }

        $this->send_unsubscribed_email($user);

        $this->notify_admin($user);

        return $user;
    }

    function send_unsubscribed_email($user, $force = false) {
        if (!$force && !empty($this->get_main_option('unsubscribed_disabled'))) {
            return true;
        }

        $this->switch_language($user->language);

        $message = do_shortcode($this->get_text('unsubscribed_message'));
        $subject = $this->get_text('unsubscribed_subject');

        $res = NewsletterSubscription::instance()->mail($user, $subject, $message);
        $this->restore_language();
        return $res;
    }

    function notify_admin($user) {

        if (empty($this->get_main_option('notify'))) {
            return;
        }

        $message = $this->generate_admin_notification_message($user);
        $email = trim($this->get_main_option('notify_email'));
        $subject = $this->generate_admin_notification_subject('New cancellation');

        Newsletter::instance()->mail($email, $subject, ['html' => $message]);
    }

    /**
     * Reactivate the subscriber extracted from the request setting his status
     * to confirmed and logging. No email are sent. Dies on subscriber extraction failure.
     *
     * @return TNP_User
     */
    function reactivate($user = null) {
        $this->set_user_status($user, TNP_User::STATUS_CONFIRMED);
        $this->add_user_log($user, 'reactivate');
        do_action('newsletter_user_reactivated', $user);
    }

    function hook_newsletter_replace($text, $user, $email, $html = true) {

        if ($user) {
            $text = $this->replace_url($text, 'unsubscription_confirm_url', $this->build_action_url('uc', $user, $email));
            $text = $this->replace_url($text, 'unsubscription_url', $this->build_action_url('u', $user, $email));
            $text = $this->replace_url($text, 'unsubscribe_url', $this->build_action_url('u', $user, $email));
            $text = $this->replace_url($text, 'reactivate_url', $this->build_action_url('reactivate', $user, $email));
        } else {
            $text = $this->replace_url($text, 'unsubscription_confirm_url', $this->build_action_url('nul'));
            $text = $this->replace_url($text, 'unsubscription_url', $this->build_action_url('nul'));
            $text = $this->replace_url($text, 'unsubscribe_url', $this->build_action_url('nul'));
        }

        return $text;
    }

    /**
     * Language and locale are already defined in this hook.
     *
     * @param type $text
     * @param type $key
     * @param type $user
     * @return type
     */
    function hook_newsletter_page_text($text, $key, $user = null) {

        // For this module?
        if (!in_array($key, ['unsubscribe', 'unsubscribed', 'reactivated'])) {
            return $text;
        }

        if (!$user || !$user->_trusted) {
            return $this->get_text('error_text');
        }

        $admin_notice = '';
        if ($user->_dummy) {
            $admin_notice = '<p style="background-color: #eee; color: #000; padding: 1rem; margin: 1rem 0"><strong>Visible only to administrator</strong>. Preview of the content with a dummy subscriber. <a href="' . admin_url('admin.php?page=newsletter_unsubscription_index') . '" target="_blank">Edit this content</a>.</p>';
        }

        $message = $this->get_text($key . '_text');

        return $admin_notice . $message;
    }

    /**
     *
     * @param TNP_Mailer_Message $message
     * @param TNP_Email $email
     * @param TNP_User $user
     * @return TNP_Mailer_Message
     */
    function hook_newsletter_message($message, $email, $user) {

        if (!empty($this->get_main_option('disable_unsubscribe_headers'))) {
            return $message;
        }

        $list_unsubscribe_values = [];
        if (!empty($this->get_main_option('list_unsubscribe_mailto_header'))) {
            $unsubscribe_address = $this->get_main_option('list_unsubscribe_mailto_header');
            $list_unsubscribe_values[] = "<mailto:$unsubscribe_address?subject=unsubscribe>";
        }

        $unsubscribe_action_url = $this->build_action_url('ocu', $user, $email);
        $list_unsubscribe_values[] = "<$unsubscribe_action_url>";

        $message->headers['List-Unsubscribe'] = implode(', ', $list_unsubscribe_values);
        $message->headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';

        return $message;
    }
}

NewsletterUnsubscription::instance();
