<?php

/**
 * Contains all the default options and default translatable texts with utility methods
 * to retrieve them.
 *
 * This class helps in decoupling the frontend from the backned.
 */
class NewsletterDefaults {

    static function get_options($sub) {

        if ($sub === 'main') {
            $sitename = strtolower(wp_parse_url(home_url(),PHP_URL_HOST));
            if (substr($sitename, 0, 4) == 'www.') {
                $sitename = substr($sitename, 4);
            }

            return [
                'return_path' => '',
                'reply_to' => '',
                'sender_email' => 'newsletter@' . $sitename,
                'sender_name' => get_option('blogname'),
                'editor' => 0,
                'scheduler_max' => 100,
                'max_per_second' => 0,
                'phpmailer' => 0,
                'debug' => 0,
                'track' => 1,
                'css' => '',
                'css_disabled' => 0,
                'ip' => '',
                'page' => 0,
                'disable_cron_notice' => 0,
                'do_shortcodes' => 1,
            ];
        }

        if ($sub === 'antispam') {
            return [
                'ip_blacklist' => [],
                'address_blacklist' => [],
                'antiflood' => 60,
                'akismet' => 0,
                'captcha' => 0,
                'disabled' => 0
            ];
        }

        if ($sub === 'lists') {
            $options = [];

            for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
                $options['list_' . $i] = '';
                $options['list_' . $i . '_status'] = 0;
                $options['list_' . $i . '_forced'] = 0;
            }
            return $options;
        }

        if ($sub === 'form') {
            return [
                'name_status' => 0,
                'name_rules' => 0,
                'surname_status' => 0,
                'sex_status' => 0,
                'privacy_status' => 0,
                'privacy_url' => '',
                'privacy_use_wp_url' => 0,
                'lists' => [],
                'lists_checked' => [],
                'profiles' => []
            ];
        }

        if ($sub === 'template') {
            return [
                'template' => file_get_contents(__DIR__ . '/email.html')
            ];
        }

        if ($sub === 'subscription') {
            return [
                'noconfirmation' => 1,
                'notify_email' => get_option('admin_email'),
                'multiple' => 1,
                'notify' => 0,
                'confirmed_tracking' => '',
                'welcome_email' => '',
                'welcome_email_id' => 0
            ];
        }

        if ($sub === 'inject') {
            return [
                'bottom_enabled' => '0',
                'bottom_text' => ''
            ];
        }

        if ($sub === 'popup') {
            return [
                'enabled' => '0',
                'text' => ''
            ];
        }

        if ($sub === 'profile') {
            return [
                'text_custom' => '0',
                'export_newsletters' => '0',
                'url' => '',
                'page_id' => '',
                'page_url' => '',
                'email' => '1',
                'name' => '1',
                'surname' => '1',
                'sex' => '1',
                'lists' => [],
                'customfields' => [],
                'profiles' => []
            ];
        }

        if ($sub === 'unsubscription') {
            return [
                'mode' => '2',
                'notify' => '',
                'notify_email' => '',
                'unsubscribe_text_custom' => '0',
                'unsubscribe_message_disabled' => '1',
                'error_text_custom' => '0',
                'reactivated_text_custom' => '0',
                'unsubscribed_text_custom' => '0',
                'unsubscribed_message_custom' => '0'
            ];
        }

        if ($sub === 'info') {
            return [
                'header_logo' => ['id' => 0],
                'header_title' => get_bloginfo('name'),
                'header_sub' => get_bloginfo('description'),
                'footer_title' => '',
                'footer_contact' => '',
                'footer_legal' => '',
                'facebook_url' => '',
                'twitter_url' => '',
                'instagram_url' => '',
                'pinterest_url' => '',
                'linkedin_url' => '',
                'tumblr_url' => '',
                'youtube_url' => '',
                'vimeo_url' => '',
                'soundcloud_url' => '',
                'telegram_url' => '',
                'vk_url' => ''
            ];
        }

