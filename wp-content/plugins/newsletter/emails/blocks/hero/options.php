<?php
/*
 * @var $options array contains all the options the current block we're ediging contains
 * @var $controls NewsletterControls
 */
/* @var $fields NewsletterFields */

$fields->controls->data['schema'] = '';
?>

<?php $controls->hidden('image_url') ?>
<div class="tnp-field-row">
    <div class="tnp-field-col-3">
        <?php $fields->select('layout', __('Layout', 'newsletter'), array('full' => 'Full', 'left' => 'Image left', 'left13' => 'Image left 1/3', 'right' => 'Image right', 'right13' => 'Image right 1/3')) ?>
    </div>
    <div class="tnp-field-col-3">
        <?php $fields->select('schema', __('Schema', 'newsletter'), array('' => 'Custom', 'bright' => 'Bright', 'dark' => 'Dark', 'orangeblue'=>'Orange+Blue'), ['after-rendering' => 'reload']) ?>
    </div>
    <div class="tnp-field-col-3">
        <?php $fields->select('order', __('Order', 'newsletter'), ['' => 'Title/Text', 'inverted' => 'Text/Title']) ?>
    </div>
</div>

<?php $fields->text('title', __('Title', 'newsletter')) ?>

<?php $fields->font('title_font', '', ['family_default'=>true, 'size_default'=>true, 'weight_default'=>true, 'align'=>true]) ?>

<?php $fields->media('image', __('Image', 'newsletter'), array('alt' => true)) ?>

<?php $fields->textarea('text', __('Text', 'newsletter')) ?>
<?php $fields->font( 'font', '', [ 'family_default' => true, 'size_default' => true, 'weight_default' => true, 'align'=>true ] ) ?>

<?php $fields->button('button', __('Button', 'newsletter'), [
	'family_default' => true,
	'size_default'   => true,
	'weight_default' => true,
        'align'=>true
]) ?>

<?php $fields->block_commons() ?>

