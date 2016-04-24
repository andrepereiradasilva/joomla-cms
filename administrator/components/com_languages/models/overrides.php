<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * Languages Overrides Model
 *
 * @since  2.5
 */
class LanguagesModelOverrides extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since		2.5
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'key',
				'text',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Retrieves the overrides data
	 *
	 * @param   boolean  $all  True if all overrides shall be returned without considering pagination, defaults to false
	 *
	 * @return  array  Array of objects containing the overrides of the override.ini file
	 *
	 * @since   2.5
	 */
	public function getOverrides($all = false)
	{
		// Get a storage key.
		$store = $this->getStoreId();

		// Try to load the data from internal storage.
		if (!empty($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$clientPath = (int) $this->getState('client_id') ? JPATH_ADMINISTRATOR : JPATH_SITE;

		// Parse the override.ini file in order to get the keys and strings.
		$filename = $clientPath . '/language/overrides/' . $this->getState('language') . '.override.ini';
		$strings = LanguagesHelper::parseFile($filename);

		// Delete the override.ini file if empty.
		if (file_exists($filename) && empty($strings))
		{
			JFile::delete($filename);
		}

		// Filter the loaded strings according to the search box.
		if ($search = $this->getState('filter.search'))
		{
			$search = preg_quote($search, '~');
			$matchvals = preg_grep('~' . $search . '~i', $strings);
			$matchkeys = array_intersect_key($strings, array_flip(preg_grep('~' . $search . '~i',  array_keys($strings))));
			$strings = array_merge($matchvals, $matchkeys);
		}

		// Consider the ordering
		$listOrder = $this->getState('list.ordering', 'key');
		$listDirn  = $this->getState('list.direction', 'ASC');

		if ($listOrder == 'text')
		{
			if (strtoupper($listDirn) == 'DESC')
			{
				arsort($strings);
			}
			else
			{
				asort($strings);
			}
		}
		else
		{
			if (strtoupper($listDirn) == 'DESC')
			{
				krsort($strings);
			}
			else
			{
				ksort($strings);
			}
		}

		// Consider the pagination.
		$listLimit = $this->getState('list.limit', 20);

		if (!$all && $listLimit && $this->getTotal() > $listLimit)
		{
			$strings = array_slice($strings, $this->getStart(), $listLimit, true);
		}

		// Add the items to the internal cache.
		$this->cache[$store] = $strings;

		return $this->cache[$store];
	}

	/**
	 * Method to get the total number of overrides.
	 *
	 * @return  int	The total number of overrides.
	 *
	 * @since		2.5
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage
		if (!empty($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Add the total to the internal cache.
		$this->cache[$store] = count($this->getOverrides(true));

		return $this->cache[$store];
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	protected function populateState($ordering = 'key', $direction = 'asc')
	{
		$app = JFactory::getApplication();

		// Sets the search filter.
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));

		// Use default language of frontend for default filter and site client.
		$default = JComponentHelper::getParams('com_languages')->get('site');

		$language_client = $this->getUserStateFromRequest($this->context . '.language_client', 'language_client', $default . '0', 'cmd');

		$clientId = substr($language_client, -1);
		$language = substr($language_client, 0, -1);

		$this->setState('language_client', $language_client);
		$this->setState('client_id', $clientId);
		$this->setState('language', $language);

		// Add filters to the session because they won't be stored there by 'getUserStateFromRequest' if they aren't in the current request.
		$app->setUserState($this->context . '.client_id', $clientId);
		$app->setUserState($this->context . '.language', $language);

		// List state information
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get all found languages of frontend and backend.
	 *
	 * The resulting array has entries of the following style:
	 * <Language Tag>0|1 => <Language Name> - <Client Name>
	 *
	 * @return  array  Sorted associative array of languages.
	 *
	 * @since		2.5
	 */
	public function getLanguages()
	{
		require_once JPATH_COMPONENT . '/helpers/overrides.php';

		return OverridesHelper::getLanguages();
	}

	/**
	 * Method to delete one or more overrides.
	 *
	 * @param   array  $cids  Array of keys to delete.
	 *
	 * @return  integer Number of successfully deleted overrides, boolean false if an error occured.
	 *
	 * @since		2.5
	 */
	public function delete($cids)
	{
		// Check permissions first.
		if (!JFactory::getUser()->authorise('core.delete', 'com_languages'))
		{
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'));

			return false;
		}

		jimport('joomla.filesystem.file');
		require_once JPATH_COMPONENT . '/helpers/languages.php';

		$clientPath = (int) $this->getState('client_id') ? JPATH_ADMINISTRATOR : JPATH_SITE;

		// Parse the override.ini file in oder to get the keys and strings.
		$filename = $clientPath . '/language/overrides/' . $this->getState('language') . '.override.ini';
		$strings = LanguagesHelper::parseFile($filename);

		// Unset strings that shall be deleted
		foreach ($cids as $key)
		{
			if (isset($strings[$key]))
			{
				unset($strings[$key]);
			}
		}

		foreach ($strings as $key => $string)
		{
			$strings[$key] = str_replace('"', '"_QQ_"', $string);
		}

		// Write override.ini file with the left strings.
		$registry = new Registry;
		$registry->loadObject($strings);
		$reg = $registry->toString('INI');

		if (!JFile::write($filename, $reg))
		{
			return false;
		}

		$this->cleanCache();

		return count($cids);
	}

	/**
	 * Removes all of the cached strings from the table.
	 *
	 * @return  boolean result of operation
	 *
	 * @since   3.4.2
	 */
	public function purge()
	{
		$db = JFactory::getDbo();

		// Note: TRUNCATE is a DDL operation
		// This may or may not mean depending on your database
		try
		{
			$db->truncateTable('#__overrider');
		}
		catch (RuntimeException $e)
		{
			return $e;
		}

		JFactory::getApplication()->enqueueMessage(JText::_('COM_LANGUAGES_VIEW_OVERRIDES_PURGE_SUCCESS'));
	}
}
