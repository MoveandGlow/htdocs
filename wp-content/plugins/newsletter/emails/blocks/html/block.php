<?php
/*
 * Name: Html
 * Section: content
 * Description: Free HTML block
 *
 */

/* @var $options array */
/* @var $wpdb wpdb */

$default_options = array(
    'html'=>'This is a piece of nice html code. You can use any tag, but be aware that email readers do not render everything.',
    'block_padding_left' => 15,
    'block_padding_right' => 15,
    'block_padding_top' => 20,
    'block_padding_bottom' => 20,
    'block_background' => '',
    'font_family' => '',
    'font_size' => '',
    'font_color' => '',
    'font_weight' => '',
);

$options = array_merge($default_options, $options);
$title_style = TNP_Composer::get_text_style($options, '', $composer);
?>
<style>
    .html-td {
        <?php $title_style->echo_css()?>
        padding: 0;
        line-height: normal !important;
        letter-spacing: normal;
    }
</style>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td valign="top" align="center" inline-class="html-td" class="html-td-global">
            <?php echo $options['html'] ?>
        </td>
    </tr>
</table>

