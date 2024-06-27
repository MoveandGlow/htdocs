<?php
defined('ABSPATH') || exit;

class NewsletterSubscription extends NewsletterModule {

    const MESSAGE_CONFIRMED = 'confirmed';
    const OPTIN_DOUBLE = 0;
    const OPTIN_SINGLE = 1;

    static $instance;
    var $popup_test = false;

    /**
     * @return NewsletterSubscription
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {

        parent::__construct('subscription');

        // Must be called after the Newsletter::hook_init, since some constants are defined
        // there.
        add_action('init', [$this, 'hook_init'], 90);
    }

    function hook_init() {

        add_action('newsletter_action', [$this, 'hook_newsletter_action'], 10, 3);
        add_filter('newsletter_page_text', [$this, 'hook_newsletter_page_text'], 10, 3);

        // The form is sometimes retrieved via AJAX
        if (!is_admin() || defined('DOING_AJAX') && DOING_AJAX) {
            add_shortcode('newsletter_form', [$this, 'shortcode_newsletter_form']);
            add_shortcode('newsletter_field', [$this, 'shortcode_newsletter_field']);
        }

        if (!empty($this->get_option('bottom_enabled', 'inject'))) {
            add_filter('the_content', [$this, 'hook_the_content'], 99);
        }

        $this->popup_test = isset($_GET['tnp-popup-test']) && Newsletter::instance()->is_allowed();
        if (!empty($this->get_option('enabled', 'popup')) || $this->popup_test) {
            add_action('wp_footer', [$this, 'hook_wp_footer'], 99);
            add_action('wp_enqueue_scripts', [$this, 'hook_wp_enqueue_scripts']);
        }
        add_action('newsletter_action', [$this, 'hook_newsletter_action_popup']);
    }

    function hook_wp_footer() {
        if (!$this->popup_test) {
            $user = Newsletter::instance()->get_current_user();
            if ($user && $user->status === 'C') {
                return;
            }
        }
        ?>
        <div id="tnp-modal">
            <div id="tnp-modal-content">
                <div id="tnp-modal-close">&times;</div>
                <div id="tnp-modal-body">
                </div>
            </div>
        </div>

        <script>
            var tnp_popup_test = <?php echo $this->popup_test ? 'true' : 'false' ?>;
            var tnp_popup_url = '<?php echo esc_js(Newsletter::add_qs(home_url('/'), 'na=popup&language=' . urlencode($this->language()))); ?>';
            var tnp_popup_action = '<?php echo esc_js($this->build_action_url('sa')); ?>';
        </script>
        <script src="<?php echo esc_attr(plugins_url('assets/popup.js', __FILE__)) ?>" async></script>
        <?php
    }

    function hook_wp_enqueue_scripts() {

        wp_enqueue_style('newsletter-popup', plugins_url('assets/popup.css', __FILE__), [], NEWSLETTER_VERSION);
        if (is_rtl()) {
            //wp_enqueue_style('newsletter-leads-rtl', plugins_url('newsletter-leads') . '/css/leads-rtl.css', [], $this->version);
        }
    }

    function hook_newsletter_action_popup($action) {
        switch ($action) {
            case 'popup':
                header('Content-Type: text/html;charset=UTF-8');
                echo $this->get_option('text', 'popup');
                echo NewsletterSubscription::instance()->get_subscription_form('popup', null,
                        ['class' => 'tnp-subscription-popup', 'id' => 'tnp-subscription-popup']);
                die();
        }
    }

    function hook_the_content($content) {
        if (!is_single()) {
            return $content;
        }

        if ('post' !== get_post_type()) {
            return $content;
        }

        $style = '';

        return $content
                . '<div class="tnp-subscription-posts" id="tnp-subscription-posts"'
                . ' style="' . esc_attr($style) . '"'
                . '>'
                . $this->get_option('bottom_text', 'inject')
                . $this->get_subscription_form('posts_bottom')
                . '</div>';
    }

    /**
     *
     * @global wpdb $wpdb
     * @return mixed
     */
    function hook_newsletter_action($action, $user, $email) {
        switch ($action) {
            case 'profile-change':
                if ($this->antibot_form_check()) {

                    if (!$user || $user->status != TNP_user::STATUS_CONFIRMED || !$user->_trusted) {
                        $this->dienow('Subscriber not found or not confirmed.', 'Even the wrong subscriber token can lead to this error.', 404);
                    }

                    if (!$email) {
                        $this->dienow('Newsletter not found', 'The newsletter containing the link has been deleted.', 404);
                    }

                    if (isset($_REQUEST['list'])) {
                        $list_id = (int) $_REQUEST['list'];

                        // Check if the list is public
                        $list = $this->get_list($list_id);
                        if (!$list || $list->status == TNP_List::STATUS_PRIVATE) {
                            $this->dienow('List change not allowed.', 'Please check if the list is marked as "private".', 400);
                        }

                        if (empty($_REQUEST['redirect'])) {
                            $url = home_url();
                        } else {
                            $url = esc_url_raw($_REQUEST['redirect']);
                        }
                        $this->set_user_list($user, $list_id, (int) $_REQUEST['value']);

                        if (strpos($url, home_url()) !== 0) {
                            $this->dienow('Invalid redirect.', 'Please check the redirect URL set on the newsletter, it should match your site URL.', 400);
                        }

                        $user = $this->get_user($user->id);
                        $this->add_user_log($user, 'cta');
                        NewsletterStatistics::instance()->add_click($url, $user->id, $email->id);
                        wp_redirect($url);
                        die();
                    }
                } else {
                    $this->request_to_antibot_form('Continue');
                }

                die();

            // normal subscription
            case 's':
            case 'subscribe':

                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    $this->dienow('Invalid request', 'The subscription request was not made with a HTTP POST', 400);
                }

                $options_antibot = $this->get_options('antispam');

                $captcha = !empty($options_antibot['captcha']);

                if (!empty($_GET['_wp_amp_action_xhr_converted']) || !empty($options_antibot['disabled']) || $this->antibot_form_check($captcha)) {

                    $subscription = $this->build_subscription();

                    $user = $this->subscribe2($subscription);

                    if (is_wp_error($user)) {
                        if ($user->get_error_code() === 'exists') {
                            $this->redirect($this->build_message_url('', 'error'));
                        }
                        $this->dienow(__('Registration failed.', 'newsletter'), $user->get_error_message(), 400);
                    }

                    $this->set_user_cookie($user);

                    if ($user->_activation) {
                        $this->redirect_to_confirmation($user);
                    } else {
                        $this->redirect_to_confirmed($user);
                    }
                } else {
                    $language = $this->sanitize_language($_REQUEST['nlang'] ?? '');
                    Newsletter::instance()->switch_language($language);
                    $this->antibot_subscription($this->get_form_text('subscribe'), $captcha);
                }
                die();

            // AJAX subscription
            case 'sa':
            case 'ajaxsub':

                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    $this->dienow('Invalid request');
                }

                $subscription = $this->build_subscription();

                $user = $this->subscribe2($subscription);

                if (is_wp_error($user)) {
                    if ($user->get_error_code() === 'exists') {
                        echo $this->get_text('error_text');
                        die();
                    } else {
                        $this->dienow(__('Registration failed.', 'newsletter'), $user->get_error_message(), 400);
                    }
                }

                $this->set_user_cookie($user);

