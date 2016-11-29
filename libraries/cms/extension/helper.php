<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Extension
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

/**
 * Extension helper class
 *
 * @since  __DEPLOY_VERSION__
 */
class JExtensionHelper
{
	/**
	 * The extensions list cache in tree format.
	 *
	 * @var    array
	 * @since  __DEPLOY_VERSION__
	 */
	private static $extensions = array();

	/**
	 * Get extensions by element key.
	 *
	 * @param   string  $type     The extension type.
	 * @param   string  $folder   The extension folder (if any).
	 * @param   string  $element  The extension element.
	 *
	 * @return  stdClass|boolean  Extension stdClass object if exists. False otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getExtension($type, $folder = null, $element)
	{
		static::preload();

		// Getting an specific plugin.
		if ($type === 'plugin' && isset(static::$extensions[$type], static::$extensions[$type][$folder], static::$extensions[$type][$folder][$element]))
		{
			$params = static::$extensions[$type][$folder][$element]->params;

			static::$extensions[$type][$folder][$element]->params = is_string($params) ? new Registry($params) : $params;

			return static::$extensions[$type][$folder][$element];
		}

		// Getting an specific component, library or language.
		if ($folder === null && isset(static::$extensions[$type], static::$extensions[$type][$element]))
		{
			$params = static::$extensions[$type][$element]->params;

			static::$extensions[$type][$element]->params = is_string($params) ? new Registry($params) : $params;

			return static::$extensions[$type][$element];
		}

		// Extension doesn't exist.
		return false;
	}

	/**
	 * Checks if an extension is enabled.
	 *
	 * @param   string  $type     The extension type.
	 * @param   string  $folder   The extension folder (if any).
	 * @param   string  $element  The extension element.
	 *
	 * @return  boolean  True if extension is enabled, false otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function isEnabled($type, $folder = null, $element)
	{
		static::preload();

		$extension = static::getExtension($type, $folder, $element);

		return (boolean) ($extension && $extension->enabled);
	}

	/**
	 * Checks if a extension is installed.
	 *
	 * @param   string  $type     The extension type.
	 * @param   string  $folder   The extension folder (if any).
	 * @param   string  $element  The extension element.
	 *
	 * @return  boolean  True if extension is installed, false otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function isInstalled($type, $folder = null, $element)
	{
		static::preload();

		return (boolean) static::getExtension($type, $folder, $element);
	}

	/**
	 * Gets the parameter object for the extension.
	 *
	 * @param   string  $type     The extension type.
	 * @param   string  $folder   The extension folder (if any).
	 * @param   string  $element  The extension element.
	 *
	 * @return  Registry  A Registry object. If extension does not exist a empty Registry object.
	 *
	 * @see     Registry
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getParams($type, $folder = null, $element)
	{
		static::preload();

		$extension = static::getExtension($type, $folder, $element);

		return $extension ? $extension->params : new Registry;
	}

	/**
	 * Save the parameters object for an extension.
	 *
	 * @param   string    $type     The extension type.
	 * @param   string    $folder   The extension folder (if any).
	 * @param   string    $element  The extension element.
	 * @param   Registry  $params   Params to save
	 *
	 * @return  boolean|Registry  A Registry object. False if extension does not exist.
	 *
	 * @see     Registry
	 * @since   __DEPLOY_VERSION__
	 */
	public static function saveParams($type, $folder = null, $element, Joomla\Registry\Registry $params)
	{
		static::preload();

		$extension = static::getExtension($type, $folder, $element);

		// No extension installed, or invalid parameters sent. Return false.
		if (!$extension || !$type || !$element || !$params)
		{
			return false;
		}

		// Save params in database.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params') . ' = ' . $db->q($params->toString()))
			->where($db->qn('type') . ' = ' . $db->q($type))
			->where($db->qn('element') . ' = ' . $db->q($element));

		if ($folder !== null)
		{
			$query->where($db->qn('folder') . ' = ' . $db->q($folder));
		}

		$db->setQuery($query);

		$result = $db->execute();

		// No result from database, return false.
		if (!$result)
		{
			return false;
		}

		// Update params in static variable.

		// Plugin.
		if ($type === 'plugin' && isset(static::$extensions[$type], static::$extensions[$type][$folder], static::$extensions[$type][$folder][$element]))
		{
			static::$extensions[$type][$folder][$element]->params = is_string($params) ? new Registry($params) : $params;
		}
		// Component, libraries and languages.
		elseif (isset(static::$extensions[$type], static::$extensions[$type][$element]))
		{
			static::$extensions[$type][$element]->params = is_string($params) ? new Registry($params) : $params;
		}

		static::$extensionsByKey[$type . '.' . $folder . '.' . $element]->params = is_string($params) ? new Registry($params) : $params;

		return $result;
	}

	/**
	 * Load installed extensions.
	 *
	 * @return  boolean|Exception  True on success, RuntimeException otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected static function preload()
	{
		// Already loaded.
		if (count(static::$extensions) !== 0)
		{
			return true;
		}

		/** @var JCacheControllerCallback $cache */
		$cache = JFactory::getCache('_system', '');

		!JDEBUG ?: JProfiler::getInstance('Application')->mark('Before JExtensionHelper::preload()');

		// Get all the extensions.
		if (!static::$extensions = $cache->get('extensions'))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->qn('extension_id', 'id'))
				->select($db->qn(array('name', 'element', 'type', 'folder', 'enabled', 'params', 'access', 'state', 'client_id', 'ordering')))
				->from($db->qn('#__extensions'))
				->where($db->qn('type') . ' IN (' . $db->q('component') . ',' . $db->q('language') . ',' . $db->q('library') . ')')
				->orWhere($db->qn('type') . ' = ' . $db->q('template') . ' AND ' . $db->qn('enabled') . ' = 1')
				->orWhere($db->qn('type') . ' = ' . $db->q('plugin') . ' AND ' . $db->qn('enabled') . ' = 1 AND ' . $db->qn('state') . ' IN (0,1)');

			$extensions = $db->setQuery($query)->loadObjectList();

			foreach ($extensions as $extension)
			{
				if ($extension->type === 'plugin')
				{
					static::$extensions[$extension->type][$extension->folder][$extension->element] = $extension;
				}
				else
				{
					static::$extensions[$extension->type][$extension->element] = $extension;
				}
			}

			$cache->store(static::$extensions, 'extensions');
		}

		!JDEBUG ?: JProfiler::getInstance('Application')->mark('After JExtensionHelper::preload()');

		// Loaded with success.
		if (static::$extensions && static::$extensions !== array())
		{
			return true;
		}

		// This exception is not translated because the extensions (including languages have not been loaded yet).
		throw new RuntimeException('Error loading extensions.', 500);
	}

	/**
	 * Get extensions by type (and folder, if needed for plugins).
	 *
	 * @param   string  $type             The extension type.
	 * @param   string  $folder           The extension folder (if any).
	 *
	 * @return  array|boolean  Array of all chosen extension Registry objects (all if null).
	 *                         False if the extension with that element key does not exist.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getExtensions($type = null, $folder = null)
	{
		static::preload();

		// Return all.
		if ($type === null)
		{
			return static::$extensions;
		}

		// Return all plugins in a plugin folder.
		if ($folder !== null && isset(static::$extensions[$type], static::$extensions[$type][$folder]))
		{
			return static::$extensions[$type][$folder];
		}

		// Return all components, libraries or languages or groups of plugins.
		if ($folder === null && isset(static::$extensions[$type]))
		{
			return static::$extensions[$type];
		}

		return false;
	}
}
