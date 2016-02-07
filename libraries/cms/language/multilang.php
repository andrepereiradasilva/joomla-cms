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
				->select($db->quoteName('enabled'))
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
			->select($db->quoteName('element'))
			->from($db->quoteName('#__extensions'))
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
			->select($db->quoteName('language'))
			->select($db->quoteName('id'))
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('home') . ' = 1')
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('client_id') . ' = 0');
		$db->setQuery($query);

		$multilangSiteHomePages = $db->loadObjectList('language');

		return $multilangSiteHomePages;
	}

	/**
	 * Get available languages. A available language is published, the language extension is enabled,
	 * has a homepage menu item, the user can view the language and the homepage and the directory of the language exists.	 
	 *
	 * @param   string  $key  Array key
	 *
	 * @return  array  An array with all available languages.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getAvailableLanguages($key = 'default')
	{
		static $languages;

		if (empty($languages))
		{
			$languages = array();
			$languages['lang_code'] = JLanguageHelper::getLanguages('lang_code');
			$levels                 = JFactory::getUser()->getAuthorisedViewLevels();

			// Fetch the installed site languages from the extensions table.
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('element'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('language'))
				->where($db->quoteName('client_id') . ' = 0')
				->where($db->quoteName('enabled') . ' = 1');
			$db->setQuery($query);
			$extensions = $db->loadObjectList('element');

			// Remove disabled languages from the available languages array.
			$languages['lang_code'] = array_intersect_key($languages['lang_code'], $extensions);

			// Fetch the menu homepages for each language.
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->select($db->quoteName('language'))
				->select($db->quoteName('level'))
				->from($db->quoteName('#__menu'))
				->where($db->quoteName('home') . ' = 1')
				->where($db->quoteName('published') . ' = 1')
				->where($db->quoteName('client_id') . ' = 0');
			$db->setQuery($query);
			$homepages = $db->loadObjectList('language');

			// Remove languages without homepages from the available languages array.
			$languages['lang_code'] = array_intersect_key($languages['lang_code'], $homepages);

			// Add the homepage menu item id and view level to the languages array.
			foreach ($languages['lang_code'] as $language)
			{
				$language->homeid     = $homepages[$language->lang_code]->id;
				$language->homeaccess = $homepages[$language->lang_code]->level;
			}

			// Gets the default language.
			$defaultLanguage       = JLanguageHelper::getDefaultLanguageCode();
			$defaultLanguageExists = false;

			foreach ($languages['lang_code'] as $index => $language)
			{
				// Adds the default language flag.
				$language->default = ($language->lang_code === $defaultLanguage) ? 1 : 0;

				// Check if user can view the language.
				if (isset($language->access) && $language->access !== 0 && !in_array($language->access, $levels))
				{
					unset($languages['lang_code'][$index]);
					continue;
				}

				// Check if user can view the language home item.
				if (isset($language->homeaccess) && $language->homeaccess !== 0 && !in_array($language->homeaccess, $levels))
				{
					unset($languages['lang_code'][$index]);
					continue;
				}

				// Check if the language site ini file exists.
				if (!is_file(JPATH_SITE . '/language/' . $language->lang_code . '/' . $language->lang_code . '.ini'))
				{
					unset($languages['lang_code'][$index]);
					continue;
				}

				// Adds the default language and check if exists.
				if ($language->default === 1)
				{
					$defaultLanguageExists = true;
				}
			}

			// Default language not available, so no available languages.
			if (!$defaultLanguageExists)
			{
				$languages['lang_code'] = array();
			}

			// Add the arrays for the other keys.
			foreach ($languages['lang_code'] as $lang)
			{
				$languages['sef'][$lang->sef] = $lang;
				$languages['default'][] = $lang;
			}
		}

		return $languages[$key];
	}
}