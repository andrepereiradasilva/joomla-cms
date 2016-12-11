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
	 * @param   string  $element  The extension element.
	 * @param   string  $folder   The extension folder (if any).
	 *
	 * @return  stdClass|boolean  Extension object if exists. False otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getExtension($type, $element, $folder = null)
	{
		static::preload();

		// Getting an specific plugin.
		if ($type === 'plugin' && isset(static::$extensions[$type], static::$extensions[$type][$folder], static::$extensions[$type][$folder][$element]))
		{
			return static::$extensions[$type][$folder][$element];
		}

		// Getting an specific component, library or language.
		if ($folder === null && isset(static::$extensions[$type], static::$extensions[$type][$element]))
		{
			return static::$extensions[$type][$element];
		}

		// Extension doesn't exist.
		return false;
	}

	/**
	 * Checks if an extension is enabled.
	 *
	 * @param   string  $type     The extension type.
	 * @param   string  $element  The extension element.
	 * @param   string  $folder   The extension folder (if any).
	 *
	 * @return  boolean  True if extension is enabled, false otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function isEnabled($type, $element, $folder = null)
	{
		static::preload();

		return (boolean) static::getExtension($type, $folder, $element)->isEnabled();
	}

	/**
	 * Checks if a extension is installed.
	 *
	 * @param   string  $type     The extension type.
	 * @param   string  $element  The extension element.
	 * @param   string  $folder   The extension folder (if any).
	 *
	 * @return  boolean  True if extension is installed, false otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function isInstalled($type, $element, $folder = null)
	{
		static::preload();

		return (boolean) static::getExtension($type, $folder, $element);
	}

	/**
	 * Gets the parameter object for the extension.
	 *
	 * @param   string  $type     The extension type.
	 * @param   string  $element  The extension element.
	 * @param   string  $folder   The extension folder (if any).
	 *
	 * @return  Registry  A Registry object. If extension does not exist a empty Registry object.
	 *
	 * @see     Registry
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getParams($type, $element, $folder = null)
	{
		static::preload();

		return static::getExtension($type, $folder, $element)->getParams();
	}

	/**
	 * Save the parameters object for an extension.
	 *
	 * @param   string           $type     The extension type.
	 * @param   string           $element  The extension element.
	 * @param   string           $folder   The extension folder (if any).
	 * @param   string|Registry  $params   Params to save
	 *
	 * @return  void
	 *
	 * @see     Registry
	 * @since   __DEPLOY_VERSION__
	 */
	public static function saveParams($type, $element, $folder = null, $params)
	{
		static::preload();

		// No extension installed, or invalid parameters sent. Return false.
		if (!$extension || !$type || !$element || !$params)
		{
			return;
		}

		static::getExtension($type, $folder, $element)->saveParams($params);

		// Reset static array.
		static::$extensions = array();

		// Rebuild extensions static cache.
		static::preload();
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
				->orWhere($db->qn('type') . ' = ' . $db->q('plugin') . ' AND ' . $db->qn('enabled') . ' = 1 AND ' . $db->qn('state') . ' IN (0, 1)');

			$extensions = $db->setQuery($query)->loadAssocList();

			foreach ($extensions as $extension)
			{
				if ($extension['type'] === 'plugin')
				{
					static::$extensions[$extension['type']][$extension['folder']][$extension['element']] = new JExtension($extension);

					continue;
				}

				static::$extensions[$extension['type']][$extension['element']] = new JExtension($extension);
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
	 * @param   string  $type     The extension type.
	 * @param   string  $folder   The extension folder (if any).
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
