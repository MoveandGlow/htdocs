<?php
/* @var $this NewsletterEmails */
/* @var $controls NewsletterControls */
defined('ABSPATH') || exit;

function tnp_prepare_controls($email, $controls) {
    $controls->data = $email;

    foreach ($email['options'] as $name => $value) {
        $controls->data['options_' . $name] = $value;
    }
}

// Always required
$email = $this->get_email($_GET['id'], ARRAY_A);

if (empty($email)) {
    echo 'Newsletter not found';
    return;
}

$email_id = $email['id'];

/* Satus changes which require a reload */
if ($controls->is_action('pause')) {
    $this->logger->info('Newsletter ' . $email_id . ' paused');
    $wpdb->update(NEWSLETTER_EMAILS_TABLE, array('status' => 'paused'), array('id' => $email_id));
    $email = $this->get_email($_GET['id'], ARRAY_A);
    tnp_prepare_controls($email, $controls);
}

if ($controls->is_action('continue')) {
    $this->logger->info('Newsletter ' . $email_id . ' restarted');
    $wpdb->update(NEWSLETTER_EMAILS_TABLE, array('status' => 'sending'), array('id' => $email_id));
    $email = $this->get_email($_GET['id'], ARRAY_A);
    tnp_prepare_controls($email, $controls);
}

if ($controls->is_action('abort')) {
    $this->logger->info('Newsletter ' . $email_id . ' aborted');
    $wpdb->query("update " . NEWSLETTER_EMAILS_TABLE . " set last_id=0, sent=0, status='new' where id=" . $email_id);
    $email = $this->get_email($_GET['id'], ARRAY_A);
    tnp_prepare_controls($email, $controls);
    $controls->messages = __('Delivery definitively cancelled', 'newsletter');
}

if ($controls->is_action('change-private')) {
    $data = [];
    $data['private'] = $controls->data['private'];
    $data['id'] = $email['id'];
    $email = $this->save_email($data, ARRAY_A);
    $controls->add_toast_saved();

    tnp_prepare_controls($email, $controls);
}


$editor_type = $this->get_editor_type($email);

// Backward compatibility: preferences conversion
if (!$controls->is_action()) {
    if (!isset($email['options']['lists'])) {

        $options_profile = get_option('newsletter_profile');

        if (empty($controls->data['preferences_status_operator'])) {
            $email['options']['lists_operator'] = 'or';
        } else {
            $email['options']['lists_operator'] = 'and';
        }
        $controls->data['options_lists'] = [];
        $controls->data['options_lists_exclude'] = [];

        if (!empty($email['preferences'])) {
            $preferences = explode(',', $email['preferences']);
            $value = empty($email['options']['preferences_status']) ? 'on' : 'off';

            foreach ($preferences as $x) {
                if ($value == 'on') {
                    $controls->data['options_lists'][] = $x;
                } else {
                    $controls->data['options_lists_exclude'][] = $x;
                }
            }
        }
    }
}
// End backward compatibility

if (!$controls->is_action()) {
    tnp_prepare_controls($email, $controls);
}

if ($controls->is_action('html')) {

    $this->logger->info('Newsletter ' . $email_id . ' converted to HTML');

    $data = [];
    $data['editor'] = NewsletterEmails::EDITOR_HTML;
    $data['id'] = $email_id;

    // Backward compatibility: clean up the composer flag
    $data['options'] = $email['options'];
    unset($data['options']['composer']);
    // End backward compatibility

    $data['message'] = preg_replace('/data-json=".*?"/is', '', $email['message']);
    $data['message'] = str_replace('</table>', "</table>\n", $data['message']);
    $data['message'] = str_replace('</td></tr>', "</td>\n</tr>", $data['message']);
    $data['message'] = str_replace('</td></tr>', "</td>\n</tr>", $data['message']);
    $data['message'] = str_replace('</tr></tbody>', "</tr>\n</tbody>", $data['message']);
    $data['message'] = str_replace('</tbody></table>', "</tbody>\n</table>", $data['message']);
    $data['message'] = str_replace('<tbody><tr>', "<tbody>\n<tr>", $data['message']);
    $data['message'] = str_replace('<tr><td ', "<tr>\n<td ", $data['message']);

    $email = $this->save_email($data, ARRAY_A);
    $controls->messages = 'You can now edit the newsletter as pure HTML';

    tnp_prepare_controls($email, $controls);

    $editor_type = NewsletterEmails::EDITOR_HTML;
}



