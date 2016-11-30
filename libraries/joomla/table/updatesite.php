<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
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
	protected $extensionId = 0;

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
		if (trim($this->name) == '' || trim($this->location) == '')
		{
			$this->setError(JText::_('JLIB_DATABASE_ERROR_MUSTCONTAIN_A_TITLE_EXTENSION'));

			return false;
		}

		return true;
	}

	/**
	 * Overloaded check function
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JTable::store()
	 * @since   __DEPLOY_VERSION__
	 */
	public function store($updateNulls = false)
	{
		// Check if update site already exists.
		$newUpdateSite = $table->update_site_id ? true : false;

		// Check for valid name
		$result = parent::store($updateNulls);

		// If it's a new update site we create the update site extension.
		if ($newUpdateSite)
		{
			$query = $this->_db->getQuery(true)
				->insert($this->_db->qn('#__update_sites_extensions'))
				->columns(array($this->_db->qn('update_site_id'), $this->_db->qn('extension_id')))
				->values($this->update_site_id . ', ' . $this->extensionId);

			$this->_db-setQuery($query)->execute();
		}

		return $result;
	}

	/**
	 * Set extension id
	 *
	 * @param   integer  $extensionId  The extension Id.
	 *
	 * @return  void.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function setExtensionId($extensionId = 0)
	{
		$this->extensionId = (int) $extensionId;
	}
}
