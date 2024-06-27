<?php
$size = [600, 0];
$total_width = $composer['width'] - $options['block_padding_left'] - $options['block_padding_right'];
$column_width = $total_width / 2 - 10;

$title_style = TNP_Composer::get_style($options, 'title', $composer, 'title', ['scale' => .8]);
$text_style = TNP_Composer::get_style($options, '', $composer, 'text');
?>
<style>
    .title-td {
        padding: 0 0 5px 0;
    }
    .title {
        <?php $title_style->echo_css() ?>
        line-height: normal !important;
        text-decoration: none;
    }
    .excerpt-td {
        padding: 10px 0 15px 0;
    }
    .excerpt {
        <?php $text_style->echo_css() ?>
        line-height: 1.5 !important;
        text-decoration: none;
    }

    .meta {
        <?php $text_style->echo_css(0.9) ?>
        font-style: italic;
        padding: 0 0 10px 0;
        line-height: normal !important;
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
    $button_options['button_align'] = 'left';
    $items = [];
    ?>

    <?php if ($media) { ?>
        <?php ob_start(); ?>

        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="padding-bottom: 20px;" width="100%">
                    <?php echo TNP_Composer::image($media, ['class' => 'fluid']) ?>
                </td>
            </tr>
        </table>
        <?php $items[] = ob_get_clean(); ?>
    <?php } ?>


    <?php ob_start() ?>
    <table border="0" cellspacing="0" cellpadding="0" width="100%">
        <?php if ($meta) { ?>
            <tr>
                <td inline-class="meta" dir="<?php echo $dir ?>" align="<?php echo $align_left ?>">
                    <?php echo esc_html(implode(' - ', $meta)) ?>
                </td>
            </tr>
        <?php } ?>

        <tr>
            <td align="<?php echo $align_left ?>" inline-class="title-td">
                <?php echo $post->title_linked ?>
            </td>
        </tr>

        <?php if ($excerpt_length) { ?>
            <tr>
                <td align="<?php echo $align_left ?>" inline-class="excerpt-td">
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
    </table>
    <?php $items[] = ob_get_clean(); ?>

    <?php echo TNP_Composer::grid($items, ['columns' => count($items), 'width' => $composer['width'] - $options['block_padding_left'] - $options['block_padding_right']]); ?>

<?php } ?>