        return [];
    }

    /**
     * Returns a translated text using the current Newsletter locale without changing the WP
     * locale.
     *
     * @param string $key
     * @param string $sub
     * @return string
     */
    static function get_text($key, $sub) {
        if (Newsletter::$locale && get_locale() !== Newsletter::$locale) {
            switch_to_locale(Newsletter::$locale);
        }
        $text = self::_get_text($key, $sub);
        restore_current_locale();
        return $text;
    }

    static private function _get_text($key, $sub) {

        if ($sub === 'form') {
            switch ($key) {
                case 'email': return __('Email', 'newsletter');
                case 'name': return __('First name', 'newsletter');
                case 'surname': return __('Last name', 'newsletter');
                case 'sex': return __('I\'m', 'newsletter');

                case 'privacy': return __('I accept the privacy policy', 'newsletter');

                case 'subscribe': return __('Subscribe', 'newsletter');

                case 'title_female': return __('Ms.', 'newsletter');
                case 'title_male': return __('Mr.', 'newsletter');
                case 'title_none': return __('Dear', 'newsletter');

                case 'sex_male': return __('Man', 'newsletter');
                case 'sex_female': return __('Woman', 'newsletter');
                case 'sex_none': return __('Not specified', 'newsletter');
            }
        }

        if ($sub === 'subscription') {
            switch ($key) {
                case 'error_text': return '<p>' . __('This email address is already subscribed, please contact the site administrator.', 'newsletter') . '</p>';
                case 'subscription_text': return "[newsletter_form]";

                case 'confirmation_text': return '<p>' . __('A confirmation email is on the way. Follow the instructions and check the spam folder. Thank you.', 'newsletter') . '</p>';
                case 'confirmation_subject': return __("Please confirm your subscription", 'newsletter');
                case 'confirmation_message': return '<p>' . __('Please confirm your subscription <a href="{subscription_confirm_url}">clicking here</a>', 'newsletter') . '</p>';

                case 'confirmed_text': return '<p>' . __('Your subscription has been confirmed', 'newsletter') . '</p>';
                case 'confirmed_subject': return __('Welcome', 'newsletter');
                case 'confirmed_message': return
                            "<p>" . __('This message confirms your subscription to our newsletter. Thank you!', 'newsletter') . '</p>' .
                            '<hr>' .
                            '<p><a href="{profile_url}">' . __('Change your profile', 'newsletter') . '</p>';
            }
        }

        if ($sub === 'profile') {
            switch ($key) {
                case 'text': return '[newsletter_profile]<p>' . __('If you change your email address a confirmation email will be sent to activate it.', 'newsletter') .
                            '</p>';

                case 'email_changed': return __("Your email has been changed, an activation email has been sent with instructions.", 'newsletter');
                case 'error': return __("Your email is not valid or already in use.", 'newsletter');
                case 'save_label': return __('Save', 'newsletter');
                case 'privacy_label': return __('Read our privacy policy', 'newsletter');
                case 'saved': return __('Saved.', 'newsletter');
            }
        }

        if ($sub === 'unsubscription') {
            switch ($key) {
                case 'unsubscribe_text': return '<p>' . __('Please confirm you want to unsubscribe.', 'newsletter') . '</p><p>[newsletter_unsubscribe_button label="" /]</p>';
                case 'error_text': return '<p>' . __("Subscriber not found, it probably has already been removed. No further actions are required.", 'newsletter') . '</p>';
                case 'unsubscribed_text': return "<p>" . __('Your subscription has been deleted. If that was an error you can subscribe again.', 'newsletter') . '</p><p>[newsletter_resubscribe_button label="" /]</p>';
                case 'unsubscribed_subject': return __("Goodbye", 'newsletter');
                case 'unsubscribed_message': return '<p>' . __('This message confirms that you have unsubscribed from our newsletter. Thank you.', 'newsletter') . '</p>';
                case 'reactivated_text': return '<p>' . __('Your subscription has been reactivated.', 'newsletter') . '</p>';
            }
        }

        return '';
    }

}
