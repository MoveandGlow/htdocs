<?php
$size = array(600, 400, !empty($options['nocrop']));
$total_width = $composer['width'] - $options['block_padding_left'] - $options['block_padding_right'];
$column_width = $total_width / 2 - 20;

$title_style = TNP_Composer::get_style($options, 'title', $composer, 'title', ['scale' => .8]);
$text_style = TNP_Composer::get_style($options, '', $composer, 'text');

$items = [];
?>
<style>
    .title-td {
        padding: 15px 0 0 0;
    }

    .title {
        <?php $title_style->echo_css() ?>
        line-height: 1.3;
        text-decoration: none;
    }

    .excerpt-td {
        padding: 5px 0 0 0;
    }
    .excerpt {
        <?php $text_style->echo_css() ?>
        line-height: 1.4;
        text-decoration: none;
    }

    .meta {
        <?php $text_style->echo_css(0.9) ?>
        padding: 10px 0 0 0;
        font-style: italic;
        line-height: normal !important;
    }
    .button {
        padding: 15px 0;
    }
    .column-left {
        padding-right: 10px;
        padding-bottom: 20px;
    }
    .column-right {
        padding-left: 10px;
        padding-bottom: 20px;
    }
    .main-title {
        <?php $main_title_style->echo_css(1.1)?>
        padding: 0 0 20px 0;
        line-height: normal !important;
        letter-spacing: normal;
    }
</style>

<?php if (!empty($main_title)) { ?>
    <table border="0" cellspacing="0" cellpadding="0" width="100%">
        <tr>
            <td align="<?php echo esc_attr($options['main_title_align']) ?>" valign="middle" inline-class="main-title" dir="<?php echo esc_attr($dir) ?>">
                <?php echo $main_title; ?>
            </td>
        </tr>
    </table>
<?php } ?>

<?php foreach ($posts as $post) { ?>
    <?php
    $media = null;
    if ($show_image) {
        $media = tnp_composer_block_posts_get_media($post, $size, $image_placeholder_url);
        if ($media) {
            $media->link = $post->url;
            $media->set_width($column_width);
        }
    }

    $meta = [];

    if ($show_date) {
        $meta[] = tnp_post_date($post);
    }

    if ($show_author) {
        $author_object = get_user_by('id', $post->post_author);
        if ($author_object) {
            $meta[] = apply_filters('the_author', $author_object->display_name);
        }
    }

    $button_options['button_url'] = $post->url;
    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <?php if ($media) { ?>
            <tr>
                <td align="center" valign="middle">
                    <?php echo TNP_Composer::image($media, ['class' => 'fluid']) ?>
                </td>
            </tr>
        <?php } ?>
        <tr>
            <td align="center" inline-class="title-td">
                <?php echo $post->title_linked ?>
            </td>
        </tr>
        <?php if ($meta) { ?>
            <tr>
                <td align="center" inline-class="meta" class="meta">
                    <?php echo esc_html(implode(' - ', $meta)) ?>
                </td>
            </tr>
        <?php } ?>


        <?php if ($excerpt_length) { ?>
            <tr>
                <td align="center" inline-class="excerpt-td">
                    <?php echo $post->excerpt_linked ?>
                </td>
            </tr>
        <?php } ?>

        <?php if ($show_read_more_button) { ?>
            <tr>
                <td align="center" inline-class="button">
                    <?php echo TNP_Composer::button($button_options, 'button', $composer) ?>
                </td>
            </tr>
        <?php } ?>
    </table>
    <?php
    $items[] = ob_get_clean();
}
?>


<?php echo TNP_Composer::grid($items, ['width' => $total_width, 'responsive' => true, 'padding' => 5]) ?>



