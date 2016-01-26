<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Language
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Utitlity class for multilang
 *
 * @since  2.5.4
 */
class JLanguageMultilang
{
	/**
	 * Method to determine if the language filter plugin is enabled.
	 * This works for both site and administrator.
	 *
	 * @return  boolean  True if site is supporting multiple languages; false otherwise.
	 *
	 * @since   2.5.4
	 */
	public static function isEnabled()
	{
		static $enabled = null;

		// If already tested, don't test again. Return the previous result.
		if (!is_null($enabled))
		{
			return $enabled;
		}

		// Set it as false by default.
		$enabled = false;

		// If being called from the front-end, we can avoid the database query.
		$app = JFactory::getApplication();
		if ($app->isSite())
		{
			$enabled = $app->getLanguageFilter();
		}
		else
		{
			// Determine status of language filter plug-in.
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('enabled')
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
				->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
				->where($db->quoteName('element') . ' = ' . $db->quote('languagefilter'));
			$db->setQuery($query);

			$enabled = $db->loadResult();
		}

		return $enabled;
	}

	/**
	 * Method to return a list of published site languages.
	 *
	 * @return  array of language extension objects.
	 *
	 * @since   3.5
	 */
	public static function getSiteLangs()
	{
		// To avoid doing duplicate database queries.
		static $multilangSiteLangs = null;

		// If already fetched, don't fetch again. Return the previous result.
		if (!is_null($multilangSiteLangs))
		{
			return $multilangSiteLangs;
		}

		// Check for published Site Languages.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('element')
			->from('#__extensions')
			->where($db->quoteName('type') . ' = ' . $db->quote('language'))
			->where($db->quoteName('client_id') . ' = 0')
			->where($db->quoteName('enabled') . ' = 1');
		$db->setQuery($query);

		$multilangSiteLangs = $db->loadObjectList('element');

		return $multilangSiteLangs;
	}

	/**
	 * Method to return a list of language home page menu items.
	 *
	 * @return  array of menu objects.
	 *
	 * @since   3.5
	 */
	public static function getSiteHomePages()
	{
		// To avoid doing duplicate database queries.
		static $multilangSiteHomePages = null;

		// If already fetched, don't fetch again. Return the previous result.
		if (!is_null($multilangSiteHomePages))
		{
			return $multilangSiteHomePages;
		}

		// Check for Home pages languages.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('language')
			->select('id')
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('home') . ' = 1')
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('client_id') . ' = 0');
		$db->setQuery($query);

		$multilangSiteHomePages = $db->loadObjectList('language');

		return $multilangSiteHomePages;
	}
}
