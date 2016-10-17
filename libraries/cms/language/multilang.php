<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Language
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Utilities\ArrayHelper;

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
		// Flag to avoid doing multiple database queries.
		static $tested = false;

		// Status of language filter plugin.
		static $enabled = false;

		// Get application object.
		$app = JFactory::getApplication();

		// If being called from the frontend, we can avoid the database query.
		if ($app->isSite())
		{
			$enabled = $app->getLanguageFilter();

			return $enabled;
		}

		// If already tested, don't test again.
		if (!$tested)
		{
			// Determine status of language filter plugin.
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('enabled')
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
				->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
				->where($db->quoteName('element') . ' = ' . $db->quote('languagefilter'));
			$db->setQuery($query);

			$enabled = $db->loadResult();
			$tested = true;
		}

		return (bool) $enabled;
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

		if (!isset($multilangSiteLangs))
		{
			// Check for published Site Languages.
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('element')
				->from('#__extensions')
				->where('type = ' . $db->quote('language'))
				->where('client_id = 0')
				->where('enabled = 1');
			$db->setQuery($query);

			$multilangSiteLangs = $db->loadObjectList('element');
		}

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

		if (!isset($multilangSiteHomePages))
		{
			// Check for Home pages languages.
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('language')
				->select('id')
				->from($db->quoteName('#__menu'))
				->where('home = 1')
				->where('published = 1')
				->where('client_id = 0');
			$db->setQuery($query);

			$multilangSiteHomePages = $db->loadObjectList('language');
		}

		return $multilangSiteHomePages;
	}


	/**
	 * Method to return a list of available site languages.
	 *
	 * @param   string  $group  Array group
	 *
	 * @return  array  Available site language objects.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getAvailableSiteLanguages($group = null, $ordering = 'ASC', $checkHome = true, $checkAccess = true, $checkPublished = true)
	{
		// To avoid doing duplicate database queries.
		static $queryContentLanguages  = null;
		static $availableSiteLanguages = array();

		if (is_null($queryContentLanguages))
		{
			$cache = JFactory::getCache('com_languages', '');

			if (!$queryContentLanguages = $cache->get('contentlanguages'))
			{
				$db = JFactory::getDbo();

				// Get all content languages.
				$query = $db->getQuery(true)
					->select('*')
					->from($db->qn('#__languages'));

				$queryContentLanguages = $db->setQuery($query)->loadObjectList();

				$cache->store($queryContentLanguages, 'contentlanguages');
			}
		}

		// Get static cache key
		$keys = func_get_args();
		unset($keys[0]);
		$key = md5(serialize($keys));

		if (!isset($availableSiteLanguages[$key]))
		{
			$availableSiteLanguages[$key] = $queryContentLanguages;
		    $querySiteLanguages           = self::getSiteLangs();
			$app                          = JFactory::getApplication();
			$menu                         = $app->getMenu('site');
			$levels                       = JFactory::getUser()->getAuthorisedViewLevels();
			$defaultLanguageCode          = JComponentHelper::getParams('com_languages')->get('site', JFactory::getConfig()->get('language', 'en-GB'));
			$count                        = 0;

			foreach ($availableSiteLanguages[$key] as $k => $language)
			{
				$availableSiteLanguages[$key][$k]->available = 0;

				// Check if the current language is the default language.
				$availableSiteLanguages[$key][$k]->default = $language->lang_code === $defaultLanguageCode;

				// Check if the language file exists.
				if (!JLanguage::exists($language->lang_code))
				{
					continue;
				}

				// Check if the language is installed and enabled.
				if (!isset($querySiteLanguages[$language->lang_code]))
				{
					continue;
				}

				// Check if the user can view the language.
				if ($checkAccess && (!$language->access || !in_array($language->access, $levels)))
				{
					continue;
				}

				// Check if the user can view not published languages.
				if ($checkPublished && (int) $language->published !== 1)
				{
					continue;
				}

				$homeMenuItem = $menu->getDefault($language->lang_code);

				// Check if the language as homepage.
				if ($checkHome && !$homeMenuItem->id)
				{
					continue;
				}

				// Since it passed all checks language is available.
				$availableSiteLanguages[$key][$k]->home_id = $homeMenuItem->id;

				// Check if the user can view the the home menu item.
				if ($checkHome && $checkAccess && (!$homeMenuItem->access || !in_array($homeMenuItem->access, $levels)))
				{
					continue;
				}

				// Since it passed all checks language is available.
				$availableSiteLanguages[$key][$k]->available = 1;

				$count++;
			}

			// Fallback to default language if no languages found.
			if ($count == 0)
			{
				foreach ($availableSiteLanguages[$key] as $k => $language)
				{
					if (!$availableSiteLanguages[$key][$k]->default)
					{
						unset($availableSiteLanguages[$key][$k]);
					}
				}
			}
			else
			{
				foreach ($availableSiteLanguages[$key] as $k => $language)
				{
					if (!$availableSiteLanguages[$key][$k]->available)
					{
						unset($availableSiteLanguages[$key][$k]);
					}
				}
			}

			// Ordering
			if (!is_null($ordering))
			{
				$availableSiteLanguages[$key] = ArrayHelper::sortObjects($availableSiteLanguages[$key], 'ordering', strtolower($ordering) == 'desc' ? -1 : 1, false, true);
			}
		}

		// Grouping
		if (!is_null($group) && $group !== 'default')
		{
			$returnLanguages = array();

			foreach ($availableSiteLanguages[$key] as $language)
			{
				$returnLanguages[$language->{$group}] = $language;
			}

			$availableSiteLanguages[$key] = $returnLanguages;
		}

		return $availableSiteLanguages[$key];
	}
}
