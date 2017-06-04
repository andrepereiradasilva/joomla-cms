<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Update site table
 * Stores the update sites for extensions
 *
 * @package     Joomla.Platform
 * @subpackage  Table
 * @since       3.4
 */
class JTableUpdatesite extends JTable
{
	/**
	 * The update site extension id.
	 *
	 * @var    integer
	 * @since  __DEPLOY_VERSION__
	 */
	protected $_extensionId = null;

	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  $db  Database driver object.
	 *
	 * @since   3.4
	 */
	public function __construct($db)
	{
		parent::__construct('#__update_sites', 'update_site_id', $db);

		JTableObserverUpdatesite::createObserver($this, array());
	}

	/**
	 * Overloaded check function
	 *
	 * @return  boolean  True if the object is ok
	 *
	 * @see     JTable::check()
	 * @since   3.4
	 */
	public function check()
	{
		// Check for valid name
		if (trim($this->name) === '' || trim($this->location) === '')
		{
			$this->setError(JText::_('JLIB_DATABASE_ERROR_MUSTCONTAIN_A_TITLE_EXTENSION'));

			return false;
		}

		return true;
	}

	/**
	 * Set the update site extension id.
	 *
	 * @param   integer  $extensionId  The update site extension id.
	 *
	 * @return  void.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function setExtensionId($extensionId = 0)
	{
		$this->_extensionId = (int) $extensionId;
	}

	/**
	 * Get the update site extension id.
	 *
	 * @return  integer  $extensionId  The update site extension id.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getExtensionId()
	{
		return $this->_extensionId;
	}

	/**
	 * Get the update sites for an extension id.
	 *
	 * @param   integer  $extensionId  The update site extension id.
	 *
	 * @return  array  Array with the update sites for this extension id.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function getUpdateSitesForExtension($extensionId = null)
	{
		if ($extensionId === null)
		{
			$extensionId = $this->getExtensionId();
		}

		if (!$extensionId)
		{
			return array();
		}

		$extensionId = (int) $extensionId;

		$query = $this->_db->getQuery(true)
			->select('us.*')
			->from($this->_db->qn('#__update_sites', 'us'))
			->join('LEFT', $this->_db->qn('#__update_sites_extensions', 'use') . ' ON ' . $this->_db->qn('use.update_site_id') . ' = ' . $this->_db->qn('us.update_site_id'))
			->where($this->_db->qn('use.extension_id') . ' = ' . $extensionId);

		return $this->_db->setQuery($query)->loadObjectList('update_site_id');
	}

	/**
	 * Add update sites.
	 *
	 * @param   array    $updateSites  Array of update sites.
	 * @param   integer  $extensionId  The update site extension id.
	 *
	 * @return  boolean  True if added, false otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function addUpdateSites($updateSites = array(), $extensionId = null)
	{
		if ($extensionId !== null && $extensionId !== null)
		{
			$this->setExtensionId((int) $extensionId);
		}

		// Confirm we have an extension id.
		if (!$this->getExtensionId())
		{
			return false;
		}

		// If it is a simple xml element we need to convert it.
		if ($updateSites instanceof SimpleXMLElement)
		{
			$updateServers = $updateSites;
			$updateSites   = array();

			// If update servers where found.
			foreach ($updateServers->children() as $updateServer)
			{
				$attrs = $updateServer->attributes();

				$updateSite = new stdClass;
				$updateSite->update_site_id = null;
				$updateSite->name           = trim($attrs['name']);
				$updateSite->type           = trim($attrs['type']);
				$updateSite->location       = trim($updateServer);
				$updateSite->enabled        = 1;

				$updateSites[] = $updateSite;
			}
		}

		// No udpate sites to add. Do nothing.
		if ($updateSites === array())
		{
			return true;
		}

		//$currentUpdateSites = $this->getUpdateSitesForExtension();
		// TODO CHECK UPDATE SITES SIZE

		// Add/Update the update sites.
		foreach ($updateSites as $updateSite)
		{
			$data = array(
				'name'     => $updateSite->name,
				'type'     => $updateSite->type,
				'location' => $updateSite->location,
			);

			$this->load(array('type' => $updateSite->type, 'location' => $updateSite->location));

			if (!$this->update_site_id)
			{
				$data['enabled'] = $updateSite->enabled;
			}

			if (!$this->bind($data) || !$this->check() || !$this->store())
			{
				JLog::add($this->getError(), JLog::WARNING, 'jerror');
			}
		}

		return true;
	}
}