if ($controls->is_action('test') || $controls->is_action('save') || $controls->is_action('send') || $controls->is_action('schedule')) {

    if ($email['updated'] != $controls->data['updated']) {
        $controls->errors = 'This newsletter has been modified by someone else. Cannot save.';
    } else {
        $email['updated'] = time();
        if ($controls->is_action('save')) {
            $this->logger->info('Saving newsletter: ' . $email_id);
        } else if ($controls->is_action('send')) {
            $this->logger->info('Sending newsletter: ' . $email_id);
        } else if ($controls->is_action('schedule')) {
            $this->logger->info('Scheduling newsletter: ' . $email_id);
        }

        //$email['subject'] = $controls->data['subject'];
        $email['track'] = $controls->data['track'];
        $email['editor'] = $editor_type;
        $email['private'] = $controls->data['private'];
        $email['message_text'] = $controls->data['message_text'];
        if ($controls->is_action('send') || $controls->is_action('save')) {
            $email['send_on'] = time();
        } else {
            // Patch, empty on continuation
            if (!empty($controls->data['send_on'])) {
                $email['send_on'] = $controls->data['send_on'];
            }
        }

        // Reset and refill the options
        // Try without the reset and let's see where the problems are
        //$email['options'] = array();
        // Reset only specific keys
        unset($email['options']['lists']);
        unset($email['options']['lists_operator']);
        unset($email['options']['lists_exclude']);
        unset($email['options']['sex']);
        for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
            unset($email['options']["profile_$i"]);
        }

        // Patch for Geo addon to be solved with a filter
        unset($email['options']['countries']);
        unset($email['options']['regions']);
        unset($email['options']['cities']);

        foreach ($controls->data as $name => $value) {
            if (strpos($name, 'options_') === 0) {
                $email['options'][substr($name, 8)] = $value;
            }
        }

        // Before send, we build the query to extract subscriber, so the delivery engine does not
        // have to worry about the email parameters
        if ($email['options']['status'] == 'S') {
            $query = "select * from " . NEWSLETTER_USERS_TABLE . " where status='S'";
        } else {
            $query = "select * from " . NEWSLETTER_USERS_TABLE . " where status='C'";
        }

        if ($email['options']['wp_users'] == '1') {
            $query .= " and wp_user_id<>0";
        }

        if (!empty($email['options']['language'])) {
            $query .= " and language='" . esc_sql((string) $email['options']['language']) . "'";
        }


        $list_where = array();
        if (isset($email['options']['lists']) && count($email['options']['lists'])) {
            foreach ($email['options']['lists'] as $list) {
                $list = (int) $list;
                $list_where[] = 'list_' . $list . '=1';
            }
        }

        if (!empty($list_where)) {
            if (isset($email['options']['lists_operator']) && $email['options']['lists_operator'] == 'and') {
                $query .= ' and (' . implode(' and ', $list_where) . ')';
            } else {
                $query .= ' and (' . implode(' or ', $list_where) . ')';
            }
        }

        // Excluded lists
        $list_where = array();
        if (isset($email['options']['lists_exclude']) && count($email['options']['lists_exclude'])) {
            foreach ($email['options']['lists_exclude'] as $list) {
                $list = (int) $list;
                $list_where[] = 'list_' . $list . '=0';
            }
        }
        if (!empty($list_where)) {
            // Must not be in one of the excluded lists
            $query .= ' and (' . implode(' and ', $list_where) . ')';
        }

        // Gender
        if (isset($email['options']['sex'])) {
            $sex = $email['options']['sex'];
            if (is_array($sex) && count($sex)) {
                $query .= " and sex in (";
                foreach ($sex as $x) {
                    $query .= "'" . esc_sql((string) $x) . "', ";
                }
                $query = substr($query, 0, -2);
                $query .= ")";
            }
        }

        // Profile fields filter
        $profile_clause = array();
        for ($i = 1; $i <= 20; $i++) {
            if (isset($email["options"]["profile_$i"]) && count($email["options"]["profile_$i"])) {
                $profile_clause[] = 'profile_' . $i . " IN ('" . implode("','", esc_sql($email["options"]["profile_$i"])) . "') ";
            }
        }

        if (!empty($profile_clause)) {
            $query .= ' and (' . implode(' and ', $profile_clause) . ')';
        }

        // Temporary save to have an object and call the query filter
        $e = Newsletter::instance()->save_email($email);
        $query = apply_filters('newsletter_emails_email_query', $query, $e);

        $email['query'] = $query;
        if ($email['status'] == 'sent') {
            $email['total'] = $email['sent'];
        } else {
            $email['total'] = $wpdb->get_var(str_replace('*', 'count(*)', $query));
        }

        if ($controls->is_action('send') && $controls->data['send_on'] < time()) {
            $controls->data['send_on'] = time();
        }

        $email = Newsletter::instance()->save_email($email, ARRAY_A);

        if ($email === false) {
            $controls->errors = 'Unable to save. Try to deactivate and reactivate the plugin may be the database is out of sync.';
        }

        tnp_prepare_controls($email, $controls);

        $controls->add_toast_saved();
    }
}

