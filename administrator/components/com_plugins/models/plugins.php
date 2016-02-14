<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_plugins
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of plugin records.
 *
 * @since  1.6
 */
class PluginsModelPlugins extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JController
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'extension_id', 'a.extension_id',
				'name', 'a.name',
				'folder', 'a.folder',
				'element', 'a.element',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'state', 'a.state',
				'enabled', 'a.enabled',
				'access', 'a.access', 'access_level',
				'ordering', 'a.ordering',
				'client_id', 'a.client_id',
			);
		}

		parent::__construct($config);
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
	 * @since   1.6
	 */
	protected function populateState($ordering = 'folder', $direction = 'asc')
	{
		// Load the filter state.
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
		$this->setState('filter.enabled', $this->getUserStateFromRequest($this->context . '.filter.enabled', 'filter_enabled', '', 'string'));
		$this->setState('filter.folder', $this->getUserStateFromRequest($this->context . '.filter.folder', 'filter_folder', null, 'cmd'));
		$this->setState('filter.element', $this->getUserStateFromRequest($this->context . '.filter.element', 'filter_element', '', 'string'));
		$this->setState('filter.access', $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', null, 'int'));

		// Load the parameters.
		$params = JComponentHelper::getParams('com_plugins');
		$this->setState('params', $params);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string    A store id.
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.folder');
		$id .= ':' . $this->getState('filter.element');
		$id .= ':' . $this->getState('filter.access');

		return parent::getStoreId($id);
	}

	/**
	 * Returns an object list.
	 *
	 * @param   JDatabaseQuery  $query       A database query object.
	 * @param   integer         $limitstart  Offset.
	 * @param   integer         $limit       The number of records.
	 *
	 * @return  array
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$search    = $this->getState('filter.search');
		$listOrder = $this->getState('list.ordering', 'folder');
		$listDirn  = $this->getState('list.direction', 'ASC');

		// Plugin name (name) and Search (name) need to translate the results before sorting.
		if ($listOrder == 'name' || (!empty($search) && stripos($search, 'id:') !== 0))
		{
			$this->_db->setQuery($query);
			$result = $this->_db->loadObjectList();
			$this->translate($result);

			if (!empty($search))
			{
				$escapedSearchString = $this->refineSearchStringToRegex($search, '/');

				foreach ($result as $i => $item)
				{
					if (!preg_match("/$escapedSearchString/i", $item->name))
					{
						unset($result[$i]);
					}
				}
			}

			$result = ArrayHelper::sortObjects($result, $listOrder, strtolower($listDirn) == 'desc' ? -1 : 1, true, true);

			$total = count($result);
			$this->cache[$this->getStoreId('getTotal')] = $total;

			if ($total < $limitstart)
			{
				$limitstart = 0;
				$this->setState('list.start', 0);
			}

			return array_slice($result, $limitstart, $limit ? $limit : null);
		}
		// For the other sorting options we can use query order by.
		else
		{
			// For ordering use the folder ascending and then ordering.
			if ($listOrder == 'ordering')
			{
				$query->order($this->_db->quoteName('a.folder') . ' ASC')
					->order($this->_db->quoteName($listOrder) . ' ' . $this->_db->escape($listDirn));
				$listOrder = 'a.ordering';
			}
			// For folder use the folder and then ordering ascending.
			elseif ($listOrder == 'folder')
			{
				$query->order($this->_db->quoteName($listOrder) . ' ' . $this->_db->escape($listDirn))
					->order($this->_db->quoteName('a.ordering') . ' ASC');
			}
			// For other order options use the standard ordering.
			else
			{
				$query->order($this->_db->quoteName($listOrder) . ' ' . $this->_db->escape($listDirn));
			}

			$result = parent::_getList($query, $limitstart, $limit);
			$this->translate($result);

			return $result;
		}
	}

	/**
	 * Translate a list of objects.
	 *
	 * @param   array  &$items  The array of objects.
	 *
	 * @return  array The array of translated objects.
	 */
	protected function translate(&$items)
	{
		$lang = JFactory::getLanguage();

		foreach ($items as &$item)
		{
			$source = JPATH_PLUGINS . '/' . $item->folder . '/' . $item->element;
			$extension = 'plg_' . $item->folder . '_' . $item->element;
			$lang->load($extension . '.sys', JPATH_ADMINISTRATOR, null, false, true)
				|| $lang->load($extension . '.sys', $source, null, false, true);
			$item->name = JText::_($item->name);
		}
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.extension_id , a.name, a.element, a.folder, a.checked_out, a.checked_out_time, ' .
				'a.enabled, a.access, a.ordering'
			)
		)
			->from($db->quoteName('#__extensions', 'a'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));

		// Join over the users for the checked out user.
		$query->select($db->quoteName('uc.name', 'editor'))
			->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

		// Join over the asset groups.
		$query->select($db->quoteName('ag.title', 'access_level'))
			->join('LEFT', $db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'));

		// Only active plugins.
		$query->where($db->quoteName('a.state') . ' >= 0');

		// Filter by published state.
		$published = $this->getState('filter.enabled');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('a.enabled') . ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where($db->quoteName('a.enabled') . ' IN (0, 1)');
		}

		// Filter by folder.
		if ($folder = $this->getState('filter.folder'))
		{
			$query->where($db->quoteName('a.folder') . ' = ' . $db->quote($folder));
		}

		// Filter by element.
		if ($element = $this->getState('filter.element'))
		{
			$query->where($db->quoteName('a.element') . ' = ' . $db->quote($element));
		}

		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$query->where($db->quoteName('a.access') . ' = ' . (int) $access);
		}

		// Filter by search for id.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->quoteName('a.extension_id') . ' = ' . (int) substr($search, 3));
			}
		}

		return $query;
	}
}
