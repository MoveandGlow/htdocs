<?php
$size = [1200, 0, false];
$content_width = $composer['width'] - $options['block_padding_left'] - $options['block_padding_right'];
$title_style = TNP_Composer::get_title_style($options, 'title', $composer);
$text_style = TNP_Composer::get_style($options, '', $composer, 'text');
?>
<style>
    .title-td {
        padding: 0 0 5px 0;
    }
    .title {
        <?php echo $title_style->echo_css() ?>
        line-height: normal!important;
        text-decoration: none;
    }

    .excerpt-td {
        padding: 10px 0 15px 0;
    }

    .excerpt {
        <?php echo $text_style->echo_css() ?>
        line-height: 1.5 !important;
        text-decoration: none;
    }

    .meta {
        <?php echo $text_style->echo_css(0.9) ?>
        line-height: normal!important;
        padding: 0 0 5px 0;
        font-style: italic;
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
    $button_options['button_url'] = $post->url;

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
    ?>

    <?php if ($media) { ?>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 20px">
            <tr>
                <td align="center">
                    <?php echo TNP_Composer::image($media) ?>
                </td>
            </tr>
        </table>
    <?php } ?>

    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="responsive" style="margin: 0;">
        <tr>
            <td style="padding-left: <?php echo (int)$options['text_padding_left'] ?>px; padding-right: <?php echo (int)$options['text_padding_right'] ?>px">

                <table border="0" cellspacing="0" cellpadding="0" width="100%">


                    <tr>
                        <td align="<?php echo $align_left ?>" inline-class="title-td">
                                <?php echo $post->title_linked ?>
                        </td>
                    </tr>

                    <?php if ($meta) { ?>
                        <tr>
                            <td align="<?php echo $align_left ?>" inline-class="meta">
                                <?php echo esc_html(implode(' - ', $meta)) ?>
                            </td>
                        </tr>
                    <?php } ?>

                    <?php if ($excerpt_length) { ?>
                        <tr>
                            <td align="<?php echo $align_left ?>" inline-class="excerpt-td" dir="<?php echo $dir ?>">
                                <?php echo $post->excerpt_linked ?>
                            </td>
                        </tr>
                    <?php } ?>

                    <?php if ($show_read_more_button) { ?>
                        <tr>
                            <td align="<?php echo $align_left ?>" inline-class="button">
                                <?php echo TNP_Composer::button($button_options, 'button', $composer) ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td style="padding: 10px">&nbsp;</td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

<?php } ?>


