<?php
/*
 * Name: Company Info
 * Section: footer
 * Description: Company Info for Can-Spam act requirements
 */

$default_options = array(
    'font_family' => '',
    'font_size' => '',
    'font_color' => '',
    'font_weight' => '',
    'block_padding_top' => 15,
    'block_padding_bottom' => 15,
    'block_padding_left' => 15,
    'block_padding_right' => 15,
    'block_background' => '',
    'title' => $info['footer_title'],
    'address' => $info['footer_contact'],
    'copyright' => $info['footer_legal'],
);

$options = array_merge($default_options, $options);

$text_style = TNP_Composer::get_text_style($options, '', $composer);
?>

<style>
    .text {
        <?php $text_style->echo_css() ?>
        padding: 10px;
        text-align: center;
        line-height: normal;
    }
</style>

<div inline-class="text">
    <strong><?php echo esc_html($options['title']) ?></strong>
    <br>
    <?php echo esc_html($options['address']) ?>
    <br>
    <em><?php echo esc_html($options['copyright']) ?></em>
</div>
