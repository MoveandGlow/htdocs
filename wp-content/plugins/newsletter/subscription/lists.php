<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;

if (!$controls->is_action()) {
    $controls->data = $this->get_options('lists', $language);
} else {
    if ($controls->is_action('save')) {

        // Processing lists for specific language
        if ($language) {
            for ($i = 0; $i <= NEWSLETTER_LIST_MAX; $i++) {
                if (empty($controls->data['list_' . $i])) {
                    unset($controls->data['list_' . $i]);
                }
            }
        }

        $this->save_options($controls->data, 'lists', $language);
        $controls->add_toast_saved();
    }

    if ($controls->is_action('unlink')) {
        $this->query("update " . NEWSLETTER_USERS_TABLE . " set list_" . ((int) $controls->button_data) . "=0");
        $controls->add_toast_done();
    }

    if ($controls->is_action('link')) {
        $this->query("update " . NEWSLETTER_USERS_TABLE . " set list_" . ((int) $controls->button_data) . "=1");
        $controls->add_toast_done();
    }
}

// Conditions for the count query
$conditions = [];
for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
    $conditions[] = "count(case list_$i when 1 then 1 else null end) list_$i";
}

$main_options = $this->get_options('lists', '');

$status = [0 => __('Private', 'newsletter'), 1 => __('Public', 'newsletter')];

$count = $this->get_row("select " . implode(',', $conditions) . ' from ' . NEWSLETTER_USERS_TABLE);

$panels = (int) (NEWSLETTER_LIST_MAX / 10) + (NEWSLETTER_LIST_MAX % 10 > 0 ? 1 : 0);
?>
<script>
    jQuery(function () {
        jQuery(".tnp-notes").tooltip({
            content: function () {
                return this.title;
            }
        });
    });
</script>
<div class="wrap tnp-lists" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscription/newsletter-lists/') ?>
        <h2><?php esc_html_e('Lists', 'newsletter') ?></h2>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <p>
            Configure the lists visibility on the <a href="?page=newsletter_subscription_form" target="_blank">Subscription form</a> and <a href="?page=newsletter_profile_index" target="_blank">Profile page</a>.
        </p>
        <p>
            List wide operations on subscribers (delete, move, add, ...) can be performed on the <a href="?page=newsletter_users_massive" target="_blank">Subscribers Maintenance page</a>.
        </p>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div id="tabs">
                <ul>
                    <?php for ($i = 0; $i < $panels; $i++) { ?>
                        <li><a href="#tabs-general-<?php echo $i ?>"><?php esc_html_e('Lists', 'newsletter') ?> <?php echo $i * 10 + 1, '-', $i * 10 + 10 ?></a></li>
                    <?php } ?>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <li><a href="#tabs-debug">Debug</a></li>
                    <?php } ?>
                </ul>

                <?php for ($j = 0; $j < $panels; $j++) { ?>
                    <div id="tabs-general-<?php echo $j ?>">

                        <?php $this->language_notice() ?>

                        <table class="widefat" style="width: auto; max-width: 800px" scope="presentation">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php esc_html_e('Name', 'newsletter') ?></th>
                                    <?php if (!$language) { ?>
                                        <th><?php _e('Type', 'newsletter') ?></th>
                                        <th style="white-space: nowrap"><?php esc_html_e('Enforced', 'newsletter') ?> <i class="fas fa-info-circle tnp-notes" title="<?php esc_attr_e('If you check this box, all your new subscribers will be automatically added to this list', 'newsletter') ?>"></i></th>
                                        <?php if ($is_multilanguage) { ?>
                                            <th><?php esc_html_e('Enforced by language', 'newsletter') ?></th>
                                        <?php } ?>
                                    <?php } ?>
                                    <th><?php esc_html_e('Subscribers', 'newsletter') ?></th>
                                    <th>&nbsp;</th>
                                </tr>
                            </thead>

                            <?php for ($i = $j * 10 + 1; $i <= min(($j + 1) * 10, NEWSLETTER_LIST_MAX); $i++) { ?>
                                <?php
                                if ($language && empty($main_options['list_' . $i])) {
                                    continue;
                                }
                                ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td>
                                        <?php $placeholder = !$language ? '' : $main_options['list_' . $i] ?>
                                        <?php $controls->text('list_' . $i, 40, $placeholder); ?>
                                    </td>
                                    <?php if (!$language) { ?>
                                        <td><?php $controls->select('list_' . $i . '_status', $status); ?></td>
                                        <td style="text-align: center">
                                            <?php $controls->checkbox('list_' . $i . '_forced'); ?>
                                        </td>
                                        <?php if ($is_multilanguage) { ?>
                                            <td><?php $controls->languages('list_' . $i . '_languages'); ?></td>
                                        <?php } ?>
                                    <?php } ?>

                                    <td>
                                        <?php //echo $wpdb->get_var("select count(*) from " . NEWSLETTER_USERS_TABLE . " where list_" . $i . "=1 and status='C'");   ?>
                                        <?php
                                        $field = 'list_' . $i;
                                        echo $count->$field;
                                        ?>
                                    </td>

                                    <td style="white-space: nowrap">
                                        <?php if (!$language) { ?>
                                            <?php $controls->button_confirm('unlink', __('Unlink everyone', 'newsletter'), '', $i); ?>
                                            <?php $controls->button_confirm('link', __('Add everyone', 'newsletter'), '', $i); ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td colspan="7">
                                        <?php $notes = apply_filters('newsletter_lists_notes', array(), $i); ?>
                                        <?php
                                        $text = '';
                                        foreach ($notes as $note) {
                                            $text .= esc_html($note) . '<br>';
                                        }
                                        if (!empty($text)) {
                                            echo $text;
                                            //echo '<i class="fas fa-info-circle tnp-notes" title="', esc_attr($text), '"></i>';
                                        }
                                        ?>

                                    </td>
                                </tr>
                            <?php } ?>
                        </table>

                    </div>
                <?php } ?>
                <?php if (NEWSLETTER_DEBUG) { ?>
                    <div id="tabs-debug">
                        <pre><?php echo esc_html(json_encode($this->get_db_options('lists', $language), JSON_PRETTY_PRINT)) ?></pre>
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