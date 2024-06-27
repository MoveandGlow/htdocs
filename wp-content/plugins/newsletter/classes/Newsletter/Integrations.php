<?php

namespace Newsletter;

defined('ABSPATH') || exit;

class Integrations {

    static function is_addon_manager_active() {
        return class_exists('NewsletterExtensions');
    }

    static function is_leads_active() {
        return class_exists('NewsletterLeads');
    }

    static function get_leads_url() {
        if (self::is_leads_active()) {
            return '?page=newsletter_leads_index';
        } else {
            if (self::is_addon_manager_active()) {
                return '?page=newsletter_extensions_index#newsletter-leads';
            } else {
                return '?page=newsletter_main_extensions#newsletter-leads';
            }
        }
    }

    static function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    static function get_woocommerce_sources() {
        if (!self::is_woocommerce_active()) {
            return [];
        }
        $source1 = new Source('Checkout', 'WooCommerce', 'woocommerce');
        $source2 = new Source('Registration', 'WooCommerce', 'woocommerce');
        if (class_exists('NewsletterWoocommerce')) {
            $source1->config_url = '?page=newsletter_woocommerce_index';
            $source2->config_url = '?page=newsletter_woocommerce_index';
        }

        return [$source1, $source2];
    }

    static function is_edd_active() {
        return class_exists('EDD_Download');
    }

    static function get_edd_sources() {
        if (!self::is_edd_active()) {
            return [];
        }
        $source = new Source('Checkout', 'Easy Digital Downloads', 'edd');
        if (class_exists('NewsletterEdd')) {
            $source->config_url = '?page=newsletter_edd_index';
        }

        return [$source];
    }

    static function is_ultimatemember_active() {
        return defined('UM_VERSION');
    }

    static function get_ultimatemember_sources() {
        if (!self::is_ultimatemember_active()) {
            return [];
        }
        $source = new Source('Registration', 'Ultimate Member', 'ultimatemember');
        if (class_exists('NewsletterUltimatemember')) {
            $source->config_url = '?page=newsletter_ultimatemember_index';
        }

        return [$source];
    }

    static function is_pmpro_active() {
        return defined('PMPRO_VERSION');
    }

    static function get_pmpro_sources() {
        if (!self::is_pmpro_active()) {
            return [];
        }
        $source = new Source('Registration', 'Paid Membership Pro', 'pmpro');
        if (class_exists('NewsletterPmpro')) {
            $source->config_url = '?page=newsletter_pmpro_index';
        }

        return [$source];
    }

    static function is_cf7_active() {
        return defined('WPCF7_VERSION');
    }

    static function get_cf7_sources() {
        if (!self::is_cf7_active()) {
            return [];
        }
        $forms = get_posts(array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => 100));

