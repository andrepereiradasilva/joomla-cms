<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
?>
<fieldset class="<?php echo !empty($displayData->formclass) ? $displayData->formclass : 'form-horizontal'; ?>">
	<legend><?php echo $displayData->name; ?></legend>

	<?php if (!empty($displayData->description)) : ?>
		<p><?php echo $displayData->description; ?></p>
	<?php endif; ?>

	<?php $fieldsnames = explode(',', $displayData->fieldsname); ?>

	<?php foreach ($fieldsnames as $fieldname) : ?>
		<?php foreach ($displayData->form->getFieldset($fieldname) as $field) : ?>
			<?php if ($field->showon) : ?>
				<?php JHtml::_('behavior.showon'); ?>
			<?php endif; ?>	
			<div class="control-group"<?php echo JFormHelper::renderDataAttributes($field->dataAttributes); ?>>
				<?php if (!isset($displayData->showlabel) || $displayData->showlabel) : ?>
					<div class="control-label"><?php echo $field->label; ?></div>
				<?php endif; ?>

				<div class="controls"><?php echo $field->input; ?></div>
			</div>
		<?php endforeach; ?>
	<?php endforeach; ?>
</fieldset>
