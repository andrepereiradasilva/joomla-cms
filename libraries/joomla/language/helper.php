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
	public static function getAvailableSiteLanguages($group = null, $ordering = 'ASC', $checkHome = true, $checkAccess = true)
	{
		// To avoid doing duplicate database queries.
		static $querySiteLanguages     = null;
		static $availableSiteLanguages = array();

		if (is_null($querySiteLanguages))
		{
			$cache = JFactory::getCache('com_languages', '');

			if (!$querySiteLanguages = $cache->get('availablesitelanguages'))
			{
				$db = JFactory::getDbo();
				$query = $db->getQuery(true)
					->select($db->qn(
						array(
							'l.lang_code',
							'l.sef',
							'l.title',
							'l.title_native',
							'l.image',
							'l.sitename',
							'l.metakey',
							'l.metadesc',
							'l.access',
							'l.ordering',
							'm.home',
							)
						)
					)
					->from($db->qn('#__languages', 'l'))
					->join('LEFT', $db->qn('#__extensions', 'e') . ' ON ' . $db->qn('l.lang_code') . ' = ' . $db->qn('e.element'))
					->join('LEFT', $db->qn('#__menu', 'm') . ' ON ' . $db->qn('l.lang_code') . ' = ' . $db->qn('m.language'))
					->where($db->qn('l.published') . ' = 1')
					->where($db->qn('e.type') . ' = ' . $db->q('language'))
					->where($db->qn('e.client_id') . ' = 0')
					->where($db->qn('e.enabled') . ' = 1');

				$querySiteLanguages = $db->setQuery($query)->loadObjectList();

				$cache->store($querySiteLanguages, 'availablesitelanguages');
			}
		}

		// Get static cache key
		$keys = func_get_args();
		unset($keys[0]);
		$key = md5(serialize($keys));

		if (!isset($availableSiteLanguages[$key]))
		{
			$availableSiteLanguages[$key] = $querySiteLanguages;
			$currentLanguage              = JFactory::getLanguage();
			$levels                       = JFactory::getUser()->getAuthorisedViewLevels();

			foreach ($availableSiteLanguages[$key] as $k => $language)
			{
				// Check if the language file exists.
				if (!JLanguage::exists($language->lang_code))
				{
					unset($availableSiteLanguages[$key][$k]);
					continue;
				}
	
				// Check if the user can view the language and if the language file exists.
				if ($checkAccess && (!$language->access || !in_array($language->access, $levels)))
				{
					unset($availableSiteLanguages[$key][$k]);
					continue;
				}

				// Check if the language as homepage.
				if ($checkHome && !$language->home)
				{
					unset($availableSiteLanguages[$key][$k]);
					continue;
				}

				// Check if is the current language.
				$availableSiteLanguages[$key][$k]->active = $language->lang_code == $currentLanguage->getTag();

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
			}

			// Ordering
			if (!is_null($ordering))
			{
				$availableSiteLanguages[$key] = ArrayHelper::sortObjects($availableSiteLanguages[$key], 'ordering', strtolower($ordering) == 'desc' ? -1 : 1, false, true);
			}

			// Fallback to default languages if no languages found.
			if ($availableSiteLanguages[$key] == array())
			{
				// @todo fallback to default languages if empty
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
