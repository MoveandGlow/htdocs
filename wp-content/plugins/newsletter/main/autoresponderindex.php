<?php

defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

$autoresponders = [];

$autoresponder = new stdClass();
$autoresponder->id = 1;
$autoresponder->name = 'Welcome email series';
$autoresponder->list = 0;
$autoresponder->status = 1;
$autoresponder->subscribers = 346;
$autoresponder->emails = [1, 2, 3];
$autoresponder->list_name = 'Not linked to a list';
$autoresponders[] = $autoresponder;

$autoresponder = new stdClass();
$autoresponder->id = 2;
$autoresponder->name = 'Yoga lessons';
$autoresponder->list = 0;
$autoresponder->status = 1;
$autoresponder->subscribers = 3454;
$autoresponder->emails = [1, 2, 3, 6, 7, 8, 9, 10];
$autoresponder->list_name = 'Yoga news';
$autoresponders[] = $autoresponder;

?>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>
    <div id="tnp-heading">
        <?php $controls->title_help('/addons/extended-features/autoresponder-extension/') ?>
        <h2>Email series</h2>
    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <p>This is only a demonstrative panel.</p>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div class="tnp-buttons">
                <?php $controls->button('add', 'Add new series') ?>
            </div>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Name</th>
                        <th>List</th>
                        <th>Status</th>
                        <th>Steps</th>
                        <th>Subscribers</th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($autoresponders as $autoresponder) { ?>
                        <tr>
                            <td><?php echo esc_html($autoresponder->id) ?></td>
                            <td><?php echo esc_html($autoresponder->name) ?></td>
                            <td><?php echo esc_html($autoresponder->list_name) ?></td>
                            <td>
                                <span class="tnp-led-<?php echo!empty($autoresponder->status) ? 'green' : 'gray' ?>">&#x2B24;</span>
                            </td>
                            <td><?php echo count($autoresponder->emails) ?></td>
                            <td><?php echo $autoresponder->subscribers ?></td>

                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_configure('?page=newsletter_main_autoresponderedit') ?>
                                <?php $controls->button_icon_statistics('?page=newsletter_main_autoresponderstatistics') ?>
                                <?php $controls->button_icon_subscribers('?page=newsletter_main_autoresponderusers') ?>
                            </td>
                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_copy($autoresponder->id); ?>
                                <?php $controls->button_icon_delete($autoresponder->id); ?>
                            </td>
                        </tr>
                    <?php } ?>

                </tbody>
            </table>

        </form>

    </div>
</div>
