<?php
$size = [$composer['width'], 0];
$content_width = $composer['width'] - $options['block_padding_left'] - $options['block_padding_right'];
$title_style = TNP_Composer::get_title_style($options, 'title', $composer);
$text_style = TNP_Composer::get_text_style($options, '', $composer);
?>

<style>
    .title-td {
        padding-bottom: 20px;
        padding-left: <?php echo (int) $options['text_padding_left'] ?>px;
        padding-right: <?php echo (int) $options['text_padding_right'] ?>px;
    }
    .title {
        <?php $title_style->echo_css() ?>
        line-height: normal;
        margin: 0;
        text-decoration: none;
    }

    .content {
        <?php $text_style->echo_css() ?>
        padding-left: <?php echo (int) $options['text_padding_left'] ?>px;
        padding-right: <?php echo (int) $options['text_padding_right'] ?>px;
        line-height: 1.5;
    }

    .p {
        <?php $text_style->echo_css() ?>
        line-height: 1.5 !important;
    }

    .li {
        <?php $text_style->echo_css() ?>
        line-height: normal!important;
    }

    .meta {
        <?php $text_style->echo_css(0.9) ?>
        line-height: normal!important;
        padding-bottom: 10px;
        text-align: center;
        font-style: italic;
    }

    .button {
        padding: 15px 0;
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
        $media = tnp_composer_block_posts_get_media($post, $size);
        if ($media) {
            $media->set_width($content_width);
            $media->link = $post->url;
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
    ?>


    <table border="0" cellpadding="0" align="center" cellspacing="0" width="100%" class="responsive">
        <tr>
            <td inline-class="title-td">
                <?php echo $post->title_linked ?>
            </td>
        </tr>

        <?php if ($meta) { ?>
            <tr>
                <td inline-class="meta">
                    <?php echo esc_html(implode(' - ', $meta)) ?>
                </td>
            </tr>
        <?php } ?>

        <?php if ($media) { ?>
            <tr>
                <td align="center">
                    <?php echo TNP_Composer::image($media) ?>
                </td>
            </tr>
        <?php } ?>

        <tr>
            <td align="<?php echo $align_left ?>" dir="<?php echo $dir ?>" inline-class="content">
                <?php echo TNP_Composer::post_content($post) ?>
            </td>
        </tr>

        <?php if ($show_read_more_button) { ?>
            <tr>
                <td align="center" inline-class="button">
                    <?php echo TNP_Composer::button($button_options, 'button', $composer) ?>
                </td>
            </tr>
        <?php } ?>
    </table>
    <br><br>

<?php } ?>
