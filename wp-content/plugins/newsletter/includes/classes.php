<?php

/**
 * @property int $id The list unique identifier
 * @property string $name The list name
 * @property bool $forced If the list must be added to every new subscriber
 * @property int $status When and how the list is visible to the subscriber - see constants
 * @property bool $checked If it must be pre-checked on subscription form
 * @property array $languages The list of language used to pre-assign this list
 */
class TNP_List {

    const STATUS_PRIVATE = 0;
    const STATUS_PUBLIC = 1;

    var $id;
    var $name;
    var $status;
    var $forced;
    var $languages;

    function is_private() {
        return $this->status == self::STATUS_PRIVATE;
    }

    function is_public() {
        return $this->status == self::STATUS_PUBLIC;
    }

    static function build($options) {
        $lists = [];
        for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
            if (empty($options['list_' . $i])) {
                continue;
            }

            $prefix = 'list_' . $i;
            $list = new TNP_List();
            $list->id = $i;
            $list->name = $options[$prefix];
            $list->forced = !empty($options[$prefix . '_forced']);
            $list->status = empty($options[$prefix . '_status']) ? TNP_List::STATUS_PRIVATE : TNP_List::STATUS_PUBLIC;
            if (empty($options[$prefix . '_languages'])) {
                $list->languages = [];
            } else {
                $list->languages = $options[$prefix . '_languages'];
            }

            $lists['' . $list->id] = $list;
        }
        return $lists;
    }
}

class TNP_Media {

    var $id;
    var $url;
    var $width;
    var $height;
    var $alt;
    var $link;
    var $align = 'center';

    /** Sets the width recalculating the height */
    public function set_width($width) {
        $width = (int) $width;
        if (empty($width))
            return;
        if ($this->width < $width)
            return;
        $this->height = floor(($width / $this->width) * $this->height);
        $this->width = $width;
    }

    /** Sets the height recalculating the width */
    public function set_height($height) {
        $height = (int) $height;
        $this->width = floor(($height / $this->height) * $this->width);
        $this->height = $height;
    }
}

/**
 * @property int $id The list unique identifier
 * @property string $name The list name
 * @property int $status When and how the list is visible to the subscriber - see constants
 * @property string $type Field type: text or select
 * @property array $options Field options (usually the select items)
 */
#[\AllowDynamicProperties]
class TNP_Profile {

    const STATUS_PRIVATE = 0;
    const STATUS_PUBLIC = 1;
    const TYPE_TEXT = 'text';
    const TYPE_SELECT = 'select';

    public $id;
    public $name;
    public $status;
    public $type;
    public $options;
    public $placeholder;
    public $rule;

    public function __construct($id = 0, $name = '', $status = '', $type = '', $options = [], $placeholder = '', $rule = '') {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
        $this->type = $type;
        $this->options = $options;
        $this->placeholder = $placeholder;
        $this->rule = $rule;
    }

    function is_select() {
        return $this->type == self::TYPE_SELECT;
    }

    function is_text() {
        return $this->type == self::TYPE_TEXT;
    }

    function is_required() {
        return $this->rule == 1;
    }

    function is_private() {
        return $this->status == self::STATUS_PRIVATE;
    }

    function is_public() {
        // To be compatibile with old statuses (2, 3)
        return $this->status != self::STATUS_PRIVATE;
    }

    function show_on_profile() {
        return $this->status == self::STATUS_PROFILE_ONLY || $this->status == self::STATUS_PUBLIC;
    }
}

/**
 * Represents the set of data collected by a subscription interface (form, API, ...). Only a valid
 * email is mandatory.
 */
class TNP_Subscription_Data {

    var $email = null;
    var $name = null;
    var $surname = null;
    var $sex = null;
    var $language = null;
    var $referrer = null;
    var $http_referer = null;
    var $ip = null;
    var $country = null;
    var $region = null;
    var $city = null;
    var $wp_user_id = 0;

    /**
     * Associative array id=>value of lists chosen by the subscriber. A list can be set to
     * 0 meaning the subscriber does not want to be in that list.
     * @var array
     */
    var $lists = [];
    var $profiles = [];

    function merge_in($subscriber) {

        if (!empty($this->email))
            $subscriber->email = $this->email;
        if (!empty($this->name))
            $subscriber->name = $this->name;
        if (!empty($this->surname))
            $subscriber->surname = $this->surname;
        if (!empty($this->sex))
            $subscriber->sex = $this->sex;
        if (!empty($this->language))
            $subscriber->language = $this->language;
        if (!empty($this->ip))
            $subscriber->ip = $this->ip;
        if (!empty($this->referrer))
            $subscriber->referrer = $this->referrer;
        if (!empty($this->http_referrer))
            $subscriber->http_referrer = $this->http_referrer;
        if (!empty($this->country))
            $subscriber->country = $this->country;
        if (!empty($this->region))
            $subscriber->region = $this->region;
        if (!empty($this->city))
            $subscriber->city = $this->city;
        if (!empty($this->wp_user_id))
            $subscriber->wp_user_id = $this->wp_user_id;

        foreach ($this->lists as $id => $value) {
            $key = 'list_' . $id;
            $subscriber->$key = $value;
        }

        // Profile
        foreach ($this->profiles as $id => $value) {
            $key = 'profile_' . $id;
            $subscriber->$key = trim($value);
        }
    }

