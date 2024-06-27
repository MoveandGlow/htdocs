<?php
/* @var $this Newsletter */

defined('ABSPATH') || exit;

include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();
$src = esc_attr(plugins_url('newsletter') . '/main/images/autoresponder');
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
        <h2>Autoresponder/Email Series (demo)</h2>
    </div>


    <div id="tnp-body" >

        <div class="tnp-notice">
            To activate the Autoresponder Addon, insert a valid license on the Newsletter's Settings and
            go to the Addons panel.
        </div>

        <div class="tnp-promo-intro">
            The Autotresponder Addon manages email series sent to subscribers after the subscription or other events (purchases,
            list change, ...).
        </div>

        <table class="tnp-promo" scope="layout">
            <tr>
                <td width="60%">
                    <img src="<?php echo $src ?>/index.png">
                </td>
                <td width="40%">
                    Create as many series as you need, with different options and emails.
                </td>
            </tr>
            <tr>
                <td width="60%">
                    <img src="<?php echo $src ?>/messages.png">
                </td>
                <td width="40%">
                    Add as many email series steps as you need. Every email can be created with our composer with
                    a specific delay. For each email you can access the report page as for regular newsletters.
                </td>
            </tr>
            <tr>
                <td width="60%">
                    <img src="<?php echo $src ?>/statistics.png">
                </td>
                <td width="40%">
                    You can have a costant overview on how the series is performing. How many subscribers for every
                    steps, how many abandons and the reason.
                </td>
            </tr>
            <tr>
                <td width="60%">
                    <img src="<?php echo $src ?>/subscribers.png">
                </td>
                <td width="40%">
                    Dive deep to the single subscriber's details, stop or restart the series when required.
                </td>
            </tr>
        </table>

        <div class="tnp-promo-footer">
            <a href="?page=newsletter_main_autoresponderindex" class="button-secondary">Surf some demo panels</a>
            <a href="https://www.thenewsletterplugin.com/premium?utm_campaign=autoresponder&utm_source=plugin" target="_blank" class="button-primary">Get it</a>
        </div>

    </div>

</div>
