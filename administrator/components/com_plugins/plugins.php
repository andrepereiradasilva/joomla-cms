<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_plugins
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JHtml::_('behavior.tabstate');

if (!JFactory::getUser()->authorise('core.manage', 'com_plugins'))
{
	JLoader::register('JControllerExceptionNotAllowed', JPATH_PLATFORM . '/joomla/controller/exception/notallowed.php');

	throw new JControllerExceptionNotAllowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

$controller = JControllerLegacy::getInstance('Plugins');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