    /** Sets to active a set of lists. Accepts incorrect data (and ignores it).
     *
     * @param array $list_ids Array of list IDs
     */
    function add_lists($list_ids) {
        $list_ids = wp_parse_list($list_ids);

        foreach ($list_ids as $list_id) {
            $list_id = (int) $list_id;
            if ($list_id <= 0 || $list_id > NEWSLETTER_LIST_MAX) {
                continue;
            }
            $this->lists[$list_id] = 1;
        }
    }
}

/**
 * Represents a subscription request with the subscriber data and actions to be taken by
 * the subscription engine (spam check, notifications, ...).
 */
class TNP_Subscription {

    const EXISTING_ERROR = 1;
    const EXISTING_MERGE = 0;
    const EXISTING_DOUBLE_OPTIN = 0;
    const EXISTING_SINGLE_OPTIN = 2;

    /**
     * Subscriber's data following the syntax of the TNP_User
     * @var TNP_Subscription_Data
     */
    var $data;
    var $spamcheck = true;
    // The optin to use, empty for the plugin default. It's a string to facilitate the use by addons (which have a selector for the desired
    // optin as empty (for default), 'single' or 'double'.
    var $optin = null;
    // What to do with an existing subscriber???
    var $if_exists = self::EXISTING_DOUBLE_OPTIN;

    /**
     * Determines if the welcome or activation email should be sent. Note: sometime an activation email is sent disregarding
     * this setting.
     * @var boolean
     */
    var $send_emails = true;
    var $welcome_email_id = 0;
    var $welcome_page_id = 0;
    var $autoresponders = [];

    public function __construct() {
        $this->data = new TNP_Subscription_Data();
    }

    public function set_optin($optin) {
        if (empty($optin))
            return;
        if ($optin != 'single' && $optin != 'double') {
            return;
        }
        $this->optin = $optin;
    }

    public function is_single_optin() {
        return $this->optin == 'single';
    }

    public function is_double_optin() {
        return $this->optin == 'double';
    }
}

/**
 */
#[\AllowDynamicProperties]
class TNP_User {

    const STATUS_CONFIRMED = 'C';
    const STATUS_NOT_CONFIRMED = 'S';
    const STATUS_UNSUBSCRIBED = 'U';
    const STATUS_BOUNCED = 'B';
    const STATUS_COMPLAINED = 'P';

    var $id = 0;
    var $email = '';
    var $name = '';
    var $surname = '';
    var $sex = 'n';
    var $status = self::STATUS_NOT_CONFIRMED;
    var $ip = '';
    var $language = '';
    var $referrer = '';
    var $http_referer = ''; // Single "r", it's ok
    var $token = '';
    var $country = '';
    var $city = '';
    var $region = '';
    var $last_activity = 0; // Unix timestamp
    var $wp_user_id = 0;
    var $updated = 0; // Unix timestamp
    var $_dummy = false; // Transient to manage the preview of different actions
    var $_trusted = true; // Transient indicating the created subscriber can modify the data
    var $_new = true; // Transient indicating the created subscriber is new
    var $_activation = true; // Transient indicating the created subscriber needs to be activated

    public static function get_status_label($status, $html = false) {
        $label = 'Unknown';
        $class = 'unknown';

        switch ($status) {
            case self::STATUS_NOT_CONFIRMED:
                $label = __('Not confirmed', 'newsletter');
                $class = 'not-confirmed';
                break;
            case self::STATUS_CONFIRMED: $label = __('Confirmed', 'newsletter');
                $class = 'confirmed';
                break;
            case self::STATUS_UNSUBSCRIBED: $label = __('Unsubscribed', 'newsletter');
                $class = 'unsubscribed';
                break;
            case self::STATUS_BOUNCED: $label = __('Bounced', 'newsletter');
                $class = 'bounced';
                break;
            case self::STATUS_COMPLAINED: $label = __('Complained', 'newsletter');
                $class = 'complained';
                break;
        }
        if (!$html) {
            return $label;
        }

        return '<span class="tnp-status tnp-user-status tnp-user-status--' . esc_attr($class) . '">' . esc_html($label) . '</span>';
    }

    public static function is_status_valid($status) {
        switch ($status) {
            case self::STATUS_CONFIRMED: return true;
            case self::STATUS_NOT_CONFIRMED: return true;
            case self::STATUS_UNSUBSCRIBED: return true;
            case self::STATUS_BOUNCED: return true;
            case self::STATUS_COMPLAINED: return true;
            default: return false;
        }
    }
}

/**
 * @property int $id The email unique identifier
 * @property string $subject The email subject
 * @property string $message The email html message
 * @property int $track Check if the email stats should be active
 * @property array $options Email options
 * @property int $total Total emails to send
 * @property int $sent Total sent emails by now
 * @property int $open_count Total opened emails
 * @property int $click_count Total clicked emails
 * */
class TNP_Email {

    const STATUS_DRAFT = 'new';
    const STATUS_SENT = 'sent';
    const STATUS_SENDING = 'sending';
    const STATUS_PAUSED = 'paused';
    const STATUS_ERROR = 'error';
    const EDITOR_COMPOSER = 2;
    const EDITOR_HTML = 1;
    const EDITOR_TINYMCE = 0;

    var $options = [];
}
