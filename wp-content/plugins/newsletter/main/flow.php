<?php
/* @var $this NewsletterMainAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

$sources = [];

$extensions_url = '?page=newsletter_main_extension';
if (class_exists('NewsletterExtensions')) {
    $extensions_url = '?page=newsletter_extensions_index';
}

$active = class_exists('NewsletterWoocommerce');
$url = $active ? '?page=newsletter_woocommerce_index' : $extensions_url;
$sources[] = ['title' => 'WC Registration', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterWoocommerce');
$url = $active ? '?page=newsletter_woocommerce_index' : $extensions_url;
$sources[] = ['title' => 'WC Checkout', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterCF7');
$url = $active ? '?page=newsletter_cf7_index' : $extensions_url;
$sources[] = ['title' => 'CF7 Forms', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterWPForms');
$url = $active ? '?page=newsletter_wpnlforms_index' : $extensions_url;
$sources[] = ['title' => 'WP Forms', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterNinjaForms');
$url = $active ? '?page=newsletter_ninjaforms_index' : $extensions_url;
$sources[] = ['title' => 'Ninja Forms', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterGravityForms');
$url = $active ? '?page=newsletter_gravityforms_index' : $extensions_url;
$sources[] = ['title' => 'Gravity Forms', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterElementor');
$url = $active ? '?page=newsletter_elementor_index' : $extensions_url;
$sources[] = ['title' => 'Elementor Forms', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterForminator');
$url = $active ? '?page=newsletter_forminator_index' : $extensions_url;
$sources[] = ['title' => 'Forminator Forms', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterFormidable');
$url = $active ? '?page=newsletter_formidable_index' : $extensions_url;
$sources[] = ['title' => 'Formidable Forms', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterFluentForms');
$url = $active ? '?page=newsletter_fluentforms_index' : $extensions_url;
$sources[] = ['title' => 'Fluent Forms', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterApi');
$url = $active ? '?page=newsletter_api_index' : $extensions_url;
$sources[] = ['title' => 'Newsletter API', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterPmpro');
$url = $active ? '?page=newsletter_pmpro_index' : $extensions_url;
$sources[] = ['title' => 'Paid Memb. Pro', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterEdd');
$url = $active ? '?page=newsletter_edd_index' : $extensions_url;
$sources[] = ['title' => 'EDD Checkout', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterComments');
$url = $active ? '?page=newsletter_comments_index' : $extensions_url;
$sources[] = ['title' => 'WP Comments', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterAutomated');
$url = $active ? '?page=newsletter_automated_index' : '?page=newsletter_main_automated';
$automated = ['title' => 'Automated', 'active' => $active, 'url' => $url, 'usable' => true];

$active = class_exists('NewsletterAutoresponder');
$url = $active ? '?page=newsletter_autoresponder_index' : $extensions_url;
$autoresponder = ['title' => 'Autoresponder', 'active' => $active, 'url' => $url, 'usable' => true];

$active = class_exists('NewsletterWpUsers');
$url = $active ? '?page=newsletter_wpusers_index' : $extensions_url;
$sources[] = ['title' => 'WP Signup', 'active' => $active, 'url' => $url, 'usable' => $active];

$active = class_exists('NewsletterReports');
$url = $active ? '?page=newsletter_reports_newsletters' : $extensions_url . '#analytics';
$reports = ['title' => 'Advanced Reports', 'active' => $active, 'url' => $url, 'usable' => true];

$blocks = [];
$active = class_exists('NewsletterBlocks');
$url = $active ? '?page=newsletter_blocks_index' : $extensions_url;
$blocks[] = ['title' => 'Extra Blocks', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterWoocommerce');
$url = $active ? '?page=newsletter_blocks_index' : $extensions_url;
$blocks[] = ['title' => 'WC Products', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterAI');
$blocks[] = ['title' => 'AI', 'active' => $active, 'url' => $extensions_url];

$active = class_exists('NewsletterEdd');
$url = $active ? '?page=newsletter_blocks_index' : $extensions_url;
$blocks[] = ['title' => 'EDD Downloads', 'active' => $active, 'url' => $url];

$active = class_exists('NewsletterEvents');
$url = $active ? '?page=newsletter_blocks_index' : $extensions_url;
$blocks[] = ['title' => 'Events', 'active' => $active, 'url' => $url];
?>

<style>
<?php include __DIR__ . '/css/dashboard.css' ?>

</style>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('https://www.thenewsletterplugin.com/plugins/newsletter/newsletter-configuration') ?>

        <h2><?php esc_html_e('Dashboard', 'newsletter'); ?></h2>
        <?php include __DIR__ . '/dashboard-nav.php' ?>

    </div>

    <div id="tnp-body" class="tnp-main-index">

        <div class="tnp-dashboard">

            <div class="tnp-cards-container">


                <div class="tnp-card">
                    <div class="tnp-flow">

                        <div>
                            <div class="tnp-flow-item title wide">Site visitors</div>
                        </div>

                        <div class="tnp-flow-arrow">▼</div>

                        <div>
                            <div class="tnp-flow-item"><a href="?page=newsletter_subscription_sources" target="_blank">Subscription Forms</a></div>

                            <?php foreach ($sources as $source) { ?>
                                <?php if (!$source['active']) continue; ?>
                                <div class="tnp-flow-item <?php echo $source['active'] ? '' : 'inactive' ?>"><a href="<?php echo esc_attr($source['url']) ?>" target="_blank"><?php echo esc_html($source['title']) ?></a></div>
                            <?php } ?>
                        </div>
                        <a href="javascript:void()" onclick="document.getElementById('tnp-other-sources').style.display = 'flex'; return false" style="display: block; text-decoration: underline">Other subscription sources</a>
                        <div id="tnp-other-sources" style="display: none;">


                            <?php foreach ($sources as $source) { ?>
                                <?php if ($source['active']) continue; ?>
                                <div class="tnp-flow-item notusable"><a href="<?php echo esc_attr($source['url']) ?>" target="_blank"><?php echo esc_html($source['title']) ?></a></div>
                            <?php } ?>

                        </div>
                        <div class="tnp-flow-arrow">▼</div>
                        <div>
                            <div class="tnp-flow-item"><a href="?page=newsletter_subscription_antispam" target="_blank">Antispam</a></div>
                        </div>

                        <div>
                            <div class="tnp-flow-item"><a href="?page=newsletter_subscription_options" target="_blank">Messages</a></div>
                            <div class="tnp-flow-item"><a href="?page=newsletter_subscription_welcome" target="_blank">Welcome email</a></div>
                        </div>


                        <div style="height: 2rem"></div>

                        <div>

                            <div class="tnp-flow-item title wide">Subscribers</div>
                        </div>

                        <div class="tnp-flow-arrow">▼</div>
                        <div>
                            <div class="tnp-flow-item"><a href="?page=newsletter_users_index" target="_blank">Manage</a></div>
                            <div class="tnp-flow-item"><a href="?page=newsletter_users_import" target="_blank">Import/Export</a></div>
                            <div class="tnp-flow-item"><a href="?page=newsletter_users_statistics" target="_blank">Statistics</a></div>
                        </div>

                    </div>
                </div>
                <div class="tnp-card">


                    <div class="tnp-flow">
                        <div>
                            <div class="tnp-flow-item title wide">Newsletters</div>
                        </div>

                        <div class="tnp-flow-arrow">▼</div>

                        <div>
                            <div class="tnp-flow-item <?php echo $automated['active'] ? '' : 'inactive' ?>"><a href="<?php echo esc_attr($automated['url']) ?>" target="_blank">Automated</a></div>
                            <div class="tnp-flow-item"><a href="?page=newsletter_emails_index" target="_blank">Regular</a></div>
                            <div class="tnp-flow-item <?php echo $autoresponder['active'] ? '' : 'inactive' ?>"><a href="<?php echo esc_attr($autoresponder['url']) ?>" target="_blank">Autoresponder</a></div>
                        </div>

                        <a href="javascript:void()" onclick="document.getElementById('tnp-other-blocks').style.display = 'flex'; return false" style="display: block; text-decoration: underline">Contents for newsletters</a>
                        <div id="tnp-other-blocks" style="display: none;">
                            <?php foreach ($blocks as $block) { ?>
                                <div class="tnp-flow-item notusable"><a href="?page=newsletter_main_extensions#tnp-creating<?php //echo esc_attr($block['url'])           ?>" target="_blank"><?php echo esc_html($block['title']) ?></a></div>
                            <?php } ?>
                        </div>

                        <div class="tnp-flow-arrow">▼</div>

                        <div>
                            <div class="tnp-flow-item">Standard reports</div>
                            <div class="tnp-flow-item <?php echo $reports['active'] ? '' : 'inactive' ?>"><a href="<?php echo esc_attr($reports['url']) ?>" target="_blank"><a href="<?php echo esc_attr($reports['url']) ?>" target="_blank"><?php echo esc_html($reports['title']) ?></a></a></div>
                        </div>

                        <div style="height: 2rem"></div>

                        <div>
                            <div class="tnp-flow-item title wide">Links on newsletters</div>
                        </div>

                        <div class="tnp-flow-arrow">▼</div>

                        <div>
                            <div class="tnp-flow-item"><a href="?page=newsletter_profile_index" target="_blank">Subscriber profile</a></div>
                            <div class="tnp-flow-item"><a href="?page=newsletter_unsubscription_index" target="_blank">Unsubscription</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>




</div>
