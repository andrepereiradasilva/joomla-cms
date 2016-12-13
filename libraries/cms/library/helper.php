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
 */
class JLibraryHelper
{
	/**
	 * The component list cache
	 *
	 * @var    array
	 * @since  3.2
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
	 */
	public static function getLibrary($element, $strict = false)
	{
		static::load();

		if (isset(static::$libraries[$element]))
		{
			return static::$libraries[$element];
		}

		$result = new stdClass;
		$result->enabled = $strict ? false : true;
		$result->params = new Registry;

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
	 */
	public static function isEnabled($element)
	{
		return JExtensionHelper::isEnabled('library', $element);
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
	 */
	public static function getParams($element)
	{
		return JExtensionHelper::getParams('library', $element);
	}

	/**
	 * Save the parameters object for the library
	 *
	 * @param   string    $element  Element of the library in the extensions table.
	 * @param   Registry  $params   Params to save
	 *
	 * @return  boolean   True if params saved, false otherwhise.
	 *
	 * @see     Registry
	 * @since   3.2
	 */
	public static function saveParams($element, $params)
	{
		return JExtensionHelper::saveParams('library', $element, null, null, $params);
	}

	/**
	 * Load the installed libraryes into the libraries property.
	 *
	 * @param   string  $element  The element value for the extension
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.2
	 * @deprecated  4.0  Use JLibraryHelper::load() instead
	 */
	protected static function _load($element)
	{
		return static::load();
	}

	/**
	 * Load the installed components into the components property.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.2
	 */
	protected static function load()
	{
		if (static::$libraries !== null)
		{
			return static::$libraries;
		}

		// Load all components.
		$libraries = JExtensionHelper::getExtensions('library');

		static::$libraries = array();

		foreach ($libraries as $library)
		{
			// Use the already used terms.
			$library->id     = $library->extension_id;
			$library->option = $library->element;

			static::$libraries[$library->option] = $library;
		}

		return true;
	}
}
