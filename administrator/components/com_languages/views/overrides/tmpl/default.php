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

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

$clientId   = (int) $this->state->get('client_id');
$client     = $clientId ? JText::_('JADMINISTRATOR') : JText::_('JSITE');
$clientPath = $clientId ? JPATH_ADMINISTRATOR : JPATH_SITE;
$language   = $this->state->get('language');
$languages  = JLanguage::getKnownLanguages($clientPath);
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
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('filterButton' => false))); ?>
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
							<?php echo JText::_('COM_LANGUAGES_HEADING_LANGUAGE'); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone hidden-tablet">
							<?php echo JText::_('COM_LANGUAGES_HEADING_LANG_TAG'); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone hidden-tablet">
							<?php echo JText::_('JCLIENT'); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="6">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
				<?php $canEdit = JFactory::getUser()->authorise('core.edit', 'com_languages'); ?>
				<?php $i = 0; ?>
				<?php foreach ($this->items as $key => $text) : ?>
					<tr class="row<?php echo $i % 2; ?>" id="overriderrow<?php echo $i; ?>">
						<td class="center">
							<?php echo JHtml::_('grid.id', $i, $key); ?>
						</td>
						<td>
							<?php if ($canEdit) : ?>
								<a id="key[<?php echo $this->escape($key); ?>]" href="<?php echo JRoute::_('index.php?option=com_languages&task=override.edit&id=' . $key); ?>"><?php echo $this->escape($key); ?></a>
							<?php else: ?>
								<?php echo $this->escape($key); ?>
							<?php endif; ?>
						</td>
						<td class="hidden-phone">
							<span id="string[<?php	echo $this->escape($key); ?>]"><?php echo $this->escape($text); ?></span>
						</td>
						<td class="hidden-phone hidden-tablet">
							<?php echo $languages[$language]['name']; ?>
						</td>
						<td class="hidden-phone hidden-tablet">
							<?php echo $language; ?>
						</td>
						<td class="hidden-phone hidden-tablet">
							<?php echo $client; ?>
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