        $sources = [];
        foreach ($forms as $form) {
            $source = new Source($form->post_title, 'Contact Form 7', 'cf7');
            if (class_exists('NewsletterCF7')) {
                $source->config_url = '?page=newsletter_cf7_edit&id=' . urlencode($form->ID);
            }
            $sources[] = $source;
        }
        return $sources;
    }

    static function is_gravityforms_active() {
        return class_exists('GFAPI');
    }

    static function get_gravityforms_sources() {
        if (!self::is_gravityforms_active()) {
            return [];
        }

        $forms = \GFAPI::get_forms();

        $sources = [];
        foreach ($forms as $form) {
            $source = new Source($form['title'], 'Gravity Forms', 'gravityforms');
            if (class_exists('NewsletterGravityForms')) {
                $source->config_url = '?page=newsletter_gravityforms_edit&id=' . urlencode($form['id']);
            }
            $sources[] = $source;
        }
        return $sources;
    }

    static function is_wpforms_active() {
        return class_exists('WPForms_Provider') && class_exists('WPForms');
    }

    static function get_wpforms_sources() {
        if (!self::is_wpforms_active()) {
            return [];
        }
        $forms = get_posts(array('post_type' => 'wpforms', 'nopaging' => true));

        $sources = [];
        foreach ($forms as $form) {
            $source = new Source($form->post_title, 'WP Forms', 'wpforms');
            if (class_exists('NewsletterWpForms')) {
                $source->config_url = '?page=newsletter_wpnlforms_edit&id=' . urlencode($form->ID);
            }
            $sources[] = $source;
        }
        return $sources;
    }

    static function is_forminator_active() {
        return class_exists('Forminator_API');
    }

    static function get_forminator_sources() {
        if (!self::is_forminator_active()) {
            return [];
        }
        $forms = \Forminator_API::get_forms();

        $sources = [];
        foreach ($forms as $form) {
            $source = new Source($form->name, 'Forminator', 'forminator');
            if (class_exists('NewsletterForminator')) {
                $source->config_url = '?page=newsletter_forminator_edit&id=' . urlencode($form->id);
            }
            $sources[] = $source;
        }
        return $sources;
    }

    static function is_formidable_active() {
        return class_exists('FrmForm');
    }

    static function get_formidable_sources() {
        if (!self::is_formidable_active()) {
            return [];
        }
        $forms = \FrmForm::get_published_forms();

        $sources = [];
        foreach ($forms as $form) {
            $source = new Source($form->name, 'Formidable Forms', 'formidable');
            if (class_exists('NewsletterFormidable')) {
                $source->config_url = '?page=newsletter_forminator_edit&id=' . urlencode($form->id);
            }
            $sources[] = $source;
        }
        return $sources;
    }

    static function is_ninjaforms_active() {
        return function_exists('Ninja_Forms');
    }

    static function get_ninjaforms_sources() {
        if (!self::is_ninjaforms_active()) {
            return [];
        }
        $forms = Ninja_Forms()->form()->get_forms();

        $sources = [];
        foreach ($forms as $form) {
            $source = new Source($form->get_setting('title'), 'Ninja Forms', 'ninjaforms');
            if (class_exists('NewsletterNinjaForms')) {
                $source->config_url = '?page=newsletter_forminator_edit&id=' . urlencode($form->get_id());
            }
            $sources[] = $source;
        }
        return $sources;
    }

    static function is_fluentforms_active() {
        return function_exists('fluentFormApi');
    }

    static function get_fluentforms_sources() {
        if (!self::is_fluentforms_active()) {
            return [];
        }

        $formApi = fluentFormApi('forms');
        $atts = [
            'status' => 'all',
            'sort_column' => 'id',
            'sort_by' => 'DESC',
            'per_page' => 100,
            'page' => 1
        ];
        $result = $formApi->forms($atts, true);
        $forms = $result['data'];

        $sources = [];
        foreach ($forms as $form) {
            $source = new Source($form->title, 'Fluent Forms', 'fluentforms');
            if (class_exists('NewsletterFluentForms')) {
                $source->config_url = '?page=newsletter_fluentforms_edit&id=' . urlencode($form->id);
            }
            $sources[] = $source;
        }

        return $sources;
    }

    static function config_button(Source $source, \NewsletterControls $controls) {
        static $default_url;
        if (!$default_url) {
            if (class_exists('NewsletterExtensions')) {
                $default_url = '?page=newsletter_extensions_index';
            } else {
                $default_url = '?page=newsletter_main_extension';
            }
        }

        if ($source->config_url) {
            $controls->button_icon_configure($source->config_url, ['target' => '_blank']);
        } else {
            $controls->btn_link($default_url . '#newsletter-' . $source->slug, 'Addon required', ['tertiary' => true, 'target' => '_blank']);
        }
    }

    static function source_row($source, $controls) {
        echo '<tr><td>', esc_html($source->plugin), '</td>';
        echo '<td>', esc_html($source->name), '</td>';
        echo '<td>';
        self::config_button($source, $controls);
        echo '</td></tr>';
    }

    static function source_rows(array $sources, $controls) {
        foreach ($sources as $source) {
            self::source_row($source, $controls);
        }
    }
}