if (empty($controls->errors) && ($controls->is_action('send') || $controls->is_action('schedule'))) {

    if (empty($email['subject'])) {
        $controls->errors = __('A subject is required to send', 'newsletter');
    } else {
        NewsletterStatistics::instance()->reset_stats($email);
        $wpdb->update(NEWSLETTER_EMAILS_TABLE, array('status' => TNP_Email::STATUS_SENDING), array('id' => $email_id));
        $email['status'] = TNP_Email::STATUS_SENDING;
        if ($controls->is_action('send')) {
            $controls->messages = __('Now sending.', 'newsletter');
        } else {
            $controls->messages = __('Scheduled.', 'newsletter');
        }

        // Immadiate first batch sending since people has no patience
        if ($controls->is_action('send') && $email['total'] < 20) {
            // Avoid the first batch if there are other newsletters delivering otherwise we can get over the per hour quota
            $sending_count = $wpdb->get_results("select count(*) from " . NEWSLETTER_EMAILS_TABLE . " where status='sending' and send_on<" . time());
            if ($sending_count <= 1) { // This newsletter is counted as well
                Newsletter::instance()->hook_newsletter();
            }
        }

        NewsletterMainAdmin::instance()->set_completed_step('first-newsletter');
    }
}

if (isset($email['options']['status']) && $email['options']['status'] == 'S') {
    $controls->warnings[] = __('This newsletter will be sent to not confirmed subscribers.', 'newsletter');
}

if (strpos($email['message'], '{profile_url}') === false && strpos($email['message'], '{unsubscription_url}') === false && strpos($email['message'], '{unsubscription_confirm_url}') === false) {
    $controls->warnings[] = __('The message is missing the subscriber profile or cancellation link.', 'newsletter');
}

if (TNP_Email::STATUS_ERROR === $email['status'] && isset($email['options']['error_message'])) {
    $controls->errors .= sprintf(__('Stopped by fatal error: %s', 'newsletter'), esc_html($email['options']['error_message']));
}


if ($email['status'] != 'sent') {
    $subscriber_count = $wpdb->get_var(str_replace('*', 'count(*)', $email['query']));
} else {
    $subscriber_count = $email['sent'];
}
?>
<style>
<?php readfile(__DIR__ . '/assets/edit.css') ?>
</style>

