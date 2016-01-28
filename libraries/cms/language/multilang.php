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
		static $languages = array();

		if (!isset($languages[$key]))
		{
			$languages[$key] = JLanguageHelper::getLanguages($key);
			$levels          = JFactory::getUser()->getAuthorisedViewLevels();

			// Add the default language
			$defaultLanguage = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
			foreach ($languages[$key] as $language)
			{
				$language->default = ($language->lang_code == $defaultLanguage) ? 1 : 0;
			}

			// Fetch the extensions table.
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('element'))
				->select($db->quoteName('enabled'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('language'))
				->where($db->quoteName('client_id') . ' = 0');
			$db->setQuery($query);
			$extensions = $db->loadObjectList('element');
			foreach ($languages[$key] as $language)
			{
				$language->extension_enabled = (!empty($extensions[$language->lang_code]->enabled)) ? 1 : 0;
			}

			// Fetch the menu homepages
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
			foreach ($languages[$key] as $language)
			{
				$language->homeid     = (!empty($homepages[$language->lang_code]->id)) ? $homepages[$language->lang_code]->id : 0;
				$language->homeaccess = (!empty($homepages[$language->lang_code]->level)) ? $homepages[$language->lang_code]->level : 0;
			}

			foreach ($languages[$key] as $index => $language)
			{
				if (empty($language->extension_enabled))
				{
					unset($languages[$key][$index]);
					continue;
				}
				if (empty($language->homeid))
				{
					unset($languages[$key][$index]);
					continue;
				}
				if (isset($language->access) && $language->access != 0 && !in_array($language->access, $levels))
				{
					unset($languages[$key][$index]);
					continue;
				}
				if (isset($language->homeaccess) && $language->homeaccess != 0 && !in_array($language->homeaccess, $levels))
				{
					unset($languages[$key][$index]);
					continue;
				}
				if (!is_dir(JPATH_SITE . '/language/' . $language->lang_code))
				{
					unset($languages[$key][$index]);
					continue;
				}
			}
		}

		return $languages[$key];
	}

	/**
	 * Get default language.
	 *
	 * @return  object  Object with default language properties.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getDefaultLanguage()
	{
		$languages = self::getAvailableLanguages('lang_code');
		foreach ($languages as $index => $language)
		{
			if ($language->default)
			{
				return $languages[$index];
			}
		}

		// If no default language is available (example the default language is not published).
		// Use the system default language as a fallback.
		$params = JComponentHelper::getParams('com_languages');
		$defaultLanguageCode = $params->get('site', $this->get('language', 'en-GB'));
		$defaultLanguage = new stdClass();
		$defaultLanguage->lang_code = $defaultLanguageCode;

		return $defaultLanguage;
	}
}