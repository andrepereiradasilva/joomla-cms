<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Extension.Joomla
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Joomla! master extension plugin.
 *
 * @since  1.6
 */
class PlgExtensionJoomla extends JPlugin
{
	/**
	 * @var    integer Extension Identifier
	 * @since  1.6
	 */
	private $eid = 0;

	/**
	 * @var    JInstaller Installer object
	 * @since  1.6
	 */
	private $installer = null;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Adds an update site to the table if it doesn't exist.
	 *
	 * @param   string   $name      The friendly name of the site
	 * @param   string   $type      The type of site (e.g. collection or extension)
	 * @param   string   $location  The URI for the site
	 * @param   boolean  $enabled   If this site is enabled
	 *
	 * @return  void
	 *
	 * @since   1.6
	 * @deprecated   __DEPLOY_VERSION__ No replacement.
	 */
	private function addUpdateSite($name, $type, $location, $enabled)
	{
		$db = JFactory::getDbo();

		// Look if the location is used already; doesn't matter what type you can't have two types at the same address, doesn't make sense
		$query = $db->getQuery(true)
			->select('update_site_id')
			->from('#__update_sites')
			->where('location = ' . $db->quote($location));
		$db->setQuery($query);
		$update_site_id = (int) $db->loadResult();

		// If it doesn't exist, add it!
		if (!$update_site_id)
		{
			$query->clear()
				->insert('#__update_sites')
				->columns(array($db->quoteName('name'), $db->quoteName('type'), $db->quoteName('location'), $db->quoteName('enabled')))
				->values($db->quote($name) . ', ' . $db->quote($type) . ', ' . $db->quote($location) . ', ' . (int) $enabled);
			$db->setQuery($query);

			if ($db->execute())
			{
				// Link up this extension to the update site
				$update_site_id = $db->insertid();
			}
		}

		// Check if it has an update site id (creation might have faileD)
		if ($update_site_id)
		{
			// Look for an update site entry that exists
			$query->clear()
				->select('update_site_id')
				->from('#__update_sites_extensions')
				->where('update_site_id = ' . $update_site_id)
				->where('extension_id = ' . $this->eid);
			$db->setQuery($query);
			$tmpid = (int) $db->loadResult();

			if (!$tmpid)
			{
				// Link this extension to the relevant update site
				$query->clear()
					->insert('#__update_sites_extensions')
					->columns(array($db->quoteName('update_site_id'), $db->quoteName('extension_id')))
					->values($update_site_id . ', ' . $this->eid);
				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	/**
	 * Handle post extension install update sites
	 *
	 * @param   JInstaller  $installer    Installer object
	 * @param   integer     $extensionId  Extension Identifier
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onExtensionAfterInstall($installer, $extensionId)
	{
		if (!$extensionId)
		{
			return;
		}

		$this->installer = $installer;
		$this->eid       = $extensionId;

		// Handle any update sites
		$this->processUpdateSites();
	}

	/**
	 * Handle extension uninstall
	 *
	 * @param   JInstaller  $installer    Installer instance
	 * @param   integer     $extensionId  Extension id
	 * @param   boolean     $result       Installation result
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onExtensionAfterUninstall($installer, $extensionId, $result)
	{
		// If we have a valid extension ID and the extension was successfully uninstalled wipe out any update sites for it
		if (!$extensionId || !$result)
		{
			return;
		}

		// Get the table object for the update site.
		$table = JTable::getInstance('Updatesite');

		// Set the extension Id for this update site.
		$table->setExtensionId($extensionId);

		// Delete all update sites for this extension.
		foreach ($table->getUpdateSitesForExtension() as $updatesSite)
		{
			if (!$table->delete($updatesSite->update_site_id))
			{
				JLog::add($table->getError(), JLog::WARNING, 'jerror');
			}
		}
	}

	/**
	 * After update of an extension
	 *
	 * @param   JInstaller  $installer    Installer object
	 * @param   integer     $extensionId  Extension identifier
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onExtensionAfterUpdate($installer, $extensionId)
	{
		if (!$extensionId)
		{
			return;
		}

		$this->installer = $installer;
		$this->eid       = $extensionId;

		// Handle any update sites
		$this->processUpdateSites();
	}

	/**
	 * Processes the list of update sites for an extension.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	private function processUpdateSites()
	{
		// Get the table object for the update site.
		$table = JTable::getInstance('Updatesite');

		// Set the extension Id for this update site and them add the update servers.
		$table->setExtensionId($this->eid);
		$table->addUpdateSites($this->installer->getManifest()->updateservers);
	}
}
