<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Utility class for jQuery JavaScript behaviors
 *
 * @since  3.0
 */
abstract class JHtmlJquery
{
	/**
	 * @var    array  Array containing information for loaded files
	 * @since  3.0
	 */
	protected static $loaded = array();

	/**
	 * Method to load the jQuery JavaScript framework into the document head
	 *
	 * If debugging mode is on an uncompressed version of jQuery is included for easier debugging.
	 *
	 * @param   boolean  $noConflict  True to load jQuery in noConflict mode [optional]
	 * @param   mixed    $debug       Is debugging mode on? [optional]
	 * @param   boolean  $migrate     True to enable the jQuery Migrate plugin
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function framework($noConflict = true, $debug = null, $migrate = true)
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		// If no debugging value is set, use the configuration setting
		$debug = (boolean) ($debug === null ? JDEBUG : $debug);

		HTMLHelper::_('script', 'jui/jquery.min.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false, 'detectDebug' => $debug));

		// Check if we are loading in noConflict
		if ($noConflict)
		{
			HTMLHelper::_('script', 'jui/jquery-noconflict.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false, 'detectDebug' => $debug));
		}

		// Check if we are loading Migrate
		if ($migrate)
		{
			HTMLHelper::_('script', 'jui/jquery-migrate.min.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false, 'detectDebug' => $debug));
		}

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Method to load the jQuery UI JavaScript framework into the document head
	 *
	 * If debugging mode is on an uncompressed version of jQuery UI is included for easier debugging.
	 *
	 * @param   array  $components  The jQuery UI components to load [optional]
	 * @param   mixed  $debug       Is debugging mode on? [optional]
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function ui(array $components = array('core'), $debug = null)
	{
		// Set an array containing the supported jQuery UI components handled by this method
		$supported = array('core', 'sortable');

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		// If no debugging value is set, use the configuration setting
		$debug = (boolean) ($debug === null ? JDEBUG : $debug);

		// Load each of the requested components
		foreach ($components as $component)
		{
			// Only attempt to load the component if it's supported in core and hasn't already been loaded
			if (!isset(static::$loaded[__METHOD__][$component]) && in_array($component, $supported, true))
			{
				HTMLHelper::_('script', 'jui/jquery.ui.' . $component . '.min.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false, 'detectDebug' => $debug));

				static::$loaded[__METHOD__][$component] = true;
			}
		}
	}

	/**
	 * Auto set CSRF token to ajaxSetup so all jQuery ajax call will contains CSRF token.
	 *
	 * @param   string  $name  The CSRF meta tag name.
	 *
	 * @return  void
	 *
	 * @throws  \InvalidArgumentException
	 *
	 * @since   3.8.0
	 */
	public static function token($name = 'csrf.token')
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__][$name]))
		{
			return;
		}

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		HTMLHelper::_('form.csrf', $name);

		Factory::getDocument()->addScriptDeclaration(
<<<JS
;(function ($) {
	$.ajaxSetup({
		headers: {
			'X-CSRF-Token': Joomla.getOptions('$name')
		}
	});
})(jQuery);
JS
		);

		static::$loaded[__METHOD__][$name] = true;
	}
}
