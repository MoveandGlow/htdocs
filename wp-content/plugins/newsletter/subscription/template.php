<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

if (!$controls->is_action()) {
    $controls->data = $this->get_options('template', $language);
} else {
    if ($controls->is_action('save')) {
        $this->save_options($controls->data, 'template', $language);
        $controls->add_message_saved();
    }

    if ($controls->is_action('reset')) {
        // TODO: Reset by language?
        $this->reset_options('template');
        $controls->data = $this->get_options('template', $language);
        $controls->add_message_done();
    }

    if ($controls->is_action('test')) {

        $users = $this->get_test_users();
        if (count($users) == 0) {
            $controls->errors = __('No test subscribers found.', 'newsletter') . ' <a href="https://www.thenewsletterplugin.com/plugins/newsletter/subscribers-module#test" target="_blank"><i class="fas fa-info-circle"></i></a>';
        } else {
            $template = $controls->data['template'];

            $message = '<p>This is a generic example of message embedded inside the template.</p>';
            $message .= '<p>Subscriber data can be referenced using tags. See the <a href="https://www.thenewsletterplugin.com/documentation">plugin documentation</a>.</p>';
            $message .= '<p>First name: {name}</p>';
            $message .= '<p>Last name: {surname}</p>';
            $message .= '<p>Email: {email}</p>';
            $message .= '<p>Here an image as well. Make them styled with the CSS rule "max-width: 100%"</p>';
            $message .= '<p><img src="' . plugins_url('newsletter') . '/images/test.jpg" style="max-width: 100%"></p>';

            $message = str_replace('{message}', $message, $template);
            $addresses = array();
            foreach ($users as $user) {
                $addresses[] = $user->email;
                Newsletter::instance()->mail($user->email, 'Newsletter Messages Template Test', Newsletter::instance()->replace_for_email($message, $user));
            }
            $controls->messages .= 'Test emails sent to ' . count($users) . ' test subscribers: ' .
                    implode(', ', $addresses) . '.' . ' <a href="https://www.thenewsletterplugin.com/plugins/newsletter/subscribers-module#test" target="_blank"><i class="fas fa-info-circle"></i></a>';
        }
    }
}

if (strpos($controls->data['template'], '{message}') === false) {
    $controls->errors = __('The tag {message} is missing in your template', 'newsletter');
}
?>

<?php include NEWSLETTER_INCLUDES_DIR . '/codemirror.php'; ?>
<style>
    .CodeMirror {
        height: 100%;
    }
</style>
<script>
    jQuery(function () {
        templateEditor = CodeMirror.fromTextArea(document.getElementById("options-template"), {
            lineNumbers: true,
            mode: 'htmlmixed',
            extraKeys: {"Ctrl-Space": "autocomplete"}
        });
    });
</script>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-heading">

        <h2><?php _e('Messages template', 'newsletter') ?></h2>
        <p>
            Edit the default template of confirmation, welcome and cancellation emails. Add the {message} tag where you
            want the specific message text to be included.
        </p>

    </div>

    <div id="tnp-body">

         <?php $controls->show(); ?>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <?php $controls->textarea_preview('template', '100%', '700px'); ?>
            <br><br>


            <p>
                <?php $controls->button_save(); ?>
                <?php $controls->button_reset(); ?>
                <?php $controls->button('test', 'Send a test'); ?>
            </p>
        </form>
    </div>

    <?php include NEWSLETTER_ADMIN_FOOTER; ?>

</div>
