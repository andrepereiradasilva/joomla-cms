<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_installer
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Installer Update Sites Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  com_installer
 * @since       3.4
 */
class InstallerControllerUpdatesites extends JControllerLegacy
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JController
	 * @since   3.4
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('unpublish', 'publish');
		$this->registerTask('publish',   'publish');
		$this->registerTask('delete',    'delete');
		$this->registerTask('rebuild',   'rebuild');
	}

	/**
	 * Enable/Disable an extension (if supported).
	 *
	 * @return  void
	 *
	 * @since   3.4
	 *
	 * @throws  Exception on error
	 */
	public function publish()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$updateSitesIds = $this->input->get('cid', array(), 'array');
		$value          = ArrayHelper::getValue(array('publish' => 1, 'unpublish' => 0), $this->getTask(), 0, 'int');

		if (empty($updateSitesIds))
		{
			throw new Exception(JText::_('COM_INSTALLER_ERROR_NO_UPDATESITES_SELECTED'), 500);
		}

		// Get the model.
		$this->getModel('Updatesites')->publish($updateSitesIds, $value);

		$this->setRedirect(JRoute::_('index.php?option=com_installer&view=updatesites', false));
	}

	/**
	 * Deletes an update site (if supported).
	 *
	 * @return  void
	 *
	 * @since   3.6
	 *
	 * @throws  Exception on error
	 */
	public function delete()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$updateSitesIds = $this->input->get('cid', array(), 'array');

		if (empty($updateSitesIds))
		{
			throw new Exception(JText::_('COM_INSTALLER_ERROR_NO_UPDATESITES_SELECTED'), 500);
		}

		// Delete the records.
		$this->getModel('Updatesites')->delete($updateSitesIds);

		$this->setRedirect(JRoute::_('index.php?option=com_installer&view=updatesites', false));
	}

	/**
	 * Rebuild update sites tables.
	 *
	 * @return  void
	 *
	 * @since   3.6
	 */
	public function rebuild()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Rebuild the update sites.
		$this->getModel('Updatesites')->rebuild();

		$this->setRedirect(JRoute::_('index.php?option=com_installer&view=updatesites', false));
	}
}
