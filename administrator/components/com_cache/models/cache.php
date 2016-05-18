<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_cache
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Cache Model
 *
 * @since  1.6
 */
class CacheModelCache extends JModelList
{
	/**
	 * An Array of CacheItems indexed by cache group ID
	 *
	 * @var Array
	 */
	protected $_data = array();

	/**
	 * Group total
	 *
	 * @var integer
	 */
	protected $_total = null;

	/**
	 * Pagination object
	 *
	 * @var object
	 */
	protected $_pagination = null;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   3.5
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'group',
				'count',
				'size',
				'title',
				'type',
				'cliend_id',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Field for ordering.
	 * @param   string  $direction  Direction of ordering.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = 'title', $direction = 'asc')
	{
		// Load the filter state.
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
		$this->setState('filter.type', $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '', 'cmd'));

		// Special case for client id.
		$clientId = (int) $this->getUserStateFromRequest($this->context . '.client_id', 'client_id', 0, 'int');
		$clientId = (!in_array($clientId, array (0, 1))) ? 0 : $clientId;
		$this->setState('client_id', $clientId);

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
	 * @return  string  A store id.
	 *
	 * @since   3.5
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id	.= ':' . $this->getState('client_id');
		$id	.= ':' . $this->getState('filter.search');
		$id	.= ':' . $this->getState('filter.type');

		return parent::getStoreId($id);
	}

	/**
	 * Method to get cache data
	 *
	 * @return array
	 */
	public function getData()
	{
		if (empty($this->_data))
		{
			$cache              = $this->getCache();
			$data               = $cache->getAll();
			$lang               = JFactory::getLanguage();
			$clientId           = (int) $this->getState('client_id');
			$clientPath         = $clientId ? JPATH_ADMINISTRATOR : JPATH_SITE;
			$fallbackClientPath = $clientId ? JPATH_SITE : JPATH_ADMINISTRATOR;

			if ($data && count($data) > 0)
			{
				// Translate
				foreach ($data as $key => $cacheItem)
				{
					switch ($cacheItem->group)
					{
						case '_system':
							$data[$key]->type  = JText::_('COM_CACHE_TYPE_OTHER');
							$data[$key]->title = JText::_('COM_CACHE_GROUP_TYPE_SYSTEM');
							break;

						case 'page':
							$data[$key]->type  = JText::_('COM_CACHE_TYPE_OTHER');
							$data[$key]->title = JText::_('COM_CACHE_GROUP_TYPE_PAGE');
							break;

						default:
							$data[$key]->type = JText::_('COM_CACHE_TYPE_LIBRARY');

							// For components
							if (preg_match('#^com_#', $cacheItem->group))
							{
								$data[$key]->type = JText::_('COM_CACHE_TYPE_COMPONENT');
								$searchPaths      = array(
									$clientPath . '/components/' . $cacheItem->group,
									$clientPath,
									$fallbackClientPath . '/components/' . $cacheItem->group,
									$fallbackClientPath,
								);
							}

							// For templates
							else if (preg_match('#^tpl_#', $cacheItem->group))
							{
								$data[$key]->type = JText::_('COM_CACHE_TYPE_TEMPLATE');
								$searchPaths      = array(
									$clientPath . '/templates/' . $templateParts[1],
									$clientPath,
									$fallbackClientPath . '/templates/' . $templateParts[1],
									$fallbackClientPath,
								);
							}

							// For plugins
							else if (preg_match('#^plg_#', $cacheItem->group))
							{
								$data[$key]->type = JText::_('COM_CACHE_TYPE_PLUGIN');
								$pluginParts      = explode('_', $cacheItem->group);
								$searchPaths      = array(
									JPATH_SITE . '/plugins/' . $pluginParts[1] . '/' . $pluginParts[2],
									JPATH_SITE,
								);
							}

							// For modules
							else if (preg_match('#^mod_#', $cacheItem->group))
							{
								$data[$key]->type = JText::_('COM_CACHE_TYPE_MODULE');
								$searchPaths      = array(
									$clientPath . '/modules/' . $cacheItem->group,
									$clientPath,
									$fallbackClientPath . '/modules/' . $cacheItem->group,
									$fallbackClientPath,
								);
							}

							// For other
							else
							{
								$data[$key]->type = JText::_('COM_CACHE_TYPE_LIBRARY');
								$searchPaths      = array(
									$clientPath,
									$fallbackClientPath,
								);
							}

							// Try to load the title from the client language path (/languages/).
							$lang->load($cacheItem->group . '.sys', $clientPath, null, false, true);
							$title = JText::_($cacheItem->group);

							// If we still don't have a title try to load from the fallback path.
							foreach ($searchPaths as $searchPath)
							{
								$lang->load($cacheItem->group . '.sys', $searchPath, null, false, true);
								$title = JText::_($cacheItem->group);

								if ($title != $cacheItem->group)
								{
									break;
								}
							}

							// If we still don't have a title mark as unknown.
							if ($title == $cacheItem->group)
							{
								$title = JText::sprintf('COM_CACHE_GROUP_TYPE_UNKNOWN', $cacheItem->group);
							}

							// Add title to object.
							$data[$key]->title = $title;

							break;
					}
				}

				// Process filter by type.
				if ($type = $this->getState('filter.type'))
				{
					foreach ($data as $key => $cacheItem)
					{
						if (strtolower($cacheItem->type) != strtolower($type))
						{
							unset($data[$key]);
							continue;
						}
					}
				}

				// Process filter by search term.
				if ($search = $this->getState('filter.search'))
				{
					foreach ($data as $key => $cacheItem)
					{
						if (stripos($cacheItem->group, $search) === false
							&& stripos($cacheItem->title, $search) === false
							&& stripos($cacheItem->type, $search) === false)
						{
							unset($data[$key]);
							continue;
						}
					}
				}

				// Process ordering.
				$listOrder = $this->getState('list.ordering', 'title');
				$listDirn  = $this->getState('list.direction', 'ASC');

				$this->_data = ArrayHelper::sortObjects($data, $listOrder, strtolower($listDirn) === 'desc' ? -1 : 1, true, true);

				// Process pagination.
				$limit = (int) $this->getState('list.limit', 25);

				if ($limit !== 0)
				{
					$start = (int) $this->getState('list.start', 0);

					return array_slice($this->_data, $start, $limit);
				}
			}
			else
			{
				$this->_data = array();
			}
		}

		return $this->_data;
	}

	/**
	 * Method to get cache instance.
	 *
	 * @return object
	 */
	public function getCache()
	{
		$conf = JFactory::getConfig();

		$options = array(
			'defaultgroup' => '',
			'storage'      => $conf->get('cache_handler', ''),
			'caching'      => true,
			'cachebase'    => ($this->getState('client_id') === 1) ? JPATH_ADMINISTRATOR . '/cache' : $conf->get('cache_path', JPATH_SITE . '/cache')
		);

		$cache = JCache::getInstance('', $options);

		return $cache;
	}

	/**
	 * Method to get client data.
	 *
	 * @return array
	 *
	 * @deprecated  4.0  No replacement.
	 */
	public function getClient()
	{
		return JApplicationHelper::getClientInfo($this->getState('client_id', 0));
	}

	/**
	 * Get the number of current Cache Groups.
	 *
	 * @return  int
	 */
	public function getTotal()
	{
		if (empty($this->_total))
		{
			$this->_total = count($this->getData());
		}

		return $this->_total;
	}

	/**
	 * Method to get a pagination object for the cache.
	 *
	 * @return  integer
	 */
	public function getPagination()
	{
		if (empty($this->_pagination))
		{
			$this->_pagination = new JPagination($this->getTotal(), $this->getState('list.start'), $this->getState('list.limit'));
		}

		return $this->_pagination;
	}

	/**
	 * Clean out a cache group as named by param.
	 * If no param is passed clean all cache groups.
	 *
	 * @param   string  $group  Cache group name.
	 *
	 * @return  boolean  True on success, false otherwise
	 */
	public function clean($group = '')
	{
		return $this->getCache()->clean($group);
	}

	/**
	 * Purge an array of cache groups.
	 *
	 * @param   array  $array  Array of cache group names.
	 *
	 * @return  array  Array with errors, if they exist.
	 */
	public function cleanlist($array)
	{
		$errors = array();

		foreach ($array as $group)
		{
			if (!$this->clean($group))
			{
				$errors[] = $group;
			}
		}

		return $errors;
	}

	/**
	 * Purge all cache items.
	 *
	 * @return  boolean  True if successful; false otherwise.
	 */
	public function purge()
	{
		return JFactory::getCache('')->gc();
	}
}
