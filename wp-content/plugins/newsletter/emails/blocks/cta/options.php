<?php
/* @var $fields NewsletterFields */

$fields->controls->data['schema'] = '';
?>

<?php $fields->select('schema', __('Schema', 'newsletter'), ['' => 'Custom', 'wire' => 'Wire'], ['after-rendering' => 'reload']) ?>

<?php
$fields->button('button', 'Button layout', [
    'family_default' => true,
    'size_default' => true,
    'weight_default' => true,
    'media' => true
])
?>

<div class="tnp-field-row">

    <div class="tnp-field-col-2">
        <?php $fields->size('button_width', __('Width', 'newsletter')) ?>
    </div>
    <div class="tnp-field-col-2">
        <?php $fields->select('button_align', __('Alignment', 'newsletter'), ['center' => __('Center'), 'left' => __('Left'), 'right' => __('Right')]) ?>
    </div>

</div>


<div class="tnp-field-row">

    <div class="tnp-field-col-2">
        <?php $fields->lists_public('list', 'Add to', ['empty_label' => 'None']) ?>
    </div>
    <div class="tnp-field-col-2">
        <?php $fields->lists_public('unlist', 'Remove from', ['empty_label' => 'None']) ?>
    </div>
    <div style="clear: both"></div>
    <?php if (!method_exists('NewsletterReports', 'build_lists_change_url')) { ?>
        <label class="tnp-row-label">Requires the Reports Addon last version</label>
    <?php } ?>
</div>


<?php $fields->block_commons() ?>
