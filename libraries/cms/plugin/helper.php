<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Plugin
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Plugin helper class
 *
 * @since  1.5
 */
abstract class JPluginHelper
{
	/**
	 * A persistent cache of the loaded plugins.
	 *
	 * @var    array
	 * @since  1.7
	 * @deprecated  __DEPLOY_VERSION__ (removed in 4.0). No replacement.
	 */
	protected static $plugins = null;

	/**
	 * Get the path to a layout from a Plugin
	 *
	 * @param   string  $type    Plugin type
	 * @param   string  $name    Plugin name
	 * @param   string  $layout  Layout name
	 *
	 * @return  string  Layout path
	 *
	 * @since   3.0
	 */
	public static function getLayoutPath($type, $name, $layout = 'default')
	{
		$template = JFactory::getApplication()->getTemplate();
		$defaultLayout = $layout;

		if (strpos($layout, ':') !== false)
		{
			// Get the template and file name from the string
			$temp = explode(':', $layout);
			$template = ($temp[0] == '_') ? $template : $temp[0];
			$layout = $temp[1];
			$defaultLayout = ($temp[1]) ? $temp[1] : 'default';
		}

		// Build the template and base path for the layout
		$tPath = JPATH_THEMES . '/' . $template . '/html/plg_' . $type . '_' . $name . '/' . $layout . '.php';
		$bPath = JPATH_PLUGINS . '/' . $type . '/' . $name . '/tmpl/' . $defaultLayout . '.php';
		$dPath = JPATH_PLUGINS . '/' . $type . '/' . $name . '/tmpl/default.php';

		// If the template has a layout override use it
		if (file_exists($tPath))
		{
			return $tPath;
		}

		if (file_exists($bPath))
		{
			return $bPath;
		}

		return $dPath;
	}

	/**
	 * Get the plugin data of a specific type if no specific plugin is specified
	 * otherwise only the specific plugin data is returned.
	 *
	 * @param   string  $folder   The plugin type, relates to the subdirectory in the plugins directory.
	 * @param   string  $element  The plugin name.
	 *
	 * @return  mixed  An array of plugin data objects, or a plugin data object.
	 *
	 * @since   1.5
	 * @deprecated  __DEPLOY_VERSION__ (removed in 4.0). Use JExtensionHelper::getExtension('plugin', $type[, $plugin]) and do adjustements (if needed).
	 */
	public static function getPlugin($folder, $element = null)
	{
		static::load();

		$result = array();

		// Find the correct plugin(s) to return.
		foreach (static::$plugins as $plugin)
		{
			if ($element === null && $plugin->folder === $folder)
			{
				$result[] = $plugin;
				continue;
			}

			if ($element !== null && $plugin->folder === $folder && $plugin->element == $element)
			{
				$result = $plugin;
				break;
			}
		}

		return $result;
	}

	/**
	 * Checks if a plugin is enabled.
	 *
	 * @param   string  $folder   The plugin type, relates to the subdirectory in the plugins directory.
	 * @param   string  $element  The plugin name.
	 *
	 * @return  boolean
	 *
	 * @since   1.5
	 * @deprecated  __DEPLOY_VERSION__ (removed in 4.0). Use JExtensionHelper::isEnabled('plugin', $type, $plugin) and do adjustements (if needed).
	 */
	public static function isEnabled($folder, $element = null)
	{
		return !empty(static::getPlugin($folder, $element));
	}

	/**
	 * Loads all the plugin files for a particular type if no specific plugin is specified
	 * otherwise only the specific plugin is loaded.
	 *
	 * @param   string            $folder      The plugin folder, relates to the subdirectory in the plugins directory.
	 * @param   string            $element     The plugin name.
	 * @param   boolean           $autocreate  Autocreate the plugin.
	 * @param   JEventDispatcher  $dispatcher  Optionally allows the plugin to use a different dispatcher.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.5
	 */
	public static function importPlugin($folder, $element = null, $autocreate = true, JEventDispatcher $dispatcher = null)
	{
		static $loaded = array();

		// Check for the default args, if so we can optimise cheaply
		$defaults = false;

		if (is_null($element) && $autocreate == true && is_null($dispatcher))
		{
			$defaults = true;
		}

		if (!isset($loaded[$folder]) || !$defaults)
		{
			$results = null;

			// Load the plugins from the database.
			static::load();

			// Get the specified plugin(s).
			foreach (static::$plugins as $plugin)
			{
				if ($plugin->folder === $folder && ($element === null || $plugin->element == $element))
				{
					static::import($plugin, $autocreate, $dispatcher);
					$results = true;
				}
			}

			// Bail out early if we're not using default args
			if (!$defaults)
			{
				return $results;
			}

			$loaded[$folder] = $results;
		}

		return $loaded[$folder];
	}

