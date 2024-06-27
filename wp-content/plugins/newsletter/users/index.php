<?php
/* @var $wpdb wpdb */
/* @var $this NewsletterUsersAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

// Move to base zero
if ($controls->is_action()) {
    if ($controls->is_action('reset')) {
        $controls->data = array();
    } else {
        $controls->data['search_page'] = (int) $controls->data['search_page'] - 1;
    }
    $this->save_options($controls->data, 'users_search');
} else {
    $controls->data = $this->get_options('users_search');
    if (empty($controls->data['search_page'])) {
        $controls->data['search_page'] = 0;
    }
}

if ($controls->is_action('resend')) {
    $user = $this->get_user($controls->button_data);
    NewsletterSubscription::instance()->send_message('confirmation', $user, true);
    $controls->messages = __('Activation email sent.', 'newsletter');
}

if ($controls->is_action('resend_welcome')) {
    $user = $this->get_user($controls->button_data);
    NewsletterSubscription::instance()->send_message('confirmed', $user, true);
    $controls->messages = __('Welcome email sent.', 'newsletter');
}

if ($controls->is_action('delete')) {
    $this->delete_user($controls->button_data);
    unset($controls->data['subscriber_id']);
}

if ($controls->is_action('delete_selected')) {
    $r = Newsletter::instance()->delete_user($_POST['ids']);
    $controls->messages .= $r . ' user(s) deleted';
}

// We build the query condition
$where = 'where 1=1';
$query_args = array();
$text = trim($controls->get_value('search_text', ''));
if ($text) {
    $query_args[] = '%' . $text . '%';
    $query_args[] = '%' . $text . '%';
    $query_args[] = '%' . $text . '%';
    $query_args[] = '%' . $text . '%';
    $where .= " and (id like %s or email like %s or name like %s or surname like %s)";
}

if (!empty($controls->data['search_status'])) {
    if ($controls->data['search_status'] == 'T') {
        $where .= " and test=1";
    } else {
        $query_args[] = $controls->data['search_status'];
        $where .= " and status=%s";
    }
}

if (!empty($controls->data['search_list'])) {
    $where .= " and list_" . ((int) $controls->data['search_list']) . "=1";
}

$filtered = $where != 'where 1=1';

// Total items, total pages
$items_per_page = 20;
if (!empty($query_args)) {
    $where = $wpdb->prepare($where, $query_args);
}
$count = Newsletter::instance()->store->get_count(NEWSLETTER_USERS_TABLE, $where);
$last_page = floor($count / $items_per_page) - ($count % $items_per_page == 0 ? 1 : 0);
if ($last_page < 0) {
    $last_page = 0;
}

if ($controls->is_action('last')) {
    $controls->data['search_page'] = $last_page;
}
if ($controls->is_action('first')) {
    $controls->data['search_page'] = 0;
}
if ($controls->is_action('next')) {
    $controls->data['search_page'] = (int) $controls->data['search_page'] + 1;
}
if ($controls->is_action('prev')) {
    $controls->data['search_page'] = (int) $controls->data['search_page'] - 1;
}
if ($controls->is_action('search')) {
    $controls->data['search_page'] = 0;
}

// Eventually fix the page
if (!isset($controls->data['search_page']) || $controls->data['search_page'] < 0)
    $controls->data['search_page'] = 0;
if ($controls->data['search_page'] > $last_page)
    $controls->data['search_page'] = $last_page;

$query = "select *, unix_timestamp(created) created_at from " . NEWSLETTER_USERS_TABLE . ' ' . $where . " order by id desc";
$query .= " limit " . ($controls->data['search_page'] * $items_per_page) . "," . $items_per_page;
$list = $wpdb->get_results($query);

// Move to base 1
$controls->data['search_page']++;

$lists = $this->get_lists();

$utc = new DateTimeZone('UTC');
?>

<style>
<?php include __DIR__ . '/css/users.css' ?>
</style>

<div class="wrap tnp-users tnp-users-index" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">

        <?php $controls->title_help('/subscribers-and-management/') ?>
        <h2><?php esc_html_e('Subscribers', 'newsletter') ?></h2>
        <?php include __DIR__ . '/nav.php' ?>

    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <form id="channel" method="post" action="">
            <?php $controls->init(); ?>

            <div class="tnp-users-search">
                <?php $controls->text('search_text', 45, __('Search by ID, email, name', 'newsletter')); ?>

                <?php
                $controls->select('search_status', ['' => __('Any status', 'newsletter'), 'T' => __('Test subscribers', 'newsletter'), 'C' => TNP_User::get_status_label('C'),
                    'S' => TNP_User::get_status_label('S'), 'U' => TNP_User::get_status_label('U'), 'B' => TNP_User::get_status_label('B'), 'P' => TNP_User::get_status_label('P')]);
                ?>
                <?php $controls->lists_select('search_list', __('Any list', 'newsletter')); ?>

                <?php $controls->button('search', __('Search', 'newsletter')); ?>
                <?php if ($where != "where 1=1") { ?>
                    <?php $controls->btn('reset', __('Reset Filters', 'newsletter'), ['tertiary' => true]); ?>
                <?php } ?>
            </div>

            <div class="tnp-paginator">

                <?php $controls->btn('first', '«', ['tertiary' => true]); ?>
                <?php $controls->btn('prev', '‹', ['tertiary' => true]); ?>
                <?php $controls->text('search_page', 3); ?> of <?php echo $last_page + 1 ?> <?php $controls->btn('go', __('Go', 'newsletter'), ['secondary' => true]); ?>
                <?php $controls->btn('next', '›', ['tertiary' => true]); ?>
                <?php $controls->btn('last', '»', ['tertiary' => true]); ?>

                <?php echo $count ?> <?php esc_html_e('subscriber(s) found', 'newsletter') ?>

                <?php $controls->btn_link('?page=newsletter_users_new', __('Add new', 'newsletter')); ?>
                <?php $controls->btn('delete_selected', __('Delete selected', 'newsletter'), ['tertiary' => true]); ?>


            </div>

            <table class="widefat">
                <thead>
                    <tr>
                        <th class="check-column"><input type="checkbox" onchange="jQuery('input.tnp-selector').prop('checked', this.checked)"></th>
                        <th>Id</th>
                        <th><?php esc_html_e('Email', 'newsletter') ?></th>
                        <th><?php esc_html_e('Name', 'newsletter') ?></th>
                        <th><?php esc_html_e('Status', 'newsletter') ?></th>
                        <th><?php esc_html_e('Date') ?></th>
                        <th style="white-space: nowrap"><?php $controls->checkbox('show_lists', __('Lists', 'newsletter'), ['onchange' => 'this.form.act.value=\'go\'; this.form.submit()']) ?></th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <?php $i = 0; ?>
                <?php foreach ($list as $s) { ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input class="tnp-selector" type="checkbox" name="ids[]" value="<?php echo (int) $s->id; ?>">
                        </th>
                        <td><?php echo (int) $s->id; ?></td>
                        <td><?php echo esc_html($s->email); ?></td>
                        <td><?php echo esc_html($s->name); ?> <?php echo esc_html($s->surname); ?></td>
                        <td>
                            <?php echo $this->get_user_status_label($s, true); ?>
                        </td>
                        <td>
                            <?php echo $controls->print_date($s->created_at); ?>
                        </td>

                        <td>
                            <?php if (!empty($controls->data['show_lists'])) { ?>
                                <small><?php
                                    foreach ($lists as $item) {
                                        $l = 'list_' . $item->id;
                                        if ($s->$l == 1)
                                            echo esc_html($item->name) . '<br>';
                                    }
                                    ?></small>
                            <?php } ?>
                        </td>

                        <td>
                            <?php $controls->button_icon_edit($this->get_admin_page_url('edit') . '&amp;id=' . ((int) $s->id)) ?>
                        </td>
                        <td style="white-space: nowrap">

                            <?php if ($s->status == "C") { ?>
                                <?php $controls->btn('resend_welcome', '', ['secondary' => true, 'data' => $s->id, 'icon' => 'fa-redo', 'confirm' => true, 'title' => __('Resend welcome', 'newsletter')]); ?>
                            <?php } else { ?>
                                <?php $controls->btn('resend', '', ['secondary' => true, 'data' => $s->id, 'icon' => 'fa-redo', 'confirm' => true, 'title' => __('Resend activation', 'newsletter')]); ?>
                            <?php } ?>

                            <?php $controls->button_icon_delete($s->id); ?>

                        </td>
                    </tr>
                <?php } ?>
            </table>
            <div class="tnp-paginator">

                <?php $controls->btn('first', '«', ['tertiary' => true]); ?>
                <?php $controls->btn('prev', '‹', ['tertiary' => true]); ?>
                <?php $controls->btn('next', '›', ['tertiary' => true]); ?>
                <?php $controls->btn('last', '»', ['tertiary' => true]); ?>
            </div>
        </form>
    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