                $this->switch_language($user->language);
                if ($user->_activation) {
                    $message = $this->replace($this->get_text('confirmation_text'), $user);
                } else {
                    $message = $this->replace($this->get_text('confirmed_text'), $user);
                    $message .= $this->get_option('confirmed_tracking');
                }

                echo $message;
                die();

            case 'c':
            case 'confirm':
                if (!$user || !$user->_trusted) {
                    $this->dienow(__('Subscriber not found.', 'newsletter'), 'Or it is not present or the secret key does not match.', 404);
                }

                if ($this->antibot_form_check()) {
                    $user = $this->confirm($user);
                    $this->set_user_cookie($user);
                    $this->redirect_to_confirmed($user);
                } else {
                    $this->antibot_subscription('Confirm');
                }
                die();
        }
    }

    function get_options($set = '', $language = null) {
        // This is a patch for addon using the "profile" set which originally contained the
        // form options. This patch can create a problem if someone calls this method to get the actual
        // "profile" set which is the configuration of the profile page.
        // The correct call would be NewsletterProfile::instance()->get_options().
        if ($set === 'profile') {
            $set = 'form';
        }
        return parent::get_options($set, $language);
    }

    function get_form_options() {
        return $this->get_options('form');
    }

    function get_form_option($key) {
        return $this->get_option($key, 'form');
    }

    function get_form_text($key) {
        return $this->get_text($key, 'form');
    }

    function set_updated($user, $time = 0, $ip = '') {
        global $wpdb;
        if (!$time) {
            $time = time();
        }

        if (!$ip) {
            $ip = $this->get_remote_ip();
        }
        $ip = $this->process_ip($ip);

        if (is_object($user)) {
            $id = $user->id;
        } else if (is_array($user)) {
            $id = $user['id'];
        }

        $id = (int) $id;

        $wpdb->update(NEWSLETTER_USERS_TABLE, array('updated' => $time, 'ip' => $ip, 'geo' => 0), array('id' => $id));
    }

    /**
     * Builds a default subscription object to be used to collect data and subscription options.
     *
     * @return TNP_Subscription
     */
    function get_default_subscription($language = null) {
        $subscription = new TNP_Subscription();

        $language = is_null($language) ? $this->language() : $language;

        $subscription->data->language = $language;
        $subscription->optin = $this->is_double_optin() ? 'double' : 'single';

        $multiple = (int) $this->get_main_option('multiple');

        switch ($multiple) {
            case 0: $subscription->if_exists = TNP_Subscription::EXISTING_ERROR;
                break;
            case 1: $subscription->if_exists = TNP_Subscription::EXISTING_DOUBLE_OPTIN;
                break;
            case 2: $subscription->if_exists = TNP_Subscription::EXISTING_SINGLE_OPTIN;
        }

        $lists = $this->get_lists();
        foreach ($lists as $list) {
            if ($list->forced) {
                $subscription->data->lists['' . $list->id] = 1;
                continue;
            }
            // Enforced by language
            if ($language && in_array($language, $list->languages)) {
                $subscription->data->lists['' . $list->id] = 1;
            }
        }

        $welcome_email = (int) $this->get_option('welcome_email');
        switch ($welcome_email) {
            case 1: $subscription->welcome_email_id = (int) $this->get_option('welcome_email_id');
                break;
            case 2: $subscription->welcome_email_id = -1;
        }

//        if ($this->get_option('autoresponder')) {
//            $subscription->autoresponders = [$this->get_option('autoresponder')];
//        }
        return $subscription;
    }

    /**
     *
     * @param TNP_Subscription $subscription
     *
     * @return TNP_User|WP_Error
     */
    function subscribe2(TNP_Subscription $subscription) {

        if ($this->logger->is_debug) {
            $this->logger->debug($subscription);
        }

        // Fill in optional data

        if (empty($subscription->data->ip)) {
            $subscription->data->ip = $this->get_remote_ip();
        }


        // Spam check before sanitization: we could remove relevant information to evaluate spam

        if ($subscription->spamcheck) {
            // TODO: Use autoload
            require_once NEWSLETTER_INCLUDES_DIR . '/antispam.php';
            $antispam = NewsletterAntispam::instance();
            $res = $antispam->is_spam($subscription, true);
            if (is_wp_error($res)) {
                Newsletter\Logs::add('antispam', $res->get_error_code() . ' - ' . $res->get_error_message());
                return new WP_Error('spam', $res->get_error_message());
            }
        }

        // Exists?
        $user = $this->get_user_by_email($subscription->data->email);

        $subscription = apply_filters('newsletter_subscription', $subscription, $user);

        $this->sanitize_subscription_data($subscription->data);

        if (empty($subscription->data->email)) {
            return new WP_Error('email', 'Wrong email address');
        }

        // GDPR
        $subscription->data->ip = $this->process_ip($subscription->data->ip);

        // Do we accept repeated subscriptions?
        if ($user != null && $user->status !== TNP_User::STATUS_NOT_CONFIRMED) {
            $this->logger->info('Existing subscriber: ' . $user->status);

            if ($user->status == TNP_User::STATUS_BOUNCED) {
                return new WP_Error('bounced', 'Subscriber blocked since bounced. Contact the site administrator.');
            }

            if ($user->status == TNP_User::STATUS_COMPLAINED) {
                return new WP_Error('complained', 'Subscriber blocked since complained. Contact the site administrator.');
            }

            if ($user->status == TNP_User::STATUS_UNSUBSCRIBED) {
                return new WP_Error('unsubscribed', 'Subscriber blocked since unsubscribed. Contact the site administrator.');
            }

            if ($subscription->if_exists === TNP_Subscription::EXISTING_ERROR) {
                return new WP_Error('exists', 'Email address already registered and Newsletter sets to block repeated registrations. You can change this behavior or the user message above on subscription configuration panel.');
            }

            if ($user->status == TNP_User::STATUS_CONFIRMED) {

                if ($subscription->if_exists === TNP_Subscription::EXISTING_SINGLE_OPTIN) {
                    $subscription->data->merge_in($user);
                    $user->updated = time();
                    $user = apply_filters('newsletter_user_subscribe', $user);
                    $user = $this->save_user($user);
                    do_action('newsletter_user_post_subscribe', $user);
                    $user->_activation = false;
                    $user->_trusted = false;
                    $user->_new = false;
                } else {
                    $user = apply_filters('newsletter_user_subscribe', $user);
                    set_transient('newsletter_subscription_' . $user->id, $subscription, 3600 * 24);
                    $user->_activation = true;
                    $user->_trusted = false;
                    $user->_new = false;
                }
            }
        } else {

            // Unconfirmed subscribers are treated as new
            if (!$user) {
                $user = new TNP_User();
            } else {
                $user->name = '';
                $user->surname = '';
                $user->language = '';
                $user->ip = '';
                for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
                    $field = 'profile_' . $i;
                    $user->$field = '';
                }
                for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
                    $field = 'list_' . $i;
                    $user->$field = 0;
                }
            }

            $subscription->data->merge_in($user);
            $user->token = $this->get_token();
            $user->status = $subscription->optin == 'single' ? TNP_User::STATUS_CONFIRMED : TNP_User::STATUS_NOT_CONFIRMED;

            $user->updated = time();
            $user = apply_filters('newsletter_user_subscribe', $user);
            $user = $this->save_user($user);
            do_action('newsletter_user_post_subscribe', $user);
            $user->_new = true;
            $user->_trusted = true;
            $user->_activation = $user->status === TNP_User::STATUS_NOT_CONFIRMED;
        }

        $this->add_user_log($user, 'subscribe');

        $this->logger->debug($user);

        if ($subscription->welcome_email_id) {
            $this->save_user_meta($user->id, 'welcome_email_id', $subscription->welcome_email_id);
        } else {
            $this->delete_user_meta($user->id, 'welcome_email_id');
        }

        if ($subscription->welcome_page_id) {
            $this->save_user_meta($user->id, 'welcome_page_id', $subscription->welcome_page_id);
        } else {
            $this->delete_user_meta($user->id, 'welcome_page_id');
        }

        if (!empty($subscription->autoresponders)) {
            $this->save_user_meta($user->id, 'autoresponders', implode(',', $subscription->autoresponders));
        } else {
            $this->delete_user_meta($user->id, 'autoresponders');
        }

        // Is the activation required (by double opt-in or modification of a confirmed subscriber)
        if ($user->_activation) {
            if ($subscription->send_emails) {
                $this->send_activation_email($user);
            }
        } else {
            do_action('newsletter_user_confirmed', $user);
            $this->notify_admin_on_subscription($user);
            setcookie('newsletter', $this->get_user_key($user), time() + 60 * 60 * 24 * 365, '/');
            if ($subscription->send_emails) {
                $this->send_welcome_email($user);
            }
        }

        return $user;
    }

    /**
     * Confirms a subscription changing the user status and, possibly, merging the
     * temporary data if present.
     *
     * @param TNP_User $user Optionally it can be null (user search from requests paramaters, but deprecated, or a user id)
     * @return TNP_User
     */
    function confirm($user = null, $emails = true) {

        if (!$user) {
            $this->dienow('Subscriber not found', '', 404);
        }

        if ($user->status !== TNP_User::STATUS_NOT_CONFIRMED && $user->status !== TNP_User::STATUS_CONFIRMED) {
            $this->dienow('Subscriber not found', 'This subscriber is bounced, complained or unsubscribed, cannot be confirmed', 404);
        }

        // Email change? (to be moved to the profile module with a custom action and message)
        $new_email = get_transient('newsletter_user_' . $user->id . '_email');
        if ($new_email) {
            delete_transient('newsletter_user_' . $user->id . '_email');
            $data = ['id' => $user->id, 'email' => $new_email];
            $user = $this->save_user($data);
            $this->update_user_last_activity($user);
            return $user;
        }

        // Confirmation for a repeated subscription
        $subscription = get_transient('newsletter_subscription_' . $user->id);
        if (!empty($subscription->data)) {
            delete_transient('newsletter_subscription_' . $user->id);
            $subscription->data->merge_in($user);
            $user = $this->save_user($user);
        }

        $user = $this->set_user_status($user, TNP_User::STATUS_CONFIRMED);

        $this->add_user_log($user, 'activate');
        $this->update_user_last_activity($user);
        setcookie('newsletter', $user->id . '-' . $user->token, time() + 60 * 60 * 24 * 365, '/');

        do_action('newsletter_user_confirmed', $user);

        if ($emails) {
            $this->send_welcome_email($user);
        }
        $this->notify_admin_on_subscription($user);

        return $user;
    }

    function save_subscription_meta($subscription, $user) {

    }

    /**
     * @deprecated since version 6.9.0
     */
    function subscribe($status = null, $emails = true) {
        return false;
    }

    function add_microdata($message) {
        return $message . '<span itemscope itemtype="http://schema.org/EmailMessage"><span itemprop="description" content="Email address confirmation"></span><span itemprop="action" itemscope itemtype="http://schema.org/ConfirmAction"><meta itemprop="name" content="Confirm Subscription"><span itemprop="handler" itemscope itemtype="http://schema.org/HttpActionHandler"><meta itemprop="url" content="{subscription_confirm_url}"><link itemprop="method" href="http://schema.org/HttpRequestMethod/POST"></span></span></span>';
    }

    function get_language_from_request() {
        return isset($_REQUEST['nlang']) ? trim(strip_tags($_REQUEST['nlang'])) : $this->language();
    }

    /**
     * Builds a subscription object starting from values in the $_REQUEST
     * global variable. It DOES NOT sanitize or formally check the values.
     * Usually data comes from a form submission.
     * https://www.thenewsletterplugin.com/documentation/subscription/newsletter-forms/
     *
     * @return TNP_Subscription
     */
    function build_subscription() {

        $posted = stripslashes_deep($_REQUEST); // Change to $_POST after compatibility tests

        $language = $this->sanitize_language($posted['nlang'] ?? $this->language());

        $subscription = $this->get_default_subscription($language);
        $data = $subscription->data;

        $data->email = $posted['ne'];

        if (isset($posted['nn'])) {
            $data->name = $posted['nn'];
        }

        if (isset($posted['ns'])) {
            $data->surname = $posted['ns'];
        }

        if (isset($posted['nx'])) {
            $data->sex = $posted['nx'];
        }

        if (isset($posted['nr'])) {
            $data->referrer = $posted['nr'];
        }

        // From the antibot form
        if (isset($posted['nhr'])) {
            $data->http_referer = $posted['nhr'];
        } else if (isset($_SERVER['HTTP_REFERER'])) {
            $data->http_referer = $_SERVER['HTTP_REFERER'];
        }

        // New profiles
        $customfields = $this->get_options('customfields');
        for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
            if (isset($posted['np' . $i])) {
                $profile = $this->get_customfield($i);
                if (!$profile || $profile->is_private()) {
                    if (current_user_can('administrator')) {
                        $this->dienow('Invalid custom field', 'Custom field ' . $i . ' has been submitted but it is set as private. Please fix the subscription form.');
                    }
                    continue;
                }
                $data->profiles['' . $i] = $posted['np' . $i];
            }
        }

        // Lists (field name is nl[] and values the list number so special forms with radio button can work)
        $nl = $posted['nl'] ?? [];

        foreach ($nl as $list_id) {
            $list = $this->get_list($list_id);
            if (!$list || $list->is_private()) {
                // To administrator show an error to make him aware of the wrong form configuration
                if (current_user_can('administrator')) {
                    $this->dienow('Invalid list', 'List ' . $list_id . ' has been submitted but it is set as private. Please fix the subscription form.');
                }
                // Ignore this list
                continue;
            }
            $data->lists['' . $list_id] = 1;
        }

        if (isset($_REQUEST['welcome_page_id'])) {
            $subscription->welcome_page_id = (int) $posted['welcome_page_id'];
        }

        if (class_exists('NewsletterAutoresponder') && method_exists('NewsletterAutoresponder', 'is_valid_key')) {

            $keys = wp_parse_list($posted['nar'] ?? []);
            if ($keys) {
                $subscription->autoresponders = []; // Remove the default one
                // Check the keys
                foreach ($keys as $key) {
                    if (NewsletterAutoresponder::$instance->is_valid_key($key)) {
                        $subscription->autoresponders[] = $id;
                    }
                }
            }
        }

        // Opt-in mode
        if (!empty($this->get_main_option('optin_override')) && isset($posted['optin'])) {
            switch ($posted['optin']) {
                case 'single': $subscription->optin = 'single';
                    break;
                case 'double': $subscription->optin = 'double';
                    break;
            }
        }

        return $subscription;
    }

    /**
     * Processes the request and fill in the *array* representing a subscriber with submitted values
     * (filtering when necessary).
     *
     * @deprecated since version 6.9.0
     * @param array $user An array partially filled with subscriber data
     * @return array The filled array representing a subscriber
     */
    function update_user_from_request($user) {
        return false;
    }

    /**
     * Sends a service message applying the template to the HTML part
     *
     * @param TNP_User $user
     * @param string $subject
     * @param string|array $message If string it is considered HTML, if array it should contains the key "html" and "text"
     * @return type
     */
    function mail($user, $subject, $message) {
        $this->switch_language($user->language);

        $options_template = $this->get_options('template');

        $template = trim($options_template['template']);
        if (empty($template) || strpos($template, '{message}') === false) {
            $template = '{message}';
        }

        if (is_array($message)) {
            $message['html'] = str_replace('{message}', $message['html'], $template);
            $message['html'] = $this->replace_for_email($message['html'], $user);
            $message['text'] = $this->replace_for_email($message['text'], $user);
        } else {
            $message = str_replace('{message}', $message, $template);
            $message = $this->replace_for_email($message, $user);
        }

        $headers = [];

        // Replaces tags from the template

        $subject = $this->replace($subject, $user);

        return Newsletter::instance()->mail($user->email, $subject, $message, $headers);
    }

    /**
     * @todo Move texts in the _get_default_text() method
     */
    function get_text_message($type) {
        switch ($type) {
            case 'confirmation':
                return __('To confirm your subscription follow the link below.', 'newsletter') . "\n\n{subscription_confirm_url}";
            case 'confirmed':
                return __('Your subscription has been confirmed.', 'newsletter');
        }
        return '';
    }

    function is_double_optin() {
        return $this->get_main_option('noconfirmation') == 0;
    }

    /**
     * Sends the activation email without conditions.
     *
     * @param stdClass $user
     * @return bool
     */
    function send_activation_email($user, $force = false) {

        if (!$force && !empty($this->options['confirmation_disabled'])) {
            return true;
        }

        $this->switch_language($user->language);

        $message = [];
        $message['html'] = do_shortcode($this->get_text('confirmation_message'));
        $message['text'] = $this->get_text_message('confirmation');
        $subject = $this->get_text('confirmation_subject');

        $r = $this->mail($user, $subject, $message);

        return $r;

        // TODO: Add filter
//        if ($res = apply_filters('newsletter_activation_email', false, $user)) {
//            if ($res === true)
//                return;
//            if (is_int($res)) {
//                $email = $this->get_email($res);
//                if ($email) {
//                    Newsletter::instance()->send($email, [$user]);
//                }
//            }
//        }
//
//        return $this->send_message('confirmation', $user, true);
    }

    function send_welcome_email($user, $force = false) {

        $this->logger->debug('Sending welcome email');

        $this->switch_language($user->language);

        $email_id = (int) $this->get_user_meta($user->id, 'welcome_email_id');
        $this->logger->debug('Email ID: ' . $email_id);
        if ($email_id) {
            if ($email_id === -1) {
                return;
            }
            $email = $this->get_email($email_id);
            if ($email) {
                $r = Newsletter::instance()->send($email, [$user]);
                return;
            } else {
                $this->logger->error('Welcome email not found: ' . $email_id);
            }
        }

        if (!$force && $this->options['welcome_email'] == '2') {
            return true;
        }

        $message = [];
        $message['html'] = do_shortcode($this->get_text('confirmed_message'));
        $message['text'] = $this->get_text_message('confirmed');
        $subject = $this->get_text('confirmed_subject');

        $r = $this->mail($user, $subject, $message);

        return $r;
    }

    /**
     * Sends a message (activation, welcome, cancellation, ...) with the correct template
     * and checking if the message itself is disabled
     *
     * @param string $type
     * @param TNP_User $user
     */
    function send_message($type, $user, $force = false) {

        $this->logger->debug('Send message: ' . $type);

        if ($type === 'confirmed') {

            $email_id = $this->get_user_meta($user->id, 'welcome_email_id');
            $this->logger->debug('Email ID: ' . $email_id);
            if ($email_id) {
                if ($email_id == '-1') {
                    return;
                }
                $email = $this->get_email($email_id);
                if ($email) {
                    $r = Newsletter::instance()->send($email, [$user]);
                    return;
                } else {
                    $this->logger->debug('Email not found');
                }
            }
            if (!$force && $this->options['welcome_email'] == '2') {
                return true;
            }
        }

        if ($type === 'confirmation') {
            if (!$force && !empty($this->options[$type . '_disabled'])) {
                return true;
            }
        }

        $this->switch_language($user->language);

        $message = [];
        $message['html'] = do_shortcode($this->get_text($type . '_message'));
        $message['text'] = $this->get_text_message($type);
//        if ($user->status == TNP_User::STATUS_NOT_CONFIRMED) {
//            $message['html'] = $this->add_microdata($message['html']);
//        }
        $subject = $this->get_text($type . '_subject');

        return $this->mail($user, $subject, $message);
    }

    function redirect_to_confirmed($user) {
        if (!$user) {
            die('Subscriber not found.');
        }
        $this->switch_language($user->language);
        $url = '';
        $welcome_page_id = $this->get_user_meta($user->id, 'welcome_page_id');
        if ($welcome_page_id) {
            $url = get_permalink($welcome_page_id);
        } else {
            if (isset($_REQUEST['ncu'])) {
                // Custom URL from the form
                $url = sanitize_url(wp_unslash($_REQUEST['ncu']));
            } else {
                // Per message custom URL from configuration (language variants could not be supported)
                $page_id = $this->get_option('confirmed_id');
                if (!empty($page_id)) {
                    if ($page_id === 'url') {
                        $url = sanitize_url($this->get_option('confirmed_url'));
                    } else {
                        $url = get_permalink((int) $page_id);
                    }
                }
            }
        }
        $url = apply_filters('newsletter_welcome_url', $url, $user);
        $url = Newsletter::instance()->build_message_url($url, 'confirmed', $user);
        $this->redirect($url);
    }

    function redirect_to_confirmation($user) {
        if (!$user) {
            die('Subscriber not found.');
        }
        $this->switch_language($user->language);
        $url = '';
        if (isset($_REQUEST['ncu'])) {
            // Custom URL from the form
            $url = sanitize_url($_REQUEST['ncu']);
        } else {
            // Per message custom URL from configuration (language variants could not be supported)
            $url = sanitize_url($this->get_option('confirmation_url'));
        }
        $url = $this->build_message_url($url, 'confirmation', $user);
        $this->redirect($url);
    }

    /**
     * Finds the right way to show the message identified by $key (welcome, unsubscription, ...) redirecting the user to the
     * WordPress page or loading the configured url or activating the standard page.
     */
    function show_message($key, $user = null, $alert = '', $email = null) {
        $url = '';

        if ($user) {
            $this->switch_language($user->language);
        }

        if ($key === 'confirmed') {
            $this->redirect_to_confirmed($user);
        }

        if ($key === 'confirmation') {
            $this->redirect_to_confirmation($user);
        }

        $url = sanitize_url($this->get_option($key . '_url'));

        $url = Newsletter::instance()->build_message_url($url, $key, $user, $email, $alert);
        $this->redirect($url);
    }

    var $privacy_url = false;

    /**
     * Generates the privacy URL and cache it.
     *
     * @return string
     */
    function get_privacy_url() {
        if ($this->privacy_url === false) {
            if (!empty($this->get_main_option('privacy_use_wp_url', 'form')) && function_exists('get_privacy_policy_url')) {
                $this->privacy_url = get_privacy_policy_url();
            } else {
                $this->privacy_url = $this->get_option('privacy_url', 'form');
            }
        }
        return $this->privacy_url;
    }

    function get_form_javascript() {

    }

    /**
     * Manages the custom forms made with [newsletter_form] and internal [newsletter_field] shortcodes.
     *
     * @param array $attrs
     * @param string $content
     * @return string
     */
    function get_subscription_form_custom($attrs = [], $content = '') {
        if (!is_array($attrs)) {
            $attrs = [];
        }

        $attrs = array_merge(['class' => 'tnp-subscription', 'style' => '', 'id' => ''], $attrs);

        $action = esc_attr($this->build_action_url('s'));
        $class = esc_attr($attrs['class']);
        $style = esc_attr($attrs['style']);

        $buffer = '<form method="post" action="' . $action . '" class="' . $class . '" style="' . $style . '"';
        if (!empty($attrs['id'])) {
            $buffer .= ' id="' . esc_attr($attrs['id']) . '"';
        }
        $buffer .= '>' . "\n";

        $buffer .= $this->get_form_hidden_fields($attrs);

        $buffer .= do_shortcode($content);

        if (isset($attrs['button_label'])) {
            $label = $attrs['button_label'];
        } else {
            $label = $this->get_form_text('subscribe');
        }

        if (!empty($label)) {
            $buffer .= $this->get_button($attrs);
        }

        $buffer .= '</form>';

        return $buffer;
    }

    /** Generates the hidden field for lists which should be implicitely set with a subscription form.
     *
     * @param string $lists Comma separated directly from the shortcode "lists" attribute
     * @param string $language ???
     * @return string
     */
    function get_form_implicit_lists($lists, $language = '') {
        $buffer = '';

        if (is_array($lists)) {
            $arr = $lists;
        } else {
            $arr = explode(',', $lists);
        }

        foreach ($arr as $a) {
            $a = trim($a);
            if (empty($a)) {
                continue;
            }

            $list = $this->get_list($a);
            if (!$list) {
                $buffer .= $this->build_field_admin_notice('List "' . $a . '" added to the form is not configured, skipped.');
                continue;
            }

            if ($list->is_private()) {
                $buffer .= $this->build_field_admin_notice('List ' . $a . ' is private cannot be used in a public form.');
                continue;
            }

            if ($list->forced) {
                $buffer .= $this->build_field_admin_notice('List ' . $a . ' is already enforced on every subscription there is no need to specify it.');
                continue;
            }

            $buffer .= "<input type='hidden' name='nl[]' value='" . esc_attr($a) . "'>\n";
        }
        return $buffer;
    }

    /**
     * Builds all the hidden fields of a subscription form. Implicit lists, confirmation url,
     * referrer, language, ...
     *
     * @param array $attrs Attributes of form shortcode
     * @return string HTML with the hidden fields
     */
    function get_form_hidden_fields($attrs) {
        $b = '';

        // Compatibility
        if (isset($attrs['list'])) {
            $attrs['lists'] = $attrs['list'];
        }
        if (isset($attrs['lists'])) {
            $b .= $this->get_form_implicit_lists($attrs['lists']);
        }

        if (isset($attrs['referrer'])) {
            $b .= '<input type="hidden" name="nr" value="' . esc_attr($attrs['referrer']) . '">' . "\n";
        }

        if (isset($attrs['confirmation_url'])) {
            if ($attrs['confirmation_url'] === '#') {
                $attrs['confirmation_url'] = esc_url_raw($_SERVER['REQUEST_URI']);
            }

            $b .= '<input type="hidden" name="ncu" value="' . esc_attr($attrs['confirmation_url']) . '">' . "\n";
        }

        if (isset($attrs['optin'])) {
            $optin = trim(strtolower($attrs['optin']));
            if ($optin !== 'double' && $optin !== 'single') {
                $b .= $this->build_field_admin_notice('The optin is set to an invalid value.');
            } else {
                if ($optin !== 'double' && $this->is_double_optin() && empty($this->get_main_option('optin_override'))) {
                    $b .= $this->build_field_admin_notice('The optin is specified but cannot be overridden (see the subscription configiraton page).');
                } else {
                    $b .= '<input type="hidden" name="optin" value="' . esc_attr($optin) . '">' . "\n";
                }
            }
        }

        $b .= '<input type="hidden" name="nlang" value="' . esc_attr($this->language()) . '">' . "\n";

        // Beta
//        if (isset($attrs['welcome_email_id'])) {
//            $e = $this->get_email($attrs['welcome_email_id']);
//            if ($e->type === 'welcome') {
//                $b .= '<input type="hidden" name="welcome_email_id" value="' . esc_attr($e->id . '-' . $e->token) . '">';
//            } else {
//                $b .= $this->build_field_admin_notice('The welcome email ID is not correct.');
//            }
//        }
        // Beta
        if (isset($attrs['welcome_page_id'])) {
            $page = get_post($attrs['welcome_page_id']);
            if ($page && $page->post_status === 'publish') {
                $b .= '<input type="hidden" name="welcome_page_id" value="' . esc_attr($page->ID) . '">' . "\n";
            } else {
                $b .= $this->build_field_admin_notice('The welcome page ID is not correct.');
            }
        }

        if (isset($attrs['autoresponders']) && method_exists('NewsletterAutoresponder', 'get_autoresponder_key')) {
            $ids = wp_parse_id_list($attrs['autoresponders']);
            foreach ($ids as $id) {
                $key = NewsletterAutoresponder::instance()->get_autoresponder_key($id);
                if ($key) {
                    $b .= '<input type="hidden" name="nar[]" value="' . esc_attr($key) . '">' . "\n";
                } else {
                    $b .= $this->build_field_admin_notice('Autoresponder not found: ' . $id);
                }
            }
        }

        return $b;
    }

    /**
     * Internal use only
     *
     * @param type $name
     * @param type $attrs
     * @return string
     */
    private function _shortcode_label($name, $attrs) {

        // When set but empty: no label
        if (isset($attrs['label']) && empty($attrs['label'])) {
            return;
        }

        $buffer = '<label for="' . esc_attr($attrs['id']) . '">';
        if (isset($attrs['label'])) {
            $buffer .= esc_html($attrs['label']);
        } else {
            if ($name !== 'lists') {
                $buffer .= esc_html($this->get_form_text($name));
            }
        }
        $buffer .= "</label>\n";
        return $buffer;
    }

    private function _shortcode_placeholder($name, $attrs) {

        if (isset($attrs['placeholder'])) {
            return $attrs['placeholder'];
        }

        if (!empty($attrs['label_as_placeholder'])) {
            return $this->get_form_text($name);
        }

        return $this->get_form_text($name . '_placeholder');
    }

    /**
     * Creates a notices to be displayed near a subscription form field to inform of worng configurations.
     * It is created only if the current user looking at the form is the administrator.
     *
     * @param string $message
     * @return string
     */
    function build_field_admin_notice($message) {
        if (!current_user_can('administrator')) {
            return '';
        }
        return '<p style="background-color: #eee; color: #000; padding: 10px; margin: 10px 0">' . $message . ' <strong>This notice is shown only to administrators to help with configuration.</strong></p>';
    }

    function shortcode_newsletter_field($attrs, $content = '') {
        // Counter to create unique ID for checkbox and labels
        static $idx = 0;

        $idx++;
        $attrs['id'] = 'tnp-' . $idx;

        $name = $attrs['name'];

        $buffer = '';

        if ($name == 'email') {
            $placeholder = $attrs['placeholder'] ?? $this->get_form_text('email_placeholder');
            $buffer .= '<div class="tnp-field tnp-field-email">';

            $buffer .= $this->_shortcode_label('email', $attrs);

            $buffer .= '<input class="tnp-email" type="email" name="ne" id="' . esc_attr($attrs['id']) . '" value=""';
            $buffer .= ' placeholder="' . esc_attr($placeholder) . '"';
            $buffer .= ' required>';
            if (isset($attrs['button_label'])) {
                $label = $attrs['button_label'];
                $buffer .= ' <input class="tnp-submit" type="submit" value="' . esc_attr($label) . '" style="width: 29%">';
            }
            $buffer .= "</div>\n";
            return $buffer;
        }

        if ($name == 'first_name' || $name == 'name') {
            $placeholder = $attrs['placeholder'] ?? $this->get_form_text('name_placeholder');
            $buffer .= '<div class="tnp-field tnp-field-firstname">';
            $buffer .= $this->_shortcode_label('name', $attrs);

            $buffer .= '<input class="tnp-name" type="text" name="nn" id="' . esc_attr($attrs['id']) . '" value=""';
            $buffer .= ' placeholder="' . esc_attr($placeholder) . '"';
            if ($this->get_form_option('name_rules') == 1) {
                $buffer .= ' required';
            }
            $buffer .= '>';
            $buffer .= "</div>\n";
            return $buffer;
        }

        if ($name == 'last_name' || $name == 'surname') {
            $placeholder = $attrs['placeholder'] ?? $this->get_form_text('surname_placeholder');
            $buffer .= '<div class="tnp-field tnp-field-surname">';
            $buffer .= $this->_shortcode_label('surname', $attrs);

            $buffer .= '<input class="tnp-surname" type="text" name="ns" id="' . esc_attr($attrs['id']) . '" value=""';
            $buffer .= ' placeholder="' . esc_attr($placeholder) . '"';
            if ($this->get_form_option('surname_rules') == 1) {
                $buffer .= ' required';
            }
            $buffer .= '>';
            $buffer .= '</div>';
            return $buffer;
        }

        // Single list
        if ($name == 'preference' || $name == 'list') {
            if (!isset($attrs['number'])) {
                return $this->build_field_admin_notice('List number not specified.');
            }
            $number = (int) $attrs['number'];
            $list = $this->get_list($number);
            if (!$list) {
                return $this->build_field_admin_notice('List ' . $number . ' is not configured, cannot be shown.');
            }

            if ($list->status == 0 || $list->forced) {
                return $this->build_field_admin_notice('List ' . $number . ' is private or enforced cannot be shown.');
            }

            if (isset($attrs['hidden'])) {
                return '<input type="hidden" name="nl[]" value="' . esc_attr($list->id) . '">';
            }

            $buffer .= '<div class="tnp-field tnp-field-checkbox tnp-field-list"><label for="tnp-' . $idx . '">';
            $buffer .= '<input type="checkbox" id="tnp-' . $idx . '" name="nl[]" value="' . esc_attr($list->id) . '"';
            if (isset($attrs['checked'])) {
                $buffer .= ' checked';
            }
            $buffer .= '> ';
            $buffer .= esc_html(isset($attrs['label']) ? $attrs['label'] : $list->name);
            $buffer .= "</label></div>\n";

            return $buffer;
        }

        // All lists
        if ($name == 'lists' || $name == 'preferences') {
            $list_ids = $this->get_form_option('lists');
            if (!empty($list_ids)) {

                $checked_ids = $this->get_form_option('lists_checked');

                if (isset($attrs['layout']) && $attrs['layout'] === 'dropdown') {

                    $buffer .= '<div class="tnp-field tnp-lists">';
                    // There is not a default "label" for the block of lists, so it can only be specified in the shortcode attrs as "label"
                    $buffer .= $this->_shortcode_label('lists', $attrs);
                    $buffer .= '<select class="tnp-lists" name="nl[]" required>';

                    if (!empty($attrs['first_option_label'])) {
                        $buffer .= '<option value="" selected disabled>' . esc_html($attrs['first_option_label']) . '</option>';
                    }

                    foreach ($list_ids as $list_id) {
                        $list = $this->get_list($list_id);
                        if ($list && $list->is_private()) {
                            continue;
                        }
                        $buffer .= '<option value="' . esc_attr($list->id) . '">' . esc_html($list->name) . '</option>';
                    }
                    $buffer .= '</select>';
                    $buffer .= '</div>';
                } else {

                    $buffer .= '<div class="tnp-field tnp-lists">';
//                    if (!empty($attrs['label'])) {
//                        $buffer .= '<p>' . $attrs['label'] . '</p>';
//                    }
                    foreach ($list_ids as $list_id) {
                        $list = $this->get_list($list_id);
                        if (!$list || $list->is_private()) {
                            continue;
                        }
                        $idx++;
                        $buffer .= '<div class="tnp-field tnp-field-checkbox tnp-field-list"><label for="nl' . $idx . '">';
                        $buffer .= '<input type="checkbox" id="nl' . $idx . '" name="nl[]" value="' . esc_attr($list->id) . '"';
                        if (in_array($list_id, $checked_ids)) {
                            $buffer .= ' checked';
                        }
                        $buffer .= '> ' . esc_html($list->name) . '</label>';
                        $buffer .= "</div>\n";
                    }
                    $buffer .= '</div>';
                }
            }
            return $buffer;
        }

        if ($name === 'sex' || $name === 'gender') {
            $buffer .= '<div class="tnp-field tnp-field-gender">';
            $buffer .= $this->_shortcode_label('sex', $attrs);

            $buffer .= '<select name="nx" class="tnp-gender" id="tnp-gender"';
            if ($this->get_form_option('sex_rules')) {
                $buffer .= ' required ';
            }
            $buffer .= '>';
            if ($this->get_form_option('sex_rules')) {
                $buffer .= '<option value=""></option>';
            }
            $buffer .= '<option value="n">' . esc_html($this->get_form_text('sex_none')) . '</option>';
            $buffer .= '<option value="f">' . esc_html($this->get_form_text('sex_female')) . '</option>';
            $buffer .= '<option value="m">' . esc_html($this->get_form_text('sex_male')) . '</option>';
            $buffer .= '</select>';
            $buffer .= "</div>\n";
            return $buffer;
        }

        if ($name === 'profile' || $name === 'customfield') {
            $number = (int) $attrs['number'] ?? 0;

            $profile = $this->get_customfield($number);

            if (!$profile) {
                return $this->build_field_admin_notice('Custom field ' . $number . ' is not configured ot the number is wrong or not specified');
            }

            if ($profile->is_private()) {
                return $this->build_field_admin_notice('Custom field ' . $number . ' is private and cannot be shown.');
            }

            $size = $attrs['size'] ?? '';
            $attrs['label'] = $attrs['label'] ?? $profile->name;
            $placeholder = $attrs['placeholder'] ?? $profile->placeholder;

            $buffer .= '<div class="tnp-field tnp-field-profile">';
            $buffer .= $this->_shortcode_label('profile_' . $profile->id, $attrs);

            // Text field
            if ($profile->type == TNP_Profile::TYPE_TEXT) {
                $buffer .= '<input class="tnp-profile tnp-profile-' . $number . '" id="tnp-profile_' . $number . '" type="text" size="' . esc_attr($size) . '" name="np' . $number . '" placeholder="' . esc_attr($placeholder) . '"';
                if ($profile->is_required()) {
                    $buffer .= ' required';
                }
                $buffer .= '>';
            }

            // Select field
            if ($profile->type == TNP_Profile::TYPE_SELECT) {
                $buffer .= '<select class="tnp-profile tnp-profile-' . $number . '" id="tnp-profile_' . $number . '" name="np' . $number . '"';
                if ($profile->is_required()) {
                    $buffer .= ' required';
                }
                $buffer .= '>';
                if (!empty($placeholder)) {
                    $buffer .= '<option value="" selected disabled>' . esc_html($placeholder) . '</option>';
                }
                foreach ($profile->options as $option) {
                    $buffer .= '<option>' . esc_html(trim($option)) . '</option>';
                }
                $buffer .= "</select>\n";
            }

            $buffer .= "</div>\n";

            return $buffer;
        }

        if (strpos($name, 'privacy') === 0) {
            $url = $attrs['url'] ?? $this->get_privacy_url();
            $label = $attrs['label'] ?? $this->get_form_text('privacy');

            $buffer .= '<div class="tnp-field tnp-field-checkbox tnp-field-privacy">';

            $buffer .= '<input type="checkbox" name="ny" required class="tnp-privacy" id="tnp-' . $idx . '"> ';
            $buffer .= '<label for="tnp-' . $idx . '">';
            if (!empty($url)) {
                $buffer .= '<a target="_blank" href="' . esc_attr($url) . '">';
            }
            $buffer .= esc_html($label);
            if (!empty($url)) {
                $buffer .= '</a>';
            }
            $buffer .= '</label>';
            $buffer .= '</div>';

            return $buffer;
        }

        if ($name === 'button') {

        }
    }

    /**
     * Builds the privacy field only for completely generated forms.
     *
     * @return string Empty id the privacy filed is not configured
     */
    function get_privacy_field($pre_html = '', $post_html = '') {

        $privacy_status = (int) $this->get_option('privacy_status', 'form');
        if (empty($privacy_status)) {
            return '';
        }

        $buffer = '<label>';
        if ($privacy_status === 1) {
            $buffer .= '<input type="checkbox" name="ny" required class="tnp-privacy"> ';
        }
        $url = $this->get_privacy_url();
        if (!empty($url)) {
            $buffer .= '<a target="_blank" href="' . esc_attr($url) . '">';
            $buffer .= esc_html($this->get_form_text('privacy')) . '</a>';
        } else {
            $buffer .= esc_html($this->get_form_text('privacy'));
        }

        $buffer .= "</label>";

        return $pre_html . $buffer . $post_html;
    }

    function get_button($attrs, $wrapper = true) {
        // Prepare the button attrbutes
        $button_style = '';
        if (!empty($attrs['button_color'])) {
            $button_style = 'background-color:' . $attrs['button_color'] . ';';
        }
        if (!empty($attrs['button_radius'])) {
            $button_style = 'border-radius:' . $attrs['button_radius'] . ';';
        }
        $button_label = $this->get_text('subscribe', 'form');
        if (!empty($attrs['button_label'])) {
            $button_label = $attrs['button_label'];
        } else if (!empty($attrs['button'])) { // Old attribute
            $button_label = $attrs['button'];
        }
        // For the button container
        $button_div_style = '';
        if (!empty($attrs['button_align'])) {
            $button_div_style = 'text-align: ' . $attrs['button_align'];
        } else {
            if (is_rtl()) {
                $button_div_style = 'text-align: right';
            } else {
                $button_div_style = 'text-align: left';
            }
        }

        $b = '';
        if ($wrapper) {
            $b .= '<div class="tnp-field tnp-field-button" style="' . esc_attr($button_div_style) . '">';
        }
        $b .= '<input class="tnp-submit" type="submit" value="' . esc_attr($button_label) . '" style="' . esc_attr($button_style) . '">' . "\n";
        if ($wrapper) {
            $b .= "</div>\n";
        }
        return $b;
    }

    /**
     * The new standard form.
     *
     * @param string $referrer Deprecated since 6.9.1, use the "referrer" key on $attrs
     * @param string $action
     * @param string $attrs
     * @return string The full HTML form
     */
    function get_subscription_form($referrer = '', $action = null, $attrs = []) {

        if (!is_array($attrs)) {
            $attrs = [];
        }
        $attrs = array_merge(['class' => '', 'show_labels' => 'true', 'show_placeholders' => 'true'], $attrs);

        $buffer = '';

        // The referrer parameter is deprecated
        if (!empty($referrer)) {
            $attrs['referrer'] = $referrer;
        }

        $action = $this->build_action_url($attrs['action'] ?? $action ?? 's');
        $class = $attrs['class'] ?? '';

        $buffer .= '<div class="tnp tnp-subscription ' . esc_attr($class) . '">' . "\n";

        $buffer .= '<form method="post" action="' . esc_attr($action) . '"';

        if (!empty($attrs['id'])) {
            $buffer .= ' id="' . esc_attr($attrs['id']) . '"';
        }

        $buffer .= '>' . "\n";

        $buffer .= $this->get_form_hidden_fields($attrs);

        $field_attrs = [];
        if ($attrs['show_labels'] === 'false') {
            $field_attrs['label'] = '';
        }

        if ($attrs['show_placeholders'] === 'false') {
            $field_attrs['placeholder'] = '';
        }

        $fields = wp_parse_list($attrs['fields'] ?? '');

        if (empty($fields)) {
            if (!empty($this->get_form_option('name_status'))) {
                $fields[] = 'first_name';
            }
            if (!empty($this->get_form_option('surname_status'))) {
                $fields[] = 'last_name';
            }
            $fields[] = 'email';
            if ($this->get_form_option('sex_status')) {
                $fields[] = 'gender';
            }
            $fields[] = 'customfields';
            $fields[] = 'lists';
            if ($this->get_option('privacy_status', 'form')) {
                $fields[] = 'privacy';
            }
        }


        if (!in_array('email', $fields)) {
            $field_attrs['name'] = 'email';
            $buffer .= $this->shortcode_newsletter_field($field_attrs);
        }

        foreach ($fields as $field) {
            switch ($field) {
                case 'email':
                    $field_attrs['name'] = 'email';
                    $buffer .= $this->shortcode_newsletter_field($field_attrs);
                    break;

                case 'first_name':
                    $field_attrs['name'] = 'first_name';
                    $buffer .= $this->shortcode_newsletter_field($field_attrs);
                    break;

                case 'last_name':
                    $field_attrs['name'] = 'last_name';
                    $buffer .= $this->shortcode_newsletter_field($field_attrs);
                    break;

                case 'gender':
                    $field_attrs['name'] = 'gender';
                    $buffer .= $this->shortcode_newsletter_field($field_attrs);
                    break;

                case 'customfields':
                    $ids = $this->get_form_option('customfields');
                    if (is_array($ids)) {
                        $field_attrs['name'] = 'customfield';
                        foreach ($ids as $id) {
                            $field_attrs['number'] = $id;
                            $buffer .= $this->shortcode_newsletter_field($field_attrs);
                        }
                    }
                    break;

                case 'lists':
                    if (empty($attrs['lists_field_label'])) {
                        $attrs['lists_field_label'] = '';
                    }
                    if (!empty($attrs['lists_field_layout']) && $attrs['lists_field_layout'] === 'dropdown') {
                        if (empty($attrs['lists_field_empty_label'])) {
                            $attrs['lists_field_empty_label'] = '';
                        }
                        $buffer .= $this->shortcode_newsletter_field(['name' => 'lists', 'layout' => 'dropdown', 'first_option_label' => $attrs['lists_field_empty_label'], 'label' => $attrs['lists_field_label']]);
                    } else {
                        $buffer .= $this->shortcode_newsletter_field(['name' => 'lists', 'label' => $attrs['lists_field_label']]);
                    }

                    break;

                case 'privacy':
                    $buffer .= $this->get_privacy_field('<div class="tnp-field tnp-privacy-field">', '</div>');
                    break;
            }
        }

        $buffer .= $this->get_button($attrs);

        $buffer .= "</form>\n";
        $buffer .= "</div>\n";

        return $buffer;
    }

    function get_form($number) {
        $options = $this->get_options('htmlforms');

        $form = $options['form_' . $number];

        $form = do_shortcode($form);

        $action = $this->build_action_url('s');

        if (stripos($form, '<form') === false) {
            $form = '<form method="post" action="' . esc_attr($action) . '">' . $form . '</form>';
        }

        // For compatibility
        $form = str_replace('{newsletter_url}', $action, $form);

        $form = $this->replace_lists($form);

        return $form;
    }

    /** Replaces on passed text the special tag {lists} that can be used to show the preferences as a list of checkbox.
     * They are called lists but on configuration panel they are named preferences!
     *
     * @param string $buffer
     * @return string
     */
    function replace_lists($buffer) {
        $checkboxes = '';
        $lists = $this->get_lists_for_subscription();
        foreach ($lists as $list) {
            $checkboxes .= '<input type="checkbox" name="nl[]" value="' . esc_attr($list->id) . '"> ' . esc_attr($list->name) . '<br />';
        }
        $buffer = str_replace('{lists}', $checkboxes, $buffer);
        $buffer = str_replace('{preferences}', $checkboxes, $buffer); // For compatibility
        return $buffer;
    }

    function notify_admin_on_subscription($user) {

        if (empty($this->get_main_option('notify'))) {
            return;
        }

        $message = $this->generate_admin_notification_message($user);
        $email = trim($this->get_main_option('notify_email'));
        $subject = $this->generate_admin_notification_subject('New subscription');

        Newsletter::instance()->mail($email, $subject, ['html' => $message]);
    }

    /**
     * Builds the minimal subscription form, with only the email field and inline
     * submit button. If enabled the privacy checkbox is added.
     *
     * @param type $attrs
     * @return string
     */
    function get_subscription_form_minimal($attrs) {
        if (!is_array($attrs)) {
            $attrs = [];
        }

        // Compatibility
        if (isset($attrs['button_label'])) {
            $attrs['button'] = $attrs['button_label'];
        }

        $attrs = array_merge(['class' => '', 'referrer' => 'minimal',
            'button' => $this->get_form_text('subscribe'), 'button_color' => '',
            'button_radius' => '', 'placeholder' => $this->get_form_text('email'),
            'name_placeholder' => $this->get_form_text('name'),
            'show_name' => 0, 'align' => 'center'], $attrs);

        $show_name = !empty($attrs['show_name']);

        $form = '<div class="tnp tnp-subscription-minimal '
                . ($show_name ? 'with-name ' : ' ')
                . esc_attr($attrs['class']) . '">';

        $form .= '<form action="' . esc_attr($this->build_action_url('s')) . '" method="post"';
        if (!empty($attrs['id'])) {
            $form .= ' id="' . esc_attr($attrs['id']) . '"';
        }
        $style = 'text-align: ' . esc_attr($attrs['align']);

        $form .= ' style="' . $style . '"';

        $form .= '>';

        $form .= $this->get_form_hidden_fields($attrs);

        $form .= '<input class="tnp-email" type="email" required name="ne" value="" placeholder="' . esc_attr($attrs['placeholder']) . '">';

        if ($show_name) {
            $form .= '<input class="tnp-name" type="text" required name="nn" value="" placeholder="' . esc_attr($attrs['name_placeholder']) . '">';
        }

//        $form .= '<input class="tnp-submit" type="submit" value="' . esc_attr($attrs['button']) . '"'
//                . ' style="background-color:' . esc_attr($attrs['button_color']) . '">';

        $form .= $this->get_button($attrs, false);

        $form .= $this->get_privacy_field('<div class="tnp-field tnp-privacy-field">', '</div>');

        $form .= "</form></div>\n";

        return $form;
    }

    /**
     * Implementation of the [newsletter_form] shortcode.
     *
     * @param array $attrs
     * @param string $content
     * @return string
     */
    function shortcode_newsletter_form($attrs, $content = '') {

        if (isset($attrs['type']) && $attrs['type'] === 'minimal') {
            return $this->get_subscription_form_minimal($attrs);
        }

        // Custom form using the [newsletter_field] shortcodes
        if (!empty($content)) {
            return $this->get_subscription_form_custom($attrs, $content);
        }

        // Custom form hand coded and saved in the custom forms option
        if (isset($attrs['form'])) {
            return $this->get_form((int) $attrs['form']);
        }

        // Custom hand coded form (as above, new syntax)
        if (isset($attrs['number'])) {
            return $this->get_form((int) $attrs['number']);
        }

        return $this->get_subscription_form(null, null, $attrs);
    }

    function hook_newsletter_page_text($text, $key, $user) {

        if (!in_array($key, ['subscription', 'confirmed', 'confirmation', 'error'])) {
            return $text;
        }

        $text = $this->get_text($key . '_text');

        // Fixing of the old tags
        $text = str_replace('{profile_form}', '[newsletter_profile]', $text); // can be used on welcome text
        $text = str_replace('{subscription_form}', '[newsletter_form]', $text);
        for ($i = 1; $i <= 10; $i++) {
            if (strpos($text, "{subscription_form_$i}") !== false) {
                $text = str_replace("{subscription_form_$i}", '[newsletter_form form="' . $i . '"]', $text);
            }
        }

        if ($key === 'confirmed') {
            $text .= $this->get_option($key . '_tracking');
        }

        if ($key === 'error' && current_user_can('administrator')) {
            $text .= '<div style="padding: 1rem; background-color: #eee"><strong>Message only visibile to administrators</strong><br>';
            $text .= 'Email address probably already registered and Newsletter sets to block repeated registrations. You can change this behavior or the user message above on subscription configuration panel.';
            $text .= '</div>';
        }

        $admin_notice = '';
        if (current_user_can('administrator')) {
            if ($this->is_multilanguage()) {
                $language = $this->language();
                if (empty($language)) {
                    $language = 'all';
                }
                $admin_notice = '<p style="background-color: #eee; color: #000; padding: 1rem; margin: 1rem 0"><strong>Visible only to administrators</strong>. <a href="' . admin_url('admin.php?page=newsletter_subscription_options&lang=' . urlencode($language)) . '" target="_blank">Edit this content</a>.</p>';
            } else {
                $admin_notice = '<p style="background-color: #eee; color: #000; padding: 1rem; margin: 1rem 0"><strong>Visible only to administrators</strong>. <a href="' . admin_url('admin.php?page=newsletter_subscription_options') . '" target="_blank">Edit this content</a>.</p>';
            }
        }

        return $admin_notice . $text;
    }
}

NewsletterSubscription::instance();

// Compatibility code

/**
 * @deprecated
 * @param int $number
 */
function newsletter_form($number = null) {
    if ($number != null) {
        echo NewsletterSubscription::instance()->get_form($number);
    } else {
        echo NewsletterSubscription::instance()->get_subscription_form();
    }
}
