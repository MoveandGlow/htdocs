<?php
/* @var $this NewsletterMainAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;
$extensions = Newsletter::instance()->getTnpExtensions();

if ($controls->is_action('activate')) {
    $result = activate_plugin('newsletter-extensions/extensions.php');
    if (is_wp_error($result)) {
        $controls->errors .= __('Error while activating:', 'newsletter') . " " . $result->get_error_message();
    } else {
        wp_clean_plugins_cache(false);
        delete_transient('tnp_extensions_json');
        $controls->js_redirect('admin.php?page=newsletter_extensions_index');
    }
}

function tnp_extensions_table($extensions, $category) {
    ?>

    <table class="widefat tnp-extensions">
        <?php foreach ($extensions as $e) { ?>
            <?php if (strpos($e->category, $category) === false) continue; ?>
            <tr id="<?php echo esc_attr($e->slug) ?>">
                <td width="1%">
                    <?php if ($e->url) { ?>
                        <a href="<?php echo esc_attr($e->url) ?>" target="_blank">
                        <?php } ?>
                        <img src="<?php echo esc_attr($e->image) ?>" alt="<?php echo esc_attr($e->title) ?>">
                        <?php if ($e->url) { ?>
                        </a>
                    <?php } ?>
                </td>
                <td width="79%">
                    <?php if ($e->url) { ?>
                        <a href="<?php echo esc_attr($e->url) ?>" target="_blank" style="color: #444">
                        <?php } ?>
                        <strong><?php echo esc_html($e->title) ?></strong>
                        <?php if ($e->free) { ?>
                            <span class="tnp-free"><?php esc_html_e('Free', 'newsletter') ?></span>
                        <?php } ?>

                        <div style="font-size:.9em">
                            <?php echo esc_html($e->description) ?>
                        </div>
                        <?php if ($e->url) { ?>
                        </a>
                    <?php } ?>
                </td>
                <td width="20%">
                    <?php if ($e->free) { ?>
                        <a href="#tnp-body" class="tnp-action tnp-install">
                            <i class="fas fa-download" aria-hidden="true"></i> <?php esc_html_e('Free', 'newsletter') ?>
                        </a>
                    <?php } else { ?>
                        <a href="https://www.thenewsletterplugin.com/premium?utm_source=manager&utm_medium=<?php echo esc_attr(urlencode($e->slug)) ?>&utm_campaign=plugin" class="tnp-action tnp-buy" target="_blank">
                            <i class="fas fa-shopping-cart" aria-hidden="true"></i> Buy Now
                        </a>
                    <?php } ?>


                </td>
            </tr>
        <?php } ?>
    </table>

    <?php
}
?>

<style>
<?php include __DIR__ . '/css/extensions.css' ?>
</style>

<div class="wrap tnp-main tnp-main-extensions" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-body">

        <?php $controls->show() ?>

        <?php if (is_wp_error(validate_plugin('newsletter-extensions/extensions.php'))) { ?>
            <div id="tnp-promo">

                <h1>Supercharge Newsletter with our Professional Addons</h1>
                <div class="tnp-promo-how-to">
                    <h3>How to install:</h3>
                    <p>To add our addons, free or professional, you need to install our Addons Manager. But don't worry, it's super easy! Just click on "Download" button to download the zip file of
                        the Addon Manager from our website, then click on "Install" to upload the same zip file to your WordPress installation.</p>
                </div>
                <div class="tnp-promo-buttons">
                    <a class="tnp-promo-button" href="https://www.thenewsletterplugin.com/get-addons-manager"><i class="fas fa-cloud-download-alt"></i> Download Addons Manager</a>
                    <a class="tnp-promo-button" href="<?php echo esc_attr(admin_url('plugin-install.php?tab=upload')) ?>"><i class="fas fa-cloud-upload-alt"></i> Install</a>
                </div>

            </div>
        <?php } else if (is_plugin_inactive('newsletter-extensions/extensions.php')) { ?>
            <div id="tnp-promo">
                <div class="tnp-promo-how-to">
                    <p>Addons Manager seems installed but not active.</p>
                    <p>Activate it to install and update our free and professional addons.</p>
                </div>
                <div class="tnp-promo-buttons">
                    <a class="tnp-promo-button" href="<?php echo wp_nonce_url(admin_url('admin.php') . '?page=newsletter_main_extensions&act=activate', 'save'); ?>"><i class="fas fa-power-off"></i> Activate</a>
                </div>
            </div>
        <?php } ?>


        <?php if ($extensions) { ?>

            <h3 id="tnp-subscription">Collecting subscribers</h3>
            <?php tnp_extensions_table($extensions, 'subscription') ?>

            <h3 id="tnp-creating">Creating newsletters</h3>
            <?php tnp_extensions_table($extensions, 'newsletters') ?>

            <h3 id="tnp-automation">Automating your work</h3>
            <?php tnp_extensions_table($extensions, 'automation') ?>

            <h3 id="tnp-reporting">Analytics</h3>
            <?php tnp_extensions_table($extensions, 'statistics') ?>

            <h3 id="tnp-delivery">Delivery</h3>
            <p>
                High speed sending of your newsletter with professional delivery services. Automatic bounces and complaints management.
            </p>
            <?php tnp_extensions_table($extensions, 'delivery') ?>

            <h3>Tools</h3>
            <?php tnp_extensions_table($extensions, 'tools') ?>

        <?php } else { ?>

            <p style="color: white;">No addons available. Could be a connection problem, try later.</p>

        <?php } ?>

    </div>

    <?php include NEWSLETTER_DIR . '/tnp-footer.php'; ?>

</div>
