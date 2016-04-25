<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');
JHtml::_('bootstrap.tooltip');

$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_languages&view=overrides'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<div class="clearfix"></div>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table table-striped" id="overrideList">
				<thead>
					<tr>
						<th width="1%" class="nowrap center">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th width="30%" class="nowrap">
							<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_VIEW_OVERRIDES_KEY', 'key', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_VIEW_OVERRIDES_TEXT', 'text', $listDirn, $listOrder); ?>
						</th>
						<th width="15%" class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_HEADING_LANGUAGE', 'language', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_HEADING_LANG_TAG', 'language_tag', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="5">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
				<?php $canEdit = JFactory::getUser()->authorise('core.edit', 'com_languages'); ?>
				<?php $i = 0; ?>
				<?php foreach ($this->items as $i => $item) : ?>
					<?php $itemId = '[' . $item->language_tag . ']' . $item->key; ?>
					<tr class="row<?php echo $i % 2; ?>" id="overriderrow<?php echo $i; ?>">
						<td class="center">
							<?php echo JHtml::_('grid.id', $i, $itemId); ?>
						</td>
						<td>
							<?php if ($canEdit) : ?>
								<a id="key[<?php echo $this->escape($itemId); ?>]" href="<?php echo JRoute::_('index.php?option=com_languages&task=override.edit&id=' . $itemId); ?>"><?php echo $this->escape($item->key); ?></a>
							<?php else: ?>
								<?php echo $this->escape($item->key); ?>
							<?php endif; ?>
						</td>
						<td class="hidden-phone">
							<span id="string[<?php	echo $this->escape($item->key); ?>]"><?php echo $this->escape($item->text); ?></span>
						</td>
						<td class="hidden-phone hidden-tablet">
							<?php echo $item->language; ?>
						</td>
						<td class="hidden-phone hidden-tablet">
							<?php echo $item->language_tag; ?>
						</td>
					</tr>
				<?php $i++; ?>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
