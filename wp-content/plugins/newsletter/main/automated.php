<?php
/* @var $this Newsletter */

defined('ABSPATH') || exit;

include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();
$src = esc_attr(plugins_url('newsletter') . '/main/images/automated');
?>

<style>
<?php include __DIR__ . '/css/automation.css' ?>

    .tnp-promo {
        background-color: #fff;
        max-width: 850px;
    }
    .tnp-promo td {
        padding: 1rem;
        font-size: 1.2rem;
        line-height: 150%;
        vertical-align: top;
        text-align: left;
    }

    .tnp-promo td img {
        max-width: 100%;
        display: block;
        border: 1px solid #ddd;
        box-shadow: 0 0 5px #ccc;
    }
    .tnp-promo-intro {
        font-size: 1.3rem;
        line-height: normal;
        margin-top: 2rem;
        margin-bottom: 3rem;
        max-width: 850px;
    }
    .tnp-promo-footer {
        text-align: left;
        margin-top: 2rem;
        margin-bottom: 3rem;
    }
</style>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_DIR . '/tnp-header.php'; ?>

    <div id="tnp-heading">
        <h2>Automated Newsletters (demo)</h2>
    </div>


    <div id="tnp-body" >

        <div class="tnp-notice">
            To activate the Automated addon, insert a valid license on the Newsletter's Settings and
            go to the Addons panel.
        </div>

        <div class="tnp-promo-intro">
            The Automated addon generates automatically newsletters with fresh content from your site (articles, products, ...)
            with a fully configurable scheduling.
        </div>

        <div class="tnp-promo-footer">
            <a href="?page=newsletter_main_automatedindex" class="button-secondary">Surf some demo panels</a>
            <a href="https://www.thenewsletterplugin.com/premium?utm_campaign=automated&utm_source=plugin" target="_blank" class="button-primary">Get it</a>
        </div>

        <table class="tnp-promo" scope="layout">
            <tr>
                <td width="60%">
                    <img src="<?php echo $src ?>/index.png">
                </td>
                <td width="40%">
                    Create as many channels as you need, with different newsletter templates, planning and targeting.
                </td>
            </tr>
            <tr>
                <td width="60%">
                    <img src="<?php echo $src ?>/template.png">
                </td>
                <td width="40%">
                    Design the newsletter template for each channel with our composer and add dynamic content blocks: they'll be updated
                    at the right time with the right content.
                </td>
            </tr>
            <tr>
                <td width="60%">
                    <img src="<?php echo $src ?>/planning.png">
                </td>
                <td width="40%">
                    Plan, for each channel, when you want the newsletter to be created and sent. No fresh content? Automated is smart enough to
                    stop and wait for the next scheduled day.
                </td>
            </tr>
            <tr>
                <td width="60%">
                    <img src="<?php echo $src ?>/newsletters.png">
                </td>
                <td width="40%">
                    For each channel you have the list of the generated newsletters and their statistics.
                </td>
            </tr>
        </table>

        <div class="tnp-promo-footer">
            <a href="?page=newsletter_main_automatedindex" class="button-secondary">Surf some demo panels</a>
            <a href="https://www.thenewsletterplugin.com/premium?utm_campaign=automated&utm_source=plugin" target="_blank" class="button-primary">Get it</a>
        </div>

    </div>

</div>
