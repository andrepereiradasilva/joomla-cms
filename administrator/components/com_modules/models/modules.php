<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Modules Component Module Model
 *
 * @since  1.5
 */
class ModulesModelModules extends JModelList
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
				'id', 'a.id',
				'title', 'a.title',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'published', 'a.published', 'state',
				'access', 'a.access', 'access_level',
				'ordering', 'a.ordering',
				'module', 'a.module',
				'language', 'a.language', 
				'language_title', 'l.title',
				'publish_up', 'a.publish_up',
				'publish_down', 'a.publish_down',
				'client_id', 'a.client_id',
				'position', 'a.position',
				'pages',
				'name', 'e.name',
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
	protected function populateState($ordering = 'a.position', $direction = 'asc')
	{
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
		$this->setState('filter.access', $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', '', 'string'));
		$this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string'));
		$this->setState('filter.position', $this->getUserStateFromRequest($this->context . '.filter.position', 'filter_position', '', 'string'));
		$this->setState('filter.module', $this->getUserStateFromRequest($this->context . '.filter.module', 'filter_module', '', 'string'));
		$this->setState('filter.language', $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string'));

		// Special case for client id.
		$clientId = (int) $this->getUserStateFromRequest($this->context . '.client_id', 'client_id', 0, 'int');

		// Only 0 or 1 are allowed for client_id, also, modal view should return only front end modules.
		if (!in_array($clientId, array (0, 1)) || JFactory::getApplication()->input->get('layout') == 'modal')
		{
			$clientId = 0;
		}

		$this->setState('client_id', $clientId);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_modules');
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
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.position');
		$id .= ':' . $this->getState('filter.module');
		$id .= ':' . $this->getState('filter.language');
		$id .= ':' . $this->getState('client_id');

		return parent::getStoreId($id);
	}

	/**
	 * Returns an object list
	 *
	 * @param   string  $query       The query
	 * @param   int     $limitstart  Offset
	 * @param   int     $limit       The number of records
	 *
	 * @return  array
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$listOrder = $this->getState('list.ordering', 'a.position');
		$listDirn  = $this->getState('list.direction', 'ASC');

		// Pages (pages) and Type (name) need to translate the results before sorting.
		if (in_array($listOrder, array('pages', 'name')))
		{
			$this->_db->setQuery($query);
			$result = $this->_db->loadObjectList();
			$this->translate($result);
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
			// For ordering use the position ascending and then ordering.
			if ($listOrder === 'a.ordering')
			{
				$query->order($this->_db->quoteName('a.position') . ' ASC')
					->order($this->_db->quoteName('a.ordering') . ' ' . $this->_db->escape($listDirn));
			}
			// For position use the position and then ordering ascending.
			elseif ($listOrder === 'a.position')
			{
				$query->order($this->_db->quoteName('a.position') . ' ' . $this->_db->escape($listDirn))
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
	 * Translate a list of objects
	 *
	 * @param   array  &$items  The array of objects
	 *
	 * @return  array The array of translated objects
	 */
	protected function translate(&$items)
	{
		$lang = JFactory::getLanguage();
		$client = $this->getState('client_id') ? 'administrator' : 'site';

		foreach ($items as $item)
		{
			$extension = $item->module;
			$source = constant('JPATH_' . strtoupper($client)) . "/modules/$extension";
			$lang->load("$extension.sys", constant('JPATH_' . strtoupper($client)), null, false, true)
				|| $lang->load("$extension.sys", $source, null, false, true);
			$item->name = JText::_($item->name);

			if (is_null($item->pages))
			{
				$item->pages = JText::_('JNONE');
			}
			elseif ($item->pages < 0)
			{
				$item->pages = JText::_('COM_MODULES_ASSIGNED_VARIES_EXCEPT');
			}
			elseif ($item->pages > 0)
			{
				$item->pages = JText::_('COM_MODULES_ASSIGNED_VARIES_ONLY');
			}
			else
			{
				$item->pages = JText::_('JALL');
			}
		}
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$app = JFactory::getApplication();

		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.id, a.title, a.note, a.position, a.module, a.language, ' .
				'a.checked_out, a.checked_out_time, a.published AS published, e.enabled AS enabled, a.access, a.ordering, a.publish_up, a.publish_down'
			)
		);
		$query->from($db->quoteName('#__modules', 'a'));

		// Join over the language
		$query->select($db->quoteName('l.title', 'language_title'))
			->select($db->quoteName('l.image', 'language_image'))
			->join('LEFT', $db->quoteName('#__languages', 'l') . ' ON ' . $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

		// Join over the users for the checked out user.
		$query->select($db->quoteName('uc.name', 'editor'))
			->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

		// Join over the asset groups.
		$query->select($db->quoteName('ag.title', 'access_level'))
			->join('LEFT', $db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'));

		// Join over the module menus
		$query->select('MIN(mm.menuid) AS pages')
			->join('LEFT', $db->quoteName('#__modules_menu', 'mm') . ' ON ' . $db->quoteName('mm.moduleid') . ' = ' . $db->quoteName('a.id'));

		// Join over the extensions
		$query->select($db->quoteName('e.name', 'name'))
			->join('LEFT', $db->quoteName('#__extensions', 'e') . ' ON ' . $db->quoteName('e.element') . ' = ' . $db->quoteName('a.module'))
			->group(
				'a.id, a.title, a.note, a.position, a.module, a.language, a.checked_out, ' .
				'a.checked_out_time, a.published, a.access, a.ordering, l.title, uc.name, ag.title, e.name, ' .
				'l.lang_code, uc.id, ag.id, mm.moduleid, e.element, a.publish_up, a.publish_down, e.enabled'
			);

		// Show only selected client.
		$clientId = (int) $this->getState('client_id');
		$query->where('(' . $db->quoteName('a.client_id') . ' = ' . $clientId . ' AND ' . $db->quoteName('e.client_id') . ' = ' . $clientId . ')');

		// Process select filters.
		$access   = $this->getState('filter.access');
		$state    = $this->getState('filter.state');
		$position = $this->getState('filter.position');
		$module   = $this->getState('filter.module');
		$language = $this->getState('filter.language');

		// Modal view should return only front end modules.
		if ($app->input->get('layout') == 'modal')
		{
			$state = 1;
		}

		// Filter by access level.
		if ($access)
		{
			$query->where($db->quoteName('a.access') . ' = ' . (int) $access);
		}

		// Filter by published state.
		if (is_numeric($state))
		{
			$query->where($db->quoteName('a.published') . ' = ' . (int) $state);
		}
		elseif ($state === '')
		{
			$query->where($db->quoteName('a.published') . ' IN (0, 1)');
		}

		// Filter by position.
		if ($position && $position != 'none')
		{
			$query->where($db->quoteName('a.position') . ' = ' . $db->quote($position));
		}
		elseif ($position == 'none')
		{
			$query->where($db->quoteName('a.position') . ' = ' . $db->quote(''));
		}

		// Filter by module.
		if ($module)
		{
			$query->where($db->quoteName('a.module') . ' = ' . $db->quote($module));
		}

		// Filter on the language.
		// Modal view should return only specific language and ALL.
		if ($app->input->get('layout') == 'modal')
		{
			if ($app->isSite() && JLanguageMultilang::isEnabled())
			{
				$query->where($db->quoteName('a.language') . ' IN (' . $db->quote(JFactory::getLanguage()->getTag()) . ', ' . $db->quote('*') . ')');
			}
		}
		elseif ($language)
		{
			$query->where($db->quoteName('a.language') . ' = ' . $db->quote($language));
		}

		// Process search filter.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true)) . '%');
				$query->where('(' . $db->quoteName('a.title') . ' LIKE ' . $search . ' OR ' . $db->quoteName('a.note') . ' LIKE ' . $search . ')');
			}
		}

		return $query;
	}
}