<div class="wrap tnp-emails tnp-emails-edit" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/newsletter-targeting'); ?>

        <h2><?php echo esc_html($email['subject']); ?></h2>

    </div>

    <div id="tnp-body">
        <?php $controls->show() ?>

        <form method="post" action="" id="newsletter-form">
            <?php $controls->init(['cookie_name' => 'newsletter_emails_edit_tab']); ?>
            <?php $controls->hidden('updated'); ?>

            <div class="tnp-emails-header">
                <div class="tnp-submit">

                    <?php if ($email['status'] == 'sending' || $email['status'] == 'sent') { ?>
                        <?php if ($email['status'] == 'message') { ?>
                            <?php $controls->button_back('?page=newsletter_emails_index') ?>
                        <?php } ?>
                    <?php } else { ?>

                        <a class="button-secondary" href="<?php echo $this->get_editor_url($email_id, $editor_type) ?>">
                            <i class="fas fa-edit"></i> <?php esc_html_e('Edit', 'newsletter') ?>
                        </a>

                    <?php } ?>

                    <?php if ($email['status'] != 'sending' && $email['status'] != 'sent') $controls->button_save(); ?>
                    <?php if ($email['status'] == 'new') $controls->button_confirm('send', __('Send now', 'newsletter'), __('Start real delivery?', 'newsletter')); ?>
                    <?php if ($email['status'] == 'sending') $controls->button_confirm('pause', __('Pause', 'newsletter'), __('Pause the delivery?', 'newsletter')); ?>
                    <?php if ($email['status'] == 'paused' || $email['status'] == 'error') $controls->button_confirm('continue', __('Continue', 'newsletter'), 'Continue the delivery?'); ?>
                    <?php if ($email['status'] == 'paused') $controls->button_confirm('abort', __('Stop', 'newsletter'), __('This totally stop the delivery, ok?', 'newsletter')); ?>
                    <?php if ($email['status'] == 'new' || ( $email['status'] == 'paused' && $email['send_on'] > time() )) { ?>
                        <a id="tnp-schedule-button" class="button-secondary" href="javascript:tnp_toggle_schedule()"><i class="far fa-clock"></i> <?php _e("Schedule") ?></a>
                        <span id="tnp-schedule" style="display: none;">
                            <?php $controls->datetime('send_on') ?>
                            <?php $controls->button_confirm('schedule', __('Schedule', 'newsletter'), __('Schedule delivery?', 'newsletter')); ?>
                            <a class="button-secondary tnp-button-cancel" href="javascript:tnp_toggle_schedule()"><?php _e("Cancel") ?></a>
                        </span>
                    <?php } ?>

                    <?php $controls->button_icon_view(home_url('/') . '?na=view&id=' . $email_id) ?>
                </div>

                <div class="tnp-emails-status">

                    <div style="display: flex; justify-content: space-between">
                        <div style="flex-grow: 1">
                            <?php $this->show_email_status_label($email) ?>
                        </div>

                        <div style="flex-grow: 1">
                            <?php
                            if ($email['status'] == 'sending' && $email['send_on'] > time() || $email['status'] == 'sent' || $email['status'] == 'error') {
                                echo $this->format_date($email['send_on']);
                            } else {
                                $this->show_email_progress_bar($email);
                            }
                            ?>

                        </div>

                        <div style="flex-grow: 1; text-align: right">
                            <?php if ($email['status'] == 'new') { ?>
                                <i class="fas fa-users"></i> <?php echo $subscriber_count ?>
                            <?php } else { ?>
                                <i class="fas fa-users"></i> <?php $this->show_email_progress_numbers($email) ?>
                            <?php } ?>
                        </div>

                    </div>

                </div>
            </div>


            <div id="tabs">

                <ul>
                    <li><a href="#tabs-options"><?php esc_html_e('Targeting', 'newsletter') ?></a></li>
                    <li><a href="#tabs-ga">Google Analytics</a></li>
                    <li class="tnp-tabs-advanced"><a href="#tabs-advanced"><?php esc_html_e('Advanced', 'newsletter') ?></a></li>
                </ul>


                <div id="tabs-options" class="tnp-list-conditions">

                    <p>
                        <?php esc_html_e('Leaving all multichoice options unselected is like to select all them', 'newsletter'); ?>
                    </p>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Lists', 'newsletter') ?></th>
                            <td>
                                <?php
                                $lists = $controls->get_list_options();
                                ?>
                                <?php $controls->select('options_lists_operator', array('or' => __('Match at least one of', 'newsletter'), 'and' => __('Match all of', 'newsletter'))); ?>

                                <?php $controls->select2('options_lists', $lists, null, true, null, __('All', 'newsletter')); ?>

                                <br>
                                <?php esc_html_e('must not in one of', 'newsletter') ?>

                                <?php $controls->select2('options_lists_exclude', $lists, null, true, null, __('None', 'newsletter')); ?>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Language', 'newsletter') ?></th>
                            <td>
                                <?php $controls->language('options_language'); ?>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Gender', 'newsletter') ?></th>
                            <td>
                                <?php $controls->checkboxes_group('options_sex', array('f' => 'Women', 'm' => 'Men', 'n' => 'Not specified')); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Status', 'newsletter') ?></th>
                            <td>
                                <?php $controls->select('options_status', array('C' => __('Confirmed', 'newsletter'), 'S' => __('Not confirmed', 'newsletter'))); ?>

                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Only to subscribers linked to WP users', 'newsletter') ?></th>
                            <td>
                                <?php $controls->yesno('options_wp_users'); ?>
                            </td>
                        </tr>
                        <?php
                        $fields = $this->get_customfields();
                        ?>
                        <?php if (!empty($fields)) { ?>
                            <tr>
                                <th><?php esc_html_e('Profile fields', 'newsletter') ?></th>
                                <td>
                                    <?php foreach ($fields as $profile) { ?>
                                        <?php if ($profile->type !== TNP_Profile::TYPE_SELECT) continue; ?>
                                        <?php echo esc_html($profile->name), ' ', __('is one of:', 'newsletter') ?>
                                        <?php $controls->select2("options_profile_$profile->id", $profile->options, null, true, null, __('Do not filter by this field', 'newsletter')); ?>
                                        <br>
                                    <?php } ?>
                                    <p class="description">

                                    </p>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>

                    <?php do_action('newsletter_emails_edit_target', $this->get_email($email_id), $controls) ?>

                </div>

                <div id="tabs-ga">
                    <?php if (!class_exists('NewsletterAnalytics')) { ?>
                        <p class="tnp-tab-notice">Google Analytics addon required.</p>
                    <?php } ?>
                    <?php if (empty($email['track'])) { ?>
                        <p class="tnp-tab-warning">Tracking must be active to use Google Analytics.</p>
                    <?php } ?>


                    <table class="form-table">
                        <tr valign="top">
                            <th>UTM Source</th>
                            <td>
                                <?php $controls->text('options_utm_source', 50); ?>
                                <p class="description">
                                    Should set as "newsletter-{email_id}" and it's mandatory for Google. "{email_id}" is replaced with the
                                    newsletter unique id. Automated newsletter, autoresponders and other non standard newsletter use a different
                                    source like automated-{channel numer}-{email id}.
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>UTM Campaign</th>
                            <td>
                                <?php $controls->text('options_utm_campaign', 50); ?>
                                <p class="description">
                                    This is the campaign name
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>UTM Medium</th>
                            <td>
                                <?php $controls->text('options_utm_medium', 50); ?>
                                <p class="description">
                                    Should be set to "email" since this is the only medium used.
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>UTM Term</th>
                            <td>
                                <?php $controls->text('options_utm_term', 50); ?>
                                <p class="description">
                                    Usually empty can be used on specific newsletters but it is more related to keyword based advertising.
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>UTM Content</th>
                            <td>
                                <?php $controls->text('options_utm_content', 50); ?>
                                <p class="description">
                                    Usually empty can be used on specific newsletters.
                                </p>
                            </td>
                        </tr>

                    </table>
                </div>

                <div id="tabs-advanced">

                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Keep private', 'newsletter') ?></th>
                            <td>
                                <?php $controls->yesno('private'); ?>
                                <?php if ($email['status'] == 'sent') { ?>
                                    <?php $controls->button('change-private', __('Save')) ?>
                                <?php } ?>
                                <span class="description">
                                    <?php esc_html_e('Hide/show from public sent newsletter list.', 'newsletter') ?>
                                    <?php esc_html_e('Used by', 'newsletter') ?>: <a href="" target="_blank">Archive Addon</a>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Track clicks and message opening', 'newsletter') ?></th>
                            <td>
                                <?php $controls->yesno('track'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Sender email address', 'newsletter') ?></th>
                            <td>
                                <?php $controls->text_email('options_sender_email', 40); ?>
                                <span class="description">
                                    <?php echo esc_html(Newsletter::instance()->get_sender_email()) ?>
                                </span>
                                <p class="description">
                                    If you use a delivery service, be sure to use a validated email address.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('Sender name', 'newsletter') ?>
                            </th>
                            <td>
                                <?php $controls->text('options_sender_name', 40); ?>
                                <span class="description">
                                    <?php echo esc_html(Newsletter::instance()->get_sender_name()) ?>
                                </span>
                            </td>
                        </tr>
                    </table>

                    <?php do_action('newsletter_emails_edit_other', $this->get_email($email_id), $controls) ?>

                    <table class="form-table">

                        <tr>
                            <th style="vertical-align: top">
                                This is the textual version of your newsletter.
                                If you empty it, only an HTML version will be sent but is an anti-spam best practice to include a text only version.
                            </th>
                            <td>
                                <?php if ($editor_type == NewsletterEmails::EDITOR_COMPOSER) { ?>
                                    <?php $controls->select('options_text_message_mode', ['' => __('Autogenerate', 'newsletter'), '1' => __('Hand edited', 'newsletter')]) ?>
                                    <p class="description"></p>
                                <?php } ?>

                                <?php $controls->textarea_fixed('message_text', '100%', '500'); ?>
                                <!--
                                <p class="tnp-tab-warning">
                                    See <a href="https://wordpress.org/plugins/plaintext-newsletter/" target="_blank">this plugin</a> for automatic plaintext generation.
                                </p>
                                -->
                            </td>
                        </tr>
                        <tr>
                            <th>Query (tech)</th>
                            <td><?php echo esc_html($email['query']); ?></td>
                        </tr>
                        <tr>
                            <th>Token (tech)</th>
                            <td><?php echo esc_html($email['token']); ?></td>
                        </tr>

                        <?php if ($editor_type != NewsletterEmails::EDITOR_HTML && $email['status'] != 'sending' && $email['status'] != 'sent') { ?>
                            <tr>
                                <th>Convert to HTML</th>
                                <td>
                                    <?php $controls->button_confirm('html', __('Convert', 'newsletter'), 'No way back!'); ?>
                                </td>
                            </tr>
                        <?php } ?>

                    </table>
                </div>

            </div>

        </form>
    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
