<?php

class NewsletterComposer {

    static $instance;
    var $logger;
    var $blocks = null;
    var $templates = null;

    //const presets = ['halloween', 'zen', 'black-friday', "cta", "invite", "announcement", "posts", "sales", "product", "tour", "simple"];
    const presets = ['welcome-1', 'valentine', 'black-friday', 'black-friday-2', "event", 'halloween', 'zen', "cta", "announcement", "posts", "sales", "product", "tour", "simple"];

    /**
     *
     * @return NewsletterComposer
     */
    static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function __construct() {
        $this->logger = new NewsletterLogger('composer');
    }

    /**
     * Encodes an array of options to be inserted in the block HMTL.
     *
     * @param array $options
     * @return string
     */
    static function options_encode($options) {
        return base64_encode(json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP));
    }

    /**
     * Decodes a string representing a set of encoded options of a block.
     * For compatibility tries different kinds of decoding.
     *
     * @param string $options
     * @return array
     */
    static function options_decode($options) {
        // Old "query string" format
        if (is_string($options) && strpos($options, 'options[') !== false) {
            $opts = [];
            parse_str($options, $opts);
            $options = $opts['options'];
        }

        if (is_array($options)) {
            return $options;
        }

        // Json data should be base64 encoded, but for short time it wasn't
        $tmp = json_decode($options, true);
        if (is_null($tmp)) {
            return json_decode(base64_decode($options), true);
        } else {
            return $tmp;
        }
    }

    /**
     * Return a single block (associative array) checking for legacy ID as well.
     *
     * @param string $id
     * @return array
     */
    function get_block($id) {
        switch ($id) {
            case 'content-03-text.block':
                $id = 'text';
                break;
            case 'footer-03-social.block':
                $id = 'social';
                break;
            case 'footer-02-canspam.block':
                $id = 'canspam';
                break;
            case 'content-05-image.block':
                $id = 'image';
                break;
            case 'header-01-header.block':
                $id = 'header';
                break;
            case 'footer-01-footer.block':
                $id = 'footer';
                break;
            case 'content-02-heading.block':
                $id = 'heading';
                break;
            case 'content-07-twocols.block':
            case 'content-06-posts.block':
                $id = 'posts';
                break;
            case 'content-04-cta.block':
                $id = 'cta';
                break;
            case 'content-01-hero.block':
                $id = 'hero';
                break;
//            case 'content-02-heading.block': $id = '/plugins/newsletter/emails/blocks/heading';
//                break;
        }

        // Conversion for old full path ID
        $id = sanitize_key(basename($id));

        // TODO: Correct id for compatibility
        $blocks = $this->get_blocks();
        if (!isset($blocks[$id])) {
            return null;
        }
        return $blocks[$id];
    }

    /**
     * Array of arrays with every registered block and legacy block converted to the new
     * format.
     *
     * @return array
     */
    function get_blocks() {

        if (!is_null($this->blocks)) {
            return $this->blocks;
        }

        $this->blocks = $this->scan_blocks_dir(NEWSLETTER_DIR . '/emails/blocks');

        $extended = $this->scan_blocks_dir(WP_CONTENT_DIR . '/extensions/newsletter/blocks');

        $this->blocks = array_merge($extended, $this->blocks);

        // Old way to register a folder of blocks to be scanned
        $dirs = apply_filters('newsletter_blocks_dir', []);

        $this->logger->debug('Folders registered to be scanned for blocks:');
        $this->logger->debug($dirs);

        foreach ($dirs as $dir) {
            $list = $this->scan_blocks_dir($dir);
            $this->blocks = array_merge($list, $this->blocks);
        }

        do_action('newsletter_register_blocks');

        foreach (TNP_Composer::$block_dirs as $dir) {
            $block = $this->build_block($dir);
            if (is_wp_error($block)) {
                $this->logger->error($block);
                continue;
            }
            if (!isset($this->blocks[$block['id']])) {
                $this->blocks[$block['id']] = $block;
            } else {
                $this->logger->error('The block "' . $block['id'] . '" has already been registered');
            }
        }

        $this->blocks = array_reverse($this->blocks);
        return $this->blocks;
    }

    function scan_blocks_dir($dir) {
        $dir = realpath($dir);
        if (!$dir) {
            return [];
        }
        $dir = wp_normalize_path($dir);

        $list = [];
        $handle = opendir($dir);
        while ($file = readdir($handle)) {
            if (substr($file, 0, 1) === '.') {
                continue;
            }

            $data = $this->build_block($dir . '/' . $file);

            if (is_wp_error($data)) {
                $this->logger->error($data);
                continue;
            }
            $list[$data['id']] = $data;
        }
        closedir($handle);
        return $list;
    }

    /**
     * Builds a block data structure starting from the folder containing the block
     * files.
     *
     * @param string $dir
     * @return array | WP_Error
     */
    function build_block($dir) {
        $dir = realpath($dir);
        $dir = wp_normalize_path($dir);
        $full_file = $dir . '/block.php';
        if (!is_file($full_file)) {
            return new WP_Error('1', 'Missing block.php file in ' . $dir);
        }

        $wp_content_dir = wp_normalize_path(realpath(WP_CONTENT_DIR));

        $relative_dir = substr($dir, strlen($wp_content_dir));
        $file = basename($dir);

        $data = get_file_data($full_file, ['name' => 'Name', 'section' => 'Section', 'description' => 'Description', 'type' => 'Type']);
        $defaults = ['section' => 'content', 'name' => ucfirst($file), 'descritpion' => '', 'icon' => plugins_url('newsletter') . '/admin/images/block-icon.png'];
        $data = array_merge($defaults, $data);

        if (is_file($dir . '/icon.png')) {
            $data['icon'] = content_url($relative_dir . '/icon.png');
        }

        $data['id'] = sanitize_key($file);

        // Absolute path of the block files
        $data['dir'] = $dir;
        $data['url'] = content_url($relative_dir);

        return $data;
    }

    /**
     * Buils the global email CSS merging the standard ones with all blocks' global
     * CSS.
     *
     * @return type
     */
    function get_composer_css() {
        $css = file_get_contents(NEWSLETTER_DIR . '/emails/tnp-composer/css/newsletter.css');
        $blocks = $this->get_blocks();
        foreach ($blocks as $block) {
            if (!file_exists($block['dir'] . '/style.css')) {
                continue;
            }
            $css .= "\n\n";
            $css .= "/* " . $block['name'] . " */\n";
            $css .= file_get_contents($block['dir'] . '/style.css');
        }
        return $css;
    }

    function get_composer_backend_css() {
        $css = file_get_contents(NEWSLETTER_DIR . '/emails/tnp-composer/css/backend.css');
        $css .= "\n\n";
        $css .= $this->get_composer_css();
        return $css;
    }

    function get_preset_from_file($id, $dir = null) {

        $templates = $this->get_templates();
        if (isset($templates[$id])) {
            return $templates[$id];
        }

        if (is_null($dir)) {
            $dir = NEWSLETTER_DIR . '/emails/presets';
        }

        $id = NewsletterModule::sanitize_file_name($id);

        if (!is_dir($dir . '/' . $id) || !in_array($id, self::presets)) {
            return array();
        }

        $json_content = file_get_contents("$dir/$id/preset.json");
        $json_content = str_replace("{placeholder_base_url}", plugins_url('newsletter') . '/emails/presets', $json_content);
        $json = json_decode($json_content);
        $json->icon = Newsletter::plugin_url() . "/emails/presets/$id/icon.png?ver=2";

        return $json;
    }

    function build_template($dir) {
        $dir = realpath($dir);
        $dir = wp_normalize_path($dir);
        $dir = untrailingslashit($dir);
        $full_file = $dir . '/template.json';
        if (!is_file($full_file)) {
            return new WP_Error('1', 'Missing template.json file in ' . $dir);
        }

        $wp_content_dir = wp_normalize_path(realpath(WP_CONTENT_DIR));

        $relative_dir = substr($dir, strlen($wp_content_dir));
        $file = basename($dir);

        $template_url = content_url($relative_dir);
        $json = file_get_contents($full_file);
        $json = str_replace("{template_url}", $template_url, $json);
        $data = json_decode($json);
        if (!$data) {
            return new WP_Error('1', 'Unable to decode the template JSON in ' . $dir);
        }
        $data->icon = $template_url . "/icon.png?ver=2";
        $data->id = sanitize_key(basename($dir));
        $data->url = $template_url;

        return $data;
    }

    function get_templates() {

        if (!is_null($this->templates)) {
            return $this->templates;
        }
        $this->templates = [];
        do_action('newsletter_register_templates');

        foreach (TNP_Composer::$template_dirs as $dir) {
            $template = $this->build_template($dir);
            if (is_wp_error($template)) {
                $this->logger->error($template);
                continue;
            }
            if (!isset($this->templates[$template->id])) {
                $this->templates[$template->id] = $template;
            } else {
                $this->logger->error('The template "' . $template->id . '" has already been registered');
            }
        }

        return $this->templates;
    }

    /**
     *
     * @param string $dir
     * @return type
     *
     * @deprecated
     */
    function scan_presets_dir($dir = null) {

        if (is_null($dir)) {
            $dir = __DIR__ . '/presets';
        }

        if (!is_dir($dir)) {
            return array();
        }

        $handle = opendir($dir);
        $list = array();
        $relative_dir = substr($dir, strlen(WP_CONTENT_DIR));
        while ($file = readdir($handle)) {

            if ($file == '.' || $file == '..')
                continue;

            // The block unique key, we should find out how to build it, maybe an hash of the (relative) dir?
            $preset_id = sanitize_key($file);

            $full_file = $dir . '/' . $file . '/preset.json';

            if (!is_file($full_file)) {
                continue;
            }

            $icon = content_url($relative_dir . '/' . $file . '/icon.png');

            $list[$preset_id] = $icon;
        }
        closedir($handle);
        return $list;
    }

    private function is_a_tnp_default_preset($preset_id) {
        return in_array($preset_id, self::presets);
    }

    function extract_composer_options($email) {
        $composer = ['width' => 600];
        foreach ($email->options as $k => $v) {
            if (strpos($k, 'composer_') === 0) {
                $composer[substr($k, 9)] = $v;
            }
        }
        return $composer;
    }

    function get_preset_composer_options($preset_id) {
        $templates = $this->get_templates();
        if (isset($templates[$preset_id])) {
            return (array) $templates[$preset_id]->settings;
        }

        if ($this->is_a_tnp_default_preset($preset_id)) {
            $preset = $this->get_preset_from_file($preset_id);
            if (!empty($preset->version) && $preset->version == 2) {
                return (array) $preset->settings;
            }

            // Preset version 1 haven't global options
            $composer = [];
            $options = TNP_Composer::get_global_style_defaults();
            foreach ($options as $k => $v) {
                if (strpos($k, 'options_composer_') === 0) {
                    $composer[substr($k, 17)] = $v;
                }
            }
            return $composer;
        }

        // Get preset from db
        $preset_email = NewsletterEmails::instance()->get_email($preset_id);
        $global_options = $this->extract_composer_options($preset_email);

        return $global_options;
    }

    /**
     *
     * @param mixed $preset_id
     * @return string
     *
     * @todo Decouple from NewsletterEmailsAdmin
     */
    function get_preset_content($preset_id) {

        $content = '';

        $templates = $this->get_templates();
        if (isset($templates[$preset_id])) {
            $composer = (array) $templates[$preset_id]->settings;
            foreach ($templates[$preset_id]->blocks as $item) {
                $options = (array) $item;
                foreach ($options as &$o) {
                    if (is_object($o)) {
                        $o = (array) $o;
                    }
                }
                ob_start();
                $this->render_block($item->block_id, true, $options, [], $composer);
                $content .= trim(ob_get_clean());
            }
            return $content;
        }


        if ($this->is_a_tnp_default_preset($preset_id)) {

            // Get preset from file
            $preset = $this->get_preset_from_file($preset_id);

            if (!empty($preset->version) && $preset->version == 2) {
                $composer = (array) $preset->settings;
                foreach ($preset->blocks as $item) {
                    $options = (array) $item;
                    foreach ($options as &$o) {
                        if (is_object($o)) {
                            $o = (array) $o;
                        }
                    }
                    ob_start();
                    $this->render_block($item->block_id, true, $options, [], $composer);
                    $content .= trim(ob_get_clean());
                    //die($content);
                }
            } else {
                $composer = $this->get_preset_composer_options($preset_id);
                foreach ($preset->blocks as $item) {
                    ob_start();
                    $this->render_block($item->block, true, (array) $item->options, [], $composer);
                    $content .= trim(ob_get_clean());
                }
            }
        } else {
            $email = NewsletterEmailsAdmin::instance()->get_email($preset_id);
            if ($email) {
                $composer = $this->extract_composer_options($email);
                $result = $this->regenerate_blocks($email->message, [], $composer);
                $content = $result['content'];
            }
        }

        return $content;
    }

    /**
     * Creates an email using the template stored in the provided folder. Asking for a folder
     * contraints to use a standard structure for the template avoiding wilde behaviors. :-)
     *
     * The returned email is not saved into the database!
     *
     * @param string $dir Folder containing the template (at minimim the template.json file)
     * @return \WP_Error|\TNP_Email
     */
    function build_email_from_template($dir) {

        $dir = wp_normalize_path($dir);
        $dir = realpath($dir);
        $dir = untrailingslashit($dir);

        $file = $dir . '/template.json';

        if (!file_exists($file)) {
            return new WP_Error('missing', 'The template.json file is missing');
        }

        // TODO: Checks? Which ones?

        $template = json_decode(file_get_contents($file));
        $content = '';
        $composer = (array) $template->settings;
        foreach ($template->blocks as $item) {
            $options = (array) $item;
            // Convert structured options to array (the json is decoded as "object")
            foreach ($options as &$o) {
                if (is_object($o)) {
                    $o = (array) $o;
                }
            }
            ob_start();
            $this->render_block($item->block_id, true, $options, [], $composer);
            $content .= trim(ob_get_clean());
        }

        $email = new TNP_Email();
        $email->editor = TNP_Email::EDITOR_COMPOSER;
        $email->options['composer'] = $composer;
        $email->subject = $template->subject ?? '[missing subject]';
        $email->message = TNP_Composer::get_html_open($email) . TNP_Composer::get_main_wrapper_open($email) .
                $content . TNP_Composer::get_main_wrapper_close($email) . TNP_Composer::get_html_close($email);

        return $email;
    }

    /**
     * Renders a block identified by its id, using the block options and adding a wrapper
     * if required (for the first block rendering).
     *
     * @param string $block_id
     * @param boolean $wrapper
     * @param array $options
     * @param array $context
     * @param array $composer
     */
    function render_block($block_id = null, $wrapper = false, $options = [], $context = [], $composer = []) {
        static $kses_style_filter = false;
        include_once NEWSLETTER_INCLUDES_DIR . '/helper.php';

        if (!is_array($options)) {
            $options = [];
        }

        // On block first creation we still do not have the defaults... this is a problem we need to address in a new
        // composer version
        $common_defaults = array(
            //'block_padding_top' => 0,
            //'block_padding_bottom' => 0,
            //'block_padding_right' => 0,
            //'block_padding_left' => 0,
            'block_background' => '',
            'block_background_2' => '',
            'block_width' => $composer['width'],
            'block_align' => 'center'
        );

        $options = array_merge($common_defaults, $options);

        //Remove 'options_composer_' prefix
        $composer_defaults = ['width' => 600];
        foreach (TNP_Composer::get_global_style_defaults() as $global_option_name => $global_option) {
            $composer_defaults[str_replace('options_composer_', '', $global_option_name)] = $global_option;
        }
        $composer = array_merge($composer_defaults, $composer);
        $composer['width'] = (int) $composer['width'];
        if (empty($composer['width'])) {
            $composer['width'] = 600;
        }

        $block_padding_right = empty($options['block_padding_right']) ? 0 : intval($options['block_padding_right']);
        $block_padding_left = empty($options['block_padding_left']) ? 0 : intval($options['block_padding_left']);

        $composer['content_width'] = $composer['width'] - $block_padding_left - $block_padding_right;

        $width = $composer['width'];
        $font_family = 'Helvetica, Arial, sans-serif';

        $global_title_font_family = $composer['title_font_family'];
        $global_title_font_size = $composer['title_font_size'];
        $global_title_font_color = $composer['title_font_color'];
        $global_title_font_weight = $composer['title_font_weight'];

        $global_text_font_family = $composer['text_font_family'];
        $global_text_font_size = $composer['text_font_size'];
        $global_text_font_color = $composer['text_font_color'];
        $global_text_font_weight = $composer['text_font_weight'];

        $global_button_font_family = $composer['button_font_family'];
        $global_button_font_size = $composer['button_font_size'];
        $global_button_font_color = $composer['button_font_color'];
        $global_button_font_weight = $composer['button_font_weight'];
        $global_button_background_color = $composer['button_background_color'];

        $global_block_background = $composer['block_background'];

        $info = Newsletter::instance()->get_options('info');

        // This code filters the HTML to remove javascript and unsecure attributes and enable the
        // "display" rule for CSS which is needed in blocks to force specific "block" or "inline" or "table".
        add_filter('safe_style_css', [$this, 'hook_safe_style_css'], 9999);
        $options = wp_kses_post_deep($options);
        remove_filter('safe_style_css', [$this, 'hook_safe_style_css']);

        $block = $this->get_block($block_id);

        if (!isset($context['type'])) {
            $context['type'] = '';
        }

        // Block not found
        if (!$block) {
            if ($wrapper) {
                echo '<table border="0" cellpadding="0" cellspacing="0" align="center" width="100%" style="border-collapse: collapse; width: 100%;" class="tnpc-row tnpc-row-block" data-id="', esc_attr($block_id), '">';
                echo '<tr>';
                echo '<td data-options="" bgcolor="#ffffff" align="center" style="padding: 0; font-family: Helvetica, Arial, sans-serif; mso-line-height-rule: exactly;" class="edit-block">';
            }
            echo $this->get_outlook_wrapper_open($composer['width']);

            echo '<p>Ops, this block type is not avalable.</p>';

            echo $this->get_outlook_wrapper_close();

            if ($wrapper) {
                echo '</td></tr></table>';
            }
            return;
        }

        $out = ['subject' => '', 'return_empty_message' => false, 'stop' => false, 'skip' => false];

        $dir = is_rtl() ? 'rtl' : 'ltr';
        $align_left = is_rtl() ? 'right' : 'left';
        $align_right = is_rtl() ? 'left' : 'right';

        ob_start();
        $logger = $this->logger;
        include $block['dir'] . '/block.php';
        $content = trim(ob_get_clean());

        if (empty($content)) {
            return $out;
        }

        // Obsolete
        $content = str_replace('{width}', $composer['width'], $content);

        $content = NewsletterEmails::instance()->inline_css($content, true);

        // CSS driven by the block
        // Requited for the server side parsing and rendering
        $options['block_id'] = $block_id;

        // Fixes missing defaults by some old blocks
        $options = array_merge([
            'block_padding_top' => '0',
            'block_padding_bottom' => '0',
            'block_padding_right' => '0',
            'block_padding_left' => '0'
                ], $options);

        $options['block_padding_top'] = (int) str_replace('px', '', $options['block_padding_top']);
        $options['block_padding_bottom'] = (int) str_replace('px', '', $options['block_padding_bottom']);
        $options['block_padding_right'] = (int) str_replace('px', '', $options['block_padding_right']);
        $options['block_padding_left'] = (int) str_replace('px', '', $options['block_padding_left']);

        $block_background = empty($options['block_background']) ? $global_block_background : $options['block_background'];

        // Internal TD wrapper
        $style = 'text-align: center; ';
        //$style .= 'width: 100% !important; ';
        $style .= 'line-height: normal !important; ';
        $style .= 'letter-spacing: normal; ';
        $style .= 'mso-line-height-rule: exactly; outline: none; ';
        $style .= 'padding: ' . $options['block_padding_top'] . 'px ' . $options['block_padding_right'] . 'px ' . $options['block_padding_bottom'] . 'px ' .
                $options['block_padding_left'] . 'px;';

        if (!empty($block_background)) {
            $style .= 'background-color: ' . $block_background . ';';
        }

        if (isset($options['block_background_gradient'])) {
            $style .= 'background: linear-gradient(180deg, ' . $block_background . ' 0%, ' . $options['block_background_2'] . '  100%);';
        }

        $data = $this->options_encode($options);
        // First time block creation wrapper
        if ($wrapper) {
            echo '<table border="0" cellpadding="0" cellspacing="0" align="center" width="100%" style="border-collapse: collapse; width: 100%;" class="tnpc-row tnpc-row-block" data-id="', esc_attr($block_id), '">', "\n";
            echo "<tr>";
            echo '<td align="center" style="padding: 0;" class="edit-block">', "\n";
        }

        // Container that fixes the width and makes the block responsive
        echo $this->get_outlook_wrapper_open($options['block_width']);

        echo '<table type="options" data-json="', esc_attr($data), '" class="tnpc-block-content" border="0" cellpadding="0" align="center" cellspacing="0" width="100%" style="width: 100%!important; max-width: ', $composer['width'], 'px!important">', "\n";
        echo "<tr>";
        //echo '<td align="', esc_attr($options['block_align']), '" style="', esc_attr($style), '" bgcolor="', esc_attr($block_background), '" width="100%">';
        echo '<td align="', esc_attr($options['block_align']), '" style="', esc_attr($style), '" bgcolor="', esc_attr($block_background), '">';

        //echo "<!-- block generated content -->\n";
        echo trim($content);
        //echo "\n<!-- /block generated content -->\n";

        echo "</td></tr></table>";
        echo $this->get_outlook_wrapper_close();

        // First time block creation wrapper
        if ($wrapper) {
            echo "</td></tr></table>";
        }

        return $out;
    }

    /**
     * Filter to enable the "display" attribute on CSS filterred by wp_kses_post_deep used
     * when rendering a block.
     *
     * @param array $rules
     * @return string
     */
    function hook_safe_style_css($rules) {
        $rules[] = 'display';
        return $rules;
    }

    static function get_outlook_wrapper_open($width = 600) {
        return '<!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" align="center" cellspacing="0" width="' . $width . '"><tr><td width="' . $width . '" style="vertical-align:top;width:' . $width . 'px;"><![endif]-->';
    }

    static function get_outlook_wrapper_close() {
        return "<!--[if mso | IE]></td></tr></table><![endif]-->";
    }

    /**
     *
     * @param TNP_Email $email
     */
    function to_json($email) {
        $data = ['version' => 2];
        $data['settings'] = $this->extract_composer_options($email);
        $data['subject'] = $email->subject;

        preg_match_all('/data-json="(.*?)"/m', $email->message, $matches, PREG_PATTERN_ORDER);

        $data['blocks'] = [];
        foreach ($matches[1] as $match) {
            $a = html_entity_decode($match, ENT_QUOTES, 'UTF-8');
            $data['blocks'][] = self::options_decode($a);
        }
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

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

        $this->logger->debug('Regenerating email ' . $email->id);

        $context = array_merge(['last_run' => 0, 'type' => ''], $context);

        $this->logger->debug($context);

        $composer = $this->extract_composer_options($email);

        $result = $this->regenerate_blocks($email->message, $context, $composer);

        // One block is signalling the email should not be regenerated (usually from Automated)
        if ($result === false) {
            $this->logger->debug('A block stopped the regeneration');
            return false;
        }

        $email->message = TNP_Composer::get_html_open($email) . TNP_Composer::get_main_wrapper_open($email) .
                $result['content'] . TNP_Composer::get_main_wrapper_close($email) . TNP_Composer::get_html_close($email);

        if (!empty($result['subject'])) {
            $email->subject = $result['subject'];
        }

        $this->logger->debug('Regeneration completed');

        return true;
    }

    /**
     * Regenerates all blocks found in the content (email body) and return the new content (without the
     * HTML wrap)
     *
     * @param string $content
     * @param array $context
     * @param array $composer
     * @return array content and subject or false
     */
    function regenerate_blocks($content, $context = [], $composer = []) {
        $this->logger->debug('Blocks regeneration started');

        preg_match_all('/data-json="(.*?)"/m', $content, $matches, PREG_PATTERN_ORDER);

        $this->logger->debug('Found ' . count($matches[1]) . ' blocks');

        // Compatibility
        $width = $composer['width'];

        $result = ['content' => '', 'subject' => ''];

        foreach ($matches[1] as $match) {
            $a = html_entity_decode($match, ENT_QUOTES, 'UTF-8');
            $options = $this->options_decode($a);

            $this->logger->debug('Regenerating block ' . $options['block_id']);

            $block = $this->get_block($options['block_id']);
            if (!$block) {
                $this->logger->debug('Unable to load the block ' . $options['block_id']);
                continue;
            }

            ob_start();
            $out = $this->render_block($options['block_id'], true, $options, $context, $composer);
            if (is_array($out)) {
                if ($out['return_empty_message'] || $out['stop']) {
                    $this->logger->debug('The block stopped the regeneration');
                    return false;
                }
                if ($out['skip']) {
                    $this->logger->debug('The block indicated to skip it');
                    continue;
                }
                if (empty($result['subject']) && !empty($out['subject'])) {
                    $this->logger->debug('The block suggested the subject: ' . $out['subject']);
                    $result['subject'] = strip_tags($out['subject']);
                }
            }
            $block_html = ob_get_clean();
            $result['content'] .= $block_html;
        }

        $this->logger->debug('Blocks regeneration completed');

        return $result;
    }
}
