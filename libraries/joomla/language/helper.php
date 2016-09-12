<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Language
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Language helper class
 *
 * @since  11.1
 */
class JLanguageHelper
{
	/**
	 * Builds a list of the system languages which can be used in a select option
	 *
	 * @param   string   $actualLanguage  Client key for the area
	 * @param   string   $basePath        Base path to use
	 * @param   boolean  $caching         True if caching is used
	 * @param   boolean  $installed       Get only installed languages
	 *
	 * @return  array  List of system languages
	 *
	 * @since   11.1
	 */
	public static function createLanguageList($actualLanguage, $basePath = JPATH_BASE, $caching = false, $installed = false)
	{
		$list = array();

		// Cache activation
		$langs = JLanguage::getKnownLanguages($basePath);

		if ($installed)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('element')
				->from('#__extensions')
				->where('type=' . $db->quote('language'))
				->where('state=0')
				->where('enabled=1')
				->where('client_id=' . ($basePath == JPATH_ADMINISTRATOR ? 1 : 0));
			$db->setQuery($query);
			$installed_languages = $db->loadObjectList('element');
		}

		foreach ($langs as $lang => $metadata)
		{
			if (!$installed || array_key_exists($lang, $installed_languages))
			{
				$option = array();

				$option['text'] = $metadata['name'];
				$option['value'] = $lang;

				if ($lang == $actualLanguage)
				{
					$option['selected'] = 'selected="selected"';
				}

				$list[] = $option;
			}
		}

		return $list;
	}

	/**
	 * Tries to detect the language.
	 *
	 * @return  string  locale or null if not found
	 *
	 * @since   11.1
	 */
	public static function detectLanguage()
	{
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$browserLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$systemLangs = self::getLanguages();

			foreach ($browserLangs as $browserLang)
			{
				// Slice out the part before ; on first step, the part before - on second, place into array
				$browserLang = substr($browserLang, 0, strcspn($browserLang, ';'));
				$primary_browserLang = substr($browserLang, 0, 2);

				foreach ($systemLangs as $systemLang)
				{
					// Take off 3 letters iso code languages as they can't match browsers' languages and default them to en
					$Jinstall_lang = $systemLang->lang_code;

					if (strlen($Jinstall_lang) < 6)
					{
						if (strtolower($browserLang) == strtolower(substr($systemLang->lang_code, 0, strlen($browserLang))))
						{
							return $systemLang->lang_code;
						}
						elseif ($primary_browserLang == substr($systemLang->lang_code, 0, 2))
						{
							$primaryDetectedLang = $systemLang->lang_code;
						}
					}
				}

				if (isset($primaryDetectedLang))
				{
					return $primaryDetectedLang;
				}
			}
		}

		return;
	}

	/**
	 * Get available languages
	 *
	 * @param   string  $key  Array key
	 *
	 * @return  array  An array of published languages
	 *
	 * @since   11.1
	 */
	public static function getLanguages($key = 'default')
	{
		static $languages;

		if (empty($languages))
		{
			// Installation uses available languages
			if (JFactory::getApplication()->getClientId() == 2)
			{
				$languages[$key] = array();
				$knownLangs = JLanguage::getKnownLanguages(JPATH_BASE);

				foreach ($knownLangs as $metadata)
				{
					// Take off 3 letters iso code languages as they can't match browsers' languages and default them to en
					$obj = new stdClass;
					$obj->lang_code = $metadata['tag'];
					$languages[$key][] = $obj;
				}
			}
			else
			{
				$languages[$key] = self::getAvailableSiteLanguages($key);
			}
		}

		return $languages[$key];
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
		static $querySiteLanguages     = null;
		static $queryContentLanguages  = null;
		static $availableSiteLanguages = array();

		if (is_null($queryContentLanguages) || is_null($querySiteLanguages))
		{
			$cache = JFactory::getCache('com_languages', '');

			if (!($querySiteLanguages = $cache->get('sitelanguages')) || !($queryContentLanguages = $cache->get('contentlanguages')))
			{
			
				$db = JFactory::getDbo();

				// Get all installed and enabled site languages.
				$query = $db->getQuery(true)
					->select('element')
					->from($db->qn('#__extensions'))
					->where($db->qn('type') . ' = ' . $db->q('language'))
					->where($db->qn('client_id') . ' = 0')
					->where($db->qn('enabled') . ' = 1');

				$querySiteLanguages = $db->setQuery($query)->loadObjectList('element');

				$cache->store($querySiteLanguages, 'sitelanguages');

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
			$app                          = JFactory::getApplication();
			$menu                         = $app->getMenu();
			$currentLanguage              = $app->getLanguage();
			$levels                       = JFactory::getUser()->getAuthorisedViewLevels();
			$defaultLanguageCode          = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
			$count                        = 0;

			foreach ($availableSiteLanguages[$key] as $k => $language)
			{
				$availableSiteLanguages[$key][$k]->available = 0;
				$availableSiteLanguages[$key][$k]->active    = 0;

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

				// Check if is the current language.
				$availableSiteLanguages[$key][$k]->active = $language->lang_code === $currentLanguage->getTag();

				// If current language get the rtl from current JLanguage metadata
				if ($availableSiteLanguages[$key][$k]->active)
				{
					$availableSiteLanguages[$key][$k]->rtl = $currentLanguage->isRtl();
				}
				// If not loaded language fetch rtl directly for performance
				else
				{
					$languageMetadata                      = JLanguage::getMetadata($language->lang_code);
					$availableSiteLanguages[$key][$k]->rtl = $languageMetadata['rtl'];
				}

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
			$currentLanguage = JFactory::getLanguage();

			foreach ($availableSiteLanguages[$key] as $language)
			{
				$returnLanguages[$language->{$group}] = $language;
			}

			$availableSiteLanguages[$key] = $returnLanguages;
		}

		return $availableSiteLanguages[$key];
	}
}