	/**
	 * Loads the plugin file.
	 *
	 * @param   object            $plugin      The plugin.
	 * @param   boolean           $autocreate  True to autocreate.
	 * @param   JEventDispatcher  $dispatcher  Optionally allows the plugin to use a different dispatcher.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 * @deprecated  4.0  Use JPluginHelper::import() instead
	 */
	protected static function _import($plugin, $autocreate = true, JEventDispatcher $dispatcher = null)
	{
		static::import($plugin, $autocreate, $dispatcher);
	}

	/**
	 * Loads the plugin file.
	 *
	 * @param   object            $plugin      The plugin.
	 * @param   boolean           $autocreate  True to autocreate.
	 * @param   JEventDispatcher  $dispatcher  Optionally allows the plugin to use a different dispatcher.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected static function import($plugin, $autocreate = true, JEventDispatcher $dispatcher = null)
	{
		static $paths = array();

		$plugin->folder  = preg_replace('/[^A-Z0-9_\.-]/i', '', $plugin->folder);
		$plugin->element = preg_replace('/[^A-Z0-9_\.-]/i', '', $plugin->element);

		$path = JPATH_PLUGINS . '/' . $plugin->folder . '/' . $plugin->element . '/' . $plugin->element . '.php';

		if (!isset($paths[$path]))
		{
			if (file_exists($path))
			{
				if (!isset($paths[$path]))
				{
					require_once $path;
				}

				$paths[$path] = true;

				if ($autocreate)
				{
					// Makes sure we have an event dispatcher
					if (!is_object($dispatcher))
					{
						$dispatcher = JEventDispatcher::getInstance();
					}

					$className = 'Plg' . $plugin->folder . $plugin->element;

					if (class_exists($className))
					{
						// Load the plugin from the database. This if will be removed in 4.0.
						if (!isset($plugin->params))
						{
							// Seems like this could just go bye bye completely
							$plugin = static::getPlugin($plugin->folder, $plugin->element);
						}

						// Instantiate and register the plugin.
						new $className($dispatcher, (array) $plugin);
					}
				}
			}
			else
			{
				$paths[$path] = false;
			}
		}
	}

	/**
	 * Loads the published plugins.
	 *
	 * @return  array  An array of published plugins
	 *
	 * @since   1.5
	 * @deprecated  4.0  Use JPluginHelper::load() instead
	 */
	protected static function _load()
	{
		return static::load();
	}

	/**
	 * Loads the published plugins.
	 *
	 * @return  array  An array of published plugins
	 *
	 * @since   3.2
	 */
	protected static function load()
	{
		if (static::$plugins !== null)
		{
			return static::$plugins;
		}

		/** @var JCacheControllerCallback $cache */
		$cache    = JFactory::getCache('com_plugins', '');
		$levels   = JFactory::getUser()->getAuthorisedViewLevels();
		$cacheKey = 'plugins-' . md5(implode(',', $levels));

		// Get all the extensions.
		if (!static::$plugins = $cache->get($cacheKey))
		{
			foreach (JExtensionHelper::getExtensions('plugin') as $pluginFolder)
			{
				foreach ($pluginFolder as $plugin)
				{
					if (in_array($plugin->access, $levels))
					{
						// B/C Plugins can be using this old type and name properties.
						$plugin->type = $plugin->folder;
						$plugin->name = $plugin->element;

						static::$plugins[] = $plugin;
					}
				}
			}

			// Order the plugins.
			static::$plugins = ArrayHelper::sortObjects(static::$plugins, 'ordering', 1, true, true);

			$cache->store(static::$plugins, $cacheKey);
		}

		return static::$plugins;
	}
}
