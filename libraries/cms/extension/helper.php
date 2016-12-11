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
	 * @return  JExtension  Extension object if exists. Empty extension object otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getExtension($type, $element, $folder = null)
	{
		self::preload();

		// For plugins.
		if ($type === 'plugin' && isset(static::$extensions[$type], static::$extensions[$type][$folder], static::$extensions[$type][$folder][$element]))
		{
			return static::$extensions[$type][$folder][$element];
		}

		// For other extension types.
		if ($folder === null && isset(static::$extensions[$type], static::$extensions[$type][$element]))
		{
			return static::$extensions[$type][$element];
		}

		// This exception is not translated because the extensions (including languages have not been loaded yet).
		throw new RuntimeException('Extension does not exist.', 500);
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
		self::preload();

		try
		{
			return (boolean) self::getExtension($type, $element, $folder)->isEnabled();
		}
		catch (Exception $e)
		{
			return false;
		}
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
		self::preload();

		try
		{
			self::getExtension($type, $element, $folder);

			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
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
		self::preload();

		try
		{
			return self::getExtension($type, $element, $folder)->getParams();
		}
		catch (Exception $e)
		{
			return new Registry;
		}
	}

	/**
	 * Save the parameters object for an extension.
	 *
	 * @param   string           $type     The extension type.
	 * @param   string           $element  The extension element.
	 * @param   string           $folder   The extension folder (if any).
	 * @param   string|Registry  $params   Params to save
	 *
	 * @return  boolean   True if params saved, false otherwhise.
	 *
	 * @see     Registry
	 * @since   __DEPLOY_VERSION__
	 */
	public static function saveParams($type, $element, $folder = null, $params)
	{
		self::preload();

		// No extension installed, or invalid parameters sent. Return false.
		if (!$params)
		{
			return false;
		}

		try
		{
			self::getExtension($type, $element, $folder)->saveParams($params);
		}
		catch (Exception $e)
		{
			return false;
		}

		// Update the static cache.

		// For plugins.
		if ($type === 'plugin' && isset(static::$extensions[$type], static::$extensions[$type][$folder], static::$extensions[$type][$folder][$element]))
		{
			static::$extensions[$type][$folder][$element]->params = !is_string($params) ?: new Registry($params);
		}

		// For other extension types.
		if ($folder === null && isset(static::$extensions[$type], static::$extensions[$type][$element]))
		{
			static::$extensions[$type][$element] = !is_string($params) ?: new Registry($params);
		}

		return true;
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
			$db = JFactory::getDbo();

			$validExtensions = array($db->q('component'), $db->q('library'), $db->q('plugin'), $db->q('language'));

			$query = $db->getQuery(true)
				->select($db->qn(array('extension_id', 'name', 'element', 'type', 'folder', 'enabled', 'params', 'access', 'state', 'client_id', 'ordering')))
				->from($db->qn('#__extensions'))
				->where($db->qn('type') . ' IN (' . implode(', ', $validExtensions) . ')');

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
		self::preload();

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
