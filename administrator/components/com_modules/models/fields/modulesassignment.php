<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');

require_once __DIR__ . '/../../helpers/modules.php';

/**
 * ModulesPosition Field class for the Joomla Framework.
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldModulesAssignment extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'ModulesAssignment';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getOptions()
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '0', JText::_('JALL'));
		$options[] = JHtml::_('select.option', '-1', JText::_('COM_MODULES_ASSIGNED_VARIES_EXCEPT'));
		$options[] = JHtml::_('select.option', '1', JText::_('COM_MODULES_ASSIGNED_VARIES_ONLY'));
		$options[] = JHtml::_('select.option', '-', JText::_('JNONE'));

		return array_merge(parent::getOptions(), $options);
	}
}
