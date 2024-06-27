<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;

if (!$controls->is_action()) {
    $controls->data = $this->get_options('form', $language);
} else {
    if ($controls->is_action('save')) {

        // Processing profile fields
        if (!$language) {
            for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
                if (empty($controls->data['profile_' . $i])) {
                    unset($controls->data['profile_' . $i]);
                }
                if (empty($controls->data['profile_' . $i . '_options'])) {
                    unset($controls->data['profile_' . $i . '_options']);
                }
                if (empty($controls->data['profile_' . $i . '_placeholder'])) {
                    unset($controls->data['profile_' . $i . '_placeholder']);
                }
            }
        }
        $this->save_options($controls->data, 'form', $language);
        $controls->data = $this->get_options('form', $language);
        $controls->add_toast_saved();
    }
}

$status = array(0 => __('Hide', 'newsletter'), 1 => __('Show', 'newsletter'));
$rules = array(0 => __('Optional', 'newsletter'), 1 => __('Required', 'newsletter'));
$extra_type = array('text' => __('Text', 'newsletter'), 'select' => __('List', 'newsletter'));

$main_options = $this->get_main_options('form');
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscription') ?>
        <h2><?php esc_html_e('Standard Form', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav-form.php' ?>

    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <p>
            The standard subscription form settings.
            See it on <a href="<?php echo esc_attr(Newsletter::instance()->get_newsletter_page_url()) ?>" target="_blank">the Newsletter public page</a>.
        </p>

        <form action="" method="post">
            <?php $controls->init(); ?>

            <div id="tabs">

                <ul>
                    <li><a href="#tabs-fields"><?php esc_html_e('Main fields', 'newsletter') ?></a></li>
                    <li><a href="#tabs-lists"><?php esc_html_e('Lists', 'newsletter') ?></a></li>
                    <li><a href="#tabs-customfields"><?php esc_html_e('Custom fields', 'newsletter') ?></a></li>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <li><a href="#tabs-debug">Debug</a></li>
                    <?php } ?>
                </ul>

                <div id="tabs-fields">

                    <?php $this->language_notice(); ?>

                    <table class="widefat" style="width: auto">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Show</th>
                                <th>Label</th>
                                <th>Placeholder</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tr>
                            <th><?php esc_html_e('Email', 'newsletter') ?></th>
                            <td>
                                <?php if (!$language) { ?>
                                    <input type="checkbox" checked disabled>
                                <?php } ?>
                            </td>
                            <td>
                                <?php $controls->text('email', 20, $this->get_default_text('email', 'form')); ?>
                            </td>
                            <td>
                                <?php $controls->text('email_placeholder', 20, $this->get_default_text('email_placeholder', 'form')); ?>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('First name', 'newsletter') ?></th>
                            <td>
                                <?php if (!$language) { ?>
                                    <?php $controls->checkbox2('name_status', '', ['title' => __('Show', 'newsletter')]); ?>
                                <?php } ?>
                            </td>
                            <td>
                                <?php $controls->text('name', 20, $this->get_default_text('name', 'form')); ?>
                            </td>
                            <td>
                                <?php $controls->text('name_placeholder', 20, $this->get_default_text('name_placeholder', 'form')); ?>
                            </td>
                            <td>
                                <?php if (!$language) { ?>
                                    <?php $controls->select('name_rules', $rules); ?>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Last name', 'newsletter') ?></th>
                            <td>
                                <?php if (!$language) { ?>
                                    <?php $controls->checkbox2('surname_status', '', ['title' => __('Show', 'newsletter')]); ?>
                                <?php } ?>
                            </td>
                            <td>
                                <?php $controls->text('surname', 20, $this->get_default_text('surname', 'form')); ?>
                            </td>
                            <td>
                                <?php $controls->text('surname_placeholder', 20, $this->get_default_text('surname_placeholder', 'form')); ?>
                            </td>
                            <td>
                                <?php if (!$language) { ?>
                                    <?php $controls->select('surname_rules', $rules); ?>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="vertical-align: top"><?php esc_html_e('Gender', 'newsletter') ?></th>
                            <td style="vertical-align: top">
                                <?php if (!$language) { ?>
                                    <?php $controls->checkbox2('sex_status'); ?>
                                <?php } ?>
                            </td>
                            <td style="vertical-align: top">
                                <?php $controls->text('sex', 20, $this->get_default_text('sex', 'form')); ?>

                                <br><br>

                                <strong><?php esc_html_e('Option labels', 'newsletter') ?></strong>
                                <br>
                                <?php $controls->text('sex_none', 20, $this->get_default_text('sex_none', 'form')); ?><br>
                                <?php $controls->text('sex_female', 20, $this->get_default_text('sex_female', 'form')); ?><br>
                                <?php $controls->text('sex_male', 20, $this->get_default_text('sex_male', 'form')); ?><br>
                            </td>
                            <td style="vertical-align: top"></td>
                            <td style="vertical-align: top">
                                <?php if (!$language) { ?>
                                    <?php $controls->select('sex_rules', $rules); ?>
                                <?php } ?>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Button', 'newsletter') ?></th>
                            <td></td>
                            <td>
                                <?php $controls->text('subscribe', 20, $this->get_default_text('subscribe', 'form')); ?>
                            </td>
                        </tr>

                    </table>

                    <h3><?php _e('Privacy', 'newsletter') ?></h3>
                    <table class="widefat" style="width: auto">
                        <thead>
                            <tr>
                                <th>&nbsp;</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <?php if (!$language) { ?>
                            <tr>
                                <th><?php esc_html_e('Enabled?', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->select('privacy_status', array(0 => __('No', 'newsletter'), 1 => __('Yes', 'newsletter'), 2 => __('Only the notice', 'newsletter'))); ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <th><?php esc_html_e('Label', 'newsletter') ?></th>
                            <td><?php $controls->text('privacy', 50, $this->get_default_text('privacy', 'form')); ?></td>
                        </tr>
                        <tr>
                            <th>Privacy URL</th>
                            <td>
                                <?php if ($language && !empty($controls->data['privacy_use_wp_url'])) { ?>
                                    <?php esc_html_e('The "all language" setting is set to use the WordPress default privacy page. Please translate that page.', 'newsletter') ?>
                                <?php } else { ?>
                                    <?php if (!$language) { ?>
                                        <?php if (function_exists('get_privacy_policy_url') && get_privacy_policy_url()) { ?>
                                            <?php $controls->checkbox('privacy_use_wp_url', __('Use WordPress privacy URL', 'newsletter')); ?>
                                            (<a href="<?php echo esc_attr(get_privacy_policy_url()) ?>"><?php echo esc_html(get_privacy_policy_url()) ?></a>)
                                            <br>OR<br>
                                        <?php } ?>
                                    <?php } ?>
                                    <?php if ($language) { ?>
                                        <?php esc_html_e('To use the WordPress privacy page, switch to "all language" and activate it.', 'newsletter') ?><br>
                                    <?php } ?>
                                    <?php $controls->text_url('privacy_url', 50); ?>
                                <?php } ?>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <?php esc_html_e('The privacy acceptance checkbox (required in many Europen countries) forces the subscriber to check it before proceeding. If an URL is specified the label becomes a link.', 'newsletter') ?>
                    </p>

                    <h3><?php esc_html_e('Salutation', 'newsletter') ?></h3>
                    <table class="widefat" style="width: auto">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Gender', 'newsletter') ?></th>
                                <th><?php esc_html_e('Label', 'newsletter') ?></th>
                            </tr>
                        </thead>
                        <tr>
                            <th><?php esc_html_e('Generic', 'newsletter') ?></th>
                            <td><?php $controls->text('title_none', 10, $this->get_default_text('title_none', 'form')); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Female', 'newsletter') ?></th>
                            <td><?php $controls->text('title_female', 10, $this->get_default_text('title_female', 'form')); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Male', 'newsletter') ?></th>
                            <td><?php $controls->text('title_male', 10, $this->get_default_text('title_male', 'form')); ?></td>
                        </tr>

                    </table>

                    <p>
                        <?php esc_html_e('Salutation titles are inserted in emails message when the tag {title} is used. For example "Good morning {title} {surname} {name}".', 'newsletter') ?>
                    </p>

                </div>

                <div id="tabs-lists">

                    <?php $this->language_notice(); ?>

                    <?php if (!$language) { ?>

                        <?php
                        $lists = $this->get_lists_public();
                        ?>
                        <table class="widefat" style="width: auto">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th><?php esc_html_e('Show', 'newsletter') ?></th>
                                    <th><?php esc_html_e('Checked', 'newsletter') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lists as $list) { ?>
                                    <tr>
                                        <td><?php echo esc_html($list->id) ?></td>
                                        <td><?php echo esc_html($list->name) ?></td>
                                        <td><?php $controls->checkbox_group('lists', $list->id) ?></td>
                                        <td><?php $controls->checkbox_group('lists_checked', $list->id) ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>

                        </table>

                    <?php } ?>

                    <p>
                        <a href="?page=newsletter_subscription_lists" target="_blank"><?php esc_html_e('Configure', 'newsletter') ?></a>
                    </p>
                </div>

                <div id="tabs-customfields">

                    <?php $this->language_notice(); ?>


                    <?php if (!$language) { ?>
                        <?php
                        $customfields = $this->get_customfields_public();
                        ?>

                        <table class="widefat" style="width: auto">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th><?php esc_html_e('Show', 'newsletter') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customfields as $customfield) { ?>
                                    <tr>
                                        <td><?php echo esc_html($customfield->id) ?></td>
                                        <td><?php echo esc_html($customfield->name) ?></td>
                                        <td><?php $controls->checkbox_group('customfields', $customfield->id) ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>

                        </table>
                    <?php } ?>

                    <p>
                        <a href="?page=newsletter_subscription_customfields" target="_blank"><?php esc_html_e('Configure', 'newsletter') ?></a>
                    </p>

                </div>

                <?php if (NEWSLETTER_DEBUG) { ?>
                    <div id="tabs-debug">
                        <pre><?php echo esc_html(wp_json_encode($this->get_db_options('form', $language), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                <?php } ?>



            </div>

            <p>
                <?php $controls->button_save(); ?>
            </p>

        </form>


    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
