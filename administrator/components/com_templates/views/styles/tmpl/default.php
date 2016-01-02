<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_templates
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user      = JFactory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_templates&view=styles'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif; ?>
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<div class="clear"> </div>
		<?php if (empty($this->items)) : ?>
				<div class="alert alert-no-items">
					<?php echo JText::_('COM_TEMPLATES_MSG_MANAGE_NO_STYLES'); ?>
				</div>
		<?php else : ?>
			<table class="table table-striped" id="styleList">
				<thead>
					<tr>
						<th width="5">
							&#160;
						</th>
						<th>
							<?php echo JHtml::_('grid.sort', 'COM_TEMPLATES_HEADING_STYLE', 'a.title', $listDirn, $listOrder); ?>
						</th>
						<th width="5%" class="nowrap center">
							<?php echo JHtml::_('grid.sort', 'COM_TEMPLATES_HEADING_DEFAULT', 'a.home', $listDirn, $listOrder); ?>
						</th>
						<th width="5%" class="nowrap center hidden-phone">
							<?php echo JText::_('COM_TEMPLATES_HEADING_ASSIGNED'); ?>
						</th>
						<th width="10%" class="nowrap">
							<?php echo JHtml::_('grid.sort', 'JCLIENT', 'a.client_id', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone">
							<?php echo JHtml::_('grid.sort', 'COM_TEMPLATES_HEADING_TEMPLATE', 'a.template', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="8">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
					<?php foreach ($this->items as $i => $item) :
						$canCreate = $user->authorise('core.create',     'com_templates');
						$canEdit   = $user->authorise('core.edit',       'com_templates');
						$canChange = $user->authorise('core.edit.state', 'com_templates');
					?>
					<tr class="row<?php echo $i % 2; ?>">
						<td width="1%" class="center">
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td>
							<?php if ($this->preview && $item->client_id == '0') : ?>
								<a target="_blank" href="<?php echo JUri::root() . 'index.php?tp=1&templateStyle=' . (int) $item->id ?>" class="jgrid">
								<span class="icon-eye-open hasTooltip" title="<?php echo JHtml::tooltipText(JText::_('COM_TEMPLATES_TEMPLATE_PREVIEW'), $item->title, 0); ?>" ></span></a>
							<?php elseif ($item->client_id == '1') : ?>
								<span class="icon-eye-close disabled hasTooltip" title="<?php echo JHtml::tooltipText('COM_TEMPLATES_TEMPLATE_NO_PREVIEW_ADMIN'); ?>" ></span>
							<?php else : ?>
								<span class="icon-eye-close disabled hasTooltip" title="<?php echo JHtml::tooltipText('COM_TEMPLATES_TEMPLATE_NO_PREVIEW'); ?>" ></span>
							<?php endif; ?>
							<?php if ($canEdit) : ?>
							<a href="<?php echo JRoute::_('index.php?option=com_templates&task=style.edit&id=' . (int) $item->id); ?>">
								<?php echo $this->escape($item->title); ?></a>
							<?php else : ?>
								<?php echo $this->escape($item->title); ?>
							<?php endif; ?>
						</td>
						<td class="center">
							<?php if ($item->home == '0' || $item->home == '1') : ?>
								<?php
									echo JHtml::_(
										'jgrid.isdefault',
										$item->home != '0',
										$i,
										array(
											'prefix'  => 'styles.',
											'states'  => array(
												0 => array(
															'setDefault',
															'',
															JText::_('COM_TEMPLATES_GRID_SET_DEFAULT'),
															'',
															1,
															'unfeatured',
															'unfeatured',
															0
														),
												1 => array(
															'unsetDefault',
															'',
															JText::_('COM_TEMPLATES_GRID_UNSET_DEFAULT'),
															JText::_('COM_TEMPLATES_GRID_DEFAULT'),
															1,
															'featured',
															'featured',
															0
														)
												)
										),
										$canChange && $item->home != '1',
										false
										);
								?>
							<?php else : ?>
								<?php
									echo JHtml::_(
										'jgrid.isdefault',
										true,
										$i,
										array(
											'prefix'  => 'styles.',
											'states'  => array(
												0 => array(
															'setDefault',
															JHtml::_('image', 'mod_languages/' . $item->language_image . '.gif', $item->language_title, array('style' => 'opacity: 0.3;'), true),
															JText::sprintf('COM_TEMPLATES_GRID_SET_DEFAULT_LANGUAGE', $item->language_title),
															'',
															1,
															'',
															'',
															1
														),
												1 => array(
															'unsetDefault',
															JHtml::_('image', 'mod_languages/' . $item->language_image . '.gif', $item->language_title, array(), true),
															JText::sprintf('COM_TEMPLATES_GRID_UNSET_DEFAULT_LANGUAGE', $item->language_title),
															JText::sprintf('COM_TEMPLATES_GRID_DEFAULT_LANGUAGE', $item->language_title),
															1,
															'',
															'',
															1
														)
												)
										),
										$canChange,
										false
										);
								?>
							<?php endif; ?>
						</td>
						<td class="center hidden-phone">
							<?php if ($item->assigned > 0) : ?>
								<span class="icon-ok tip hasTooltip" title="<?php echo JHtml::tooltipText(JText::plural('COM_TEMPLATES_ASSIGNED', $item->assigned), '', 0); ?>"></span>
							<?php else : ?>
								&#160;
							<?php endif; ?>
						</td>
						<td class="small">
							<?php echo $item->client_id == 0 ? JText::_('JSITE') : JText::_('JADMINISTRATOR'); ?>
						</td>
						<td class="hidden-phone">
							<label for="cb<?php echo $i; ?>" class="small">
								<a href="<?php echo JRoute::_('index.php?option=com_templates&view=template&id=' . (int) $item->e_id); ?>">
									<?php echo ucfirst($this->escape($item->template)); ?>
								</a>
							</label>
						</td>
						<td class="hidden-phone">
							<?php echo (int) $item->id; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
