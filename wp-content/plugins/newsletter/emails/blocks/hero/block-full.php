<style>
    /* Styles which will be removed and injected in the replacing the matching "inline-class" attribute */
    .title {
        <?php $title_style->echo_css() ?>
        line-height: normal;
        margin: 0;
            padding: 0 0 20px 0;
    }

    .text {
        <?php $text_style->echo_css() ?>

            padding: 0 0 20px 0;

        line-height: 1.5;
        margin: 0;
    }

    .image {
        padding-bottom: 20px;
    }

    .button {
        padding: 10px 0 0 0;
    }

</style>

<table width="100%" class="responsive" border="0" cellspacing="0" cellpadding="0">
    <?php if ($media) { ?>
        <tr>
            <td align="center" inline-class="image">
                <?php echo TNP_Composer::image($media); ?>
            </td>
        </tr>
    <?php } ?>

    <?php if (empty($order)) { ?>    
        <tr>
            <td align="center" inline-class="title">
                <?php echo $options['title'] ?>
            </td>
        </tr>

        <tr>
            <td align="center" inline-class="text">
                <?php echo $options['text'] ?>
            </td>
        </tr>
    <?php } else { ?>
        <tr>
            <td align="center" inline-class="text">
                <?php echo $options['text'] ?>
            </td>
        </tr>
        <tr>
            <td align="center" inline-class="title">
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
