<?php
/**
 * @package     Joomla.Legacy
 * @subpackage  Library
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

/**
 * Library helper class
 *
 * @since  3.2
 * @deprecated  __DEPLOY_VERSION__ (removed in 4.0). Use JExtensionHelper::isEnabled('library', null, ...) and do adjustements (if needed).
 */
class JLibraryHelper
{
	/**
	 * The component list cache
	 *
	 * @var    array
	 * @since  3.2
	 * @deprecated  __DEPLOY_VERSION__ (removed in 4.0). No replacement.
	 */
	protected static $libraries = array();

	/**
	 * Get the library information.
	 *
	 * @param   string   $element  Element of the library in the extensions table.
	 * @param   boolean  $strict   If set and the library does not exist, the enabled attribute will be set to false.
	 *
	 * @return  stdClass   An object with the library's information.
	 *
	 * @since   3.2
	 * @deprecated  __DEPLOY_VERSION__ (removed in 4.0). Use JExtensionHelper::getExtension('library', null, $element) and do adjustements (if needed).
	 */
	public static function getLibrary($element, $strict = false)
	{
		// Is already cached ?
		if (isset(static::$libraries[$element]))
		{
			return static::$libraries[$element];
		}

		if (static::_load($element))
		{
			$result = static::$libraries[$element];
		}
		else
		{
			$result = new stdClass;
			$result->enabled = $strict ? false : true;
			$result->params = new Registry;
		}

		return $result;
	}

	/**
	 * Checks if a library is enabled
	 *
	 * @param   string  $element  Element of the library in the extensions table.
	 *
	 * @return  boolean
	 *
	 * @since   3.2
	 * @deprecated  __DEPLOY_VERSION__ (removed in 4.0). Use JExtensionHelper::isEnabled('library', null, $element) and do adjustements (if needed).
	 */
	public static function isEnabled($element)
	{
		return JExtensionHelper::isEnabled('library', null, $element);
	}

	/**
	 * Gets the parameter object for the library
	 *
	 * @param   string   $element  Element of the library in the extensions table.
	 * @param   boolean  $strict   If set and the library does not exist, false will be returned
	 *
	 * @return  Registry  A Registry object.
	 *
	 * @see     Registry
	 * @since   3.2
	 * @deprecated  __DEPLOY_VERSION__ (removed in 4.0). Use JExtensionHelper::getParams('library', null, $element) and do adjustements (if needed).
	 */
	public static function getParams($element, $strict = false)
	{
		return JExtensionHelper::getParams('library', null, $element);
	}

	/**
	 * Save the parameters object for the library
	 *
	 * @param   string    $element  Element of the library in the extensions table.
	 * @param   Registry  $params   Params to save
	 *
	 * @return  Registry  A Registry object.
	 *
	 * @see     Registry
	 * @since   3.2
	 * @deprecated  __DEPLOY_VERSION__ (removed in 4.0). Use JExtensionHelper::saveParams('library', null, $element, $params).
	 */
	public static function saveParams($element, $params)
	{
		if (static::isEnabled($element))
		{
			// Save params in DB
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->update($db->quoteName('#__extensions'))
				->set($db->quoteName('params') . ' = ' . $db->quote($params->toString()))
				->where($db->quoteName('type') . ' = ' . $db->quote('library'))
				->where($db->quoteName('element') . ' = ' . $db->quote($element));
			$db->setQuery($query);

			$result = $db->execute();

			// Update params in libraries cache
			if ($result && isset(static::$libraries[$element]))
			{
				static::$libraries[$element]->params = $params;
			}

			return $result;
		}

		return false;
	}

	/**
	 * Load the installed libraryes into the libraries property.
	 *
	 * @param   string  $element  The element value for the extension
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.2
	 * @deprecated  __DEPLOY_VERSION__ (removed in 4.0). No replacement.
	 */
	protected static function _load($element)
	{
		// Already loaded no need to do anything more.
		if (static::$libraries !== array())
		{
			return true;
		}

		/** @var JCacheControllerCallback $cache */
		$cache = JFactory::getCache('_system', '');

		// Load all libraries, first try to laod them from cache, fallback to use JExtensionHelper::getExtensions('library').
		if (!static::$libraries = $cache->get('libraries'))
		{
			foreach (JExtensionHelper::getExtensions('library') as $library)
			{
				// B/C Library is using option.
				$library->option = $library->element;

				static::$libraries[$library->element] = $library;
			}

			$cache->store(static::$libraries, 'libraries');
		}

		// Loaded with success.
		if (static::$libraries && static::$libraries !== array())
		{
			return true;
		}

		/*
		 * Fatal error
		 *
		 * It is possible for this error to be reached before the global JLanguage instance has been loaded so we check for its presence
		 * before logging the error to ensure a human friendly message is always given
		 */
		if (JFactory::$language)
		{
			$msg = JText::sprintf('JLIB_APPLICATION_ERROR_LIBRARY_NOT_LOADING', $element, JText::_('JLIB_APPLICATION_ERROR_LIBRARY_NOT_FOUND'));
		}
		else
		{
			$msg = sprintf('Error loading library: %1$s, %2$s', $element, 'Component not found.');
		}

		JLog::add($msg, JLog::WARNING, 'jerror');

		return false;
	}
}
