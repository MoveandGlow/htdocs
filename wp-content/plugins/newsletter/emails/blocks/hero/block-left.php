<?php
defined('ABSPATH') || exit;
$widths = [];
if ($layout === 'left13') {
    $widths = [1, 2];
}
$items = [];
?>
<style>
    /* Styles which will be removed and injected in the replacing the matching "inline-class" attribute */
    .title {
        <?php $title_style->echo_css(0.8); ?>
        margin: 0;
        line-height: normal;
        padding: 0 0 20px 0;
    }
    .text {
        <?php $text_style->echo_css(); ?>
        padding: 0 0 15px;
        line-height: 1.5;
        margin: 0;
    }

    .button {
        padding: 10px 0 0 0;
    }
</style>
<?php
if ($media) {
    $items[] = TNP_Composer::image($media);
}
ob_start();
?>
<table align="right" class="responsive" border="0" cellspacing="0" cellpadding="0">
    <?php if (empty($order)) { ?>
        <tr>
            <td inline-class="title">
                <?php echo $options['title'] ?>
            </td>
        </tr>
        <tr>
            <td inline-class="text">
                <?php echo $options['text'] ?>
            </td>
        </tr>
    <?php } else { ?>
        <tr>
            <td inline-class="text">
                <?php echo $options['text'] ?>
            </td>
        </tr>
        <tr>
            <td inline-class="title">
                <?php echo $options['title'] ?>
            </td>
        </tr>

    <?php } ?>

    <tr>
        <td align="center" inline-class="button">
            <?php echo TNP_Composer::button($button_options, 'button', $composer) ?>
        </td>
    </tr>

</table>
<?php
$items[] = ob_get_clean();
echo TNP_Composer::grid($items, ['columns' => count($items), 'widths'=>$widths, 'width' => $composer['content_width'], 'responsive' => true]);
?>
