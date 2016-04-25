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
use Joomla\Utilities\ArrayHelper;

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
				'language',
				'language_tag',
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
		$languages  = JLanguage::getKnownLanguages($clientPath);
		$strings    = array();

		// Search main language directory for subdirectories
		foreach (glob($clientPath . '/language/overrides/*.override.ini', GLOB_NOSORT) as $filename)
		{
			// Check if language is installed.
			$fileParts   = pathinfo($filename);
			$languageTag = str_replace('.override', '', $fileParts['filename']);

			if (!isset($languages[$languageTag]))
			{
				continue;
			}

			// Filter the loaded strings according to the search box.
			$language = $this->getState('filter.language');

			if (!empty($language) && $language != $languageTag)
			{
				continue;
			}

			// Parse the override.ini file in order to get the keys and strings.
			$overrideStrings = LanguagesHelper::parseFile($filename);

			// Delete the override.ini file if empty.
			if (file_exists($filename) && empty($overrideStrings))
			{
				JFile::delete($filename);
			}
			else
			{
				// Create array of override objects.
				foreach($overrideStrings as $key => $text)
				{
					$override               = new stdClass();
					$override->key          = $key;
					$override->text         = $text;
					$override->language     = $languages[$languageTag]['name'];
					$override->language_tag = $languageTag;
					$strings[] = $override;
				}
			}
		}

		// Filter the loaded strings according to the search box.
		if ($search = $this->getState('filter.search'))
		{
			$search = preg_quote($search, '~');
			$matchkeys = preg_grep('~' . $search . '~i', ArrayHelper::getColumn($strings, 'key'));
			$matchvals = preg_grep('~' . $search . '~i', ArrayHelper::getColumn($strings, 'text'));
			$strings = array_intersect_key($strings, array_replace($matchkeys, $matchvals));
		}

		// Consider the ordering
		$listOrder = $this->getState('list.ordering', 'key');
		$listDirn  = $this->getState('list.direction', 'ASC');

		$strings = ArrayHelper::sortObjects($strings, $listOrder, strtolower($listDirn) == 'desc' ? -1 : 1, true, true);

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

		// Sets the filters.
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
		$this->setState('filter.language', $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'cmd'));

		// Special case for the client id.
		$clientId = (int) $this->getUserStateFromRequest($this->context . '.client_id', 'client_id', 0, 'int');
		$clientId = (!in_array($clientId, array (0, 1))) ? 0 : $clientId;
		$this->setState('client_id', $clientId);

		// Add filters to the session because they won't be stored there by 'getUserStateFromRequest' if they aren't in the current request.
		$app->setUserState($this->context . '.client_id', $clientId);

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

		$toDelete = array();

		// Check which strings in which language shall be deleted.
		foreach ($cids as $key)
		{
			preg_match('#\[([a-z]{2,3}\-[A-Z]{2,3})\](.*)#', $key, $matches);
			$toDelete[$matches[1]][] = $matches[2];
		}

		// For each language delete the files.
		foreach ($toDelete as $languageTag => $items)
		{
			$filename = $clientPath . '/language/overrides/' . $languageTag . '.override.ini';

			if (!file_exists($filename))
			{
				continue;
			}

			// Parse the override.ini file in order to get the keys and strings.
			$strings = LanguagesHelper::parseFile($filename);

			if (!empty($strings))
			{
				continue;
			}

			// Unset strings that shall be deleted.
			foreach ($items as $key)
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
			echo $filename;

			if (!JFile::write($filename, $reg))
			{
				return false;
			}
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
