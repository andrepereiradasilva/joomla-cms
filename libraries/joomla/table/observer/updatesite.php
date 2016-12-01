<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Abstract class defining methods that can be
 * implemented by an Observer class of a JTable class (which is an Observable).
 * Attaches $this Observer to the $table in the constructor.
 * The classes extending this class should not be instanciated directly, as they
 * are automatically instanciated by the JObserverMapper
 *
 * @since  __DEPLOY_VERSION__
 */
class JTableObserverUpdatesite extends JTableObserver
{
	/**
	 * Creates the associated observer instance and attaches it to the $observableObject
	 *
	 * @param   JObservableInterface  $observableObject  The subject object to be observed
	 * @param   array                 $params            Array of patams
	 *
	 * @return  JTableObserverUpdatesite
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function createObserver(JObservableInterface $observableObject, $params = array())
	{
		return new self($observableObject);
	}

	/**
	 * Post-processor for $table->store($updateNulls)
	 *
	 * @param   boolean  &$result  The result of the load
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAfterStore(&$result)
	{
		if ($result && $extensionId = $this->table->getExtensionId())
		{
			$db       = $this->table->getDbo();
			$tableKey = $this->table->getKeyName();

			// If we are inserting a new update site we need to insert it in update site extension table too.
			if ($updateSiteId = $db->insertid())
			{
				$query = $db->getQuery(true)
					->insert($db->qn('#__update_sites_extensions'))
					->columns(array($db->qn($tableKey), $db->qn('extension_id')))
					->values($updateSiteId . ', ' . $extensionId);

				$db->setQuery($query)->execute();
			}
		}
	}

	/**
	 * Post-processor for $table->delete($pk)
	 *
	 * @param   mixed  $pk  An optional primary key value to delete. If not set the instance property value is used.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAfterDelete($pk)
	{
		// Ensure is an array.
		if (!is_array($pk))
		{
			$pk = array($pk);
		}

		$db       = $this->table->getDbo();
		$tableKey = $this->table->getKeyName();

		// Delete updates sites extensions.
		$query = $db->getQuery(true)
			->delete($db->qn('#__update_sites_extensions'))
			->where($db->qn($tableKey) . ' IN (' . implode(',', $pk) .  ')');

		$db->setQuery($query)->execute();

		// Delete updates.
		$query = $db->getQuery(true)
			->delete($db->qn('#__updates'))
			->where($db->qn($tableKey) . ' IN (' . implode(',', $pk) .  ')');

		$db->setQuery($query)->execute();
	}
}
