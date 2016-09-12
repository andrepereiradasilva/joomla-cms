<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Language
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

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
				$cache = JFactory::getCache('com_languages', '');

				if (!$languages = $cache->get('languages'))
				{
					$db = JFactory::getDbo();
					$query = $db->getQuery(true)
						->select('*')
						->from('#__languages')
						->where('published=1')
						->order('ordering ASC');
					$db->setQuery($query);

					$languages['default'] = $db->loadObjectList();
					$languages['sef'] = array();
					$languages['lang_code'] = array();

					if (isset($languages['default'][0]))
					{
						foreach ($languages['default'] as $lang)
						{
							$languages['sef'][$lang->sef] = $lang;
							$languages['lang_code'][$lang->lang_code] = $lang;
						}
					}

					$cache->store($languages, 'languages');
				}
			}
		}

		return $languages[$key];
	}

	/**
	 * Method to return a list of languages.
	 *
	 * @param   string  $client  Application client id
	 *
	 * @return  array of language objects.
	 *
	 * @since  3.7.0
	 */
	public static function getCmsLanguages($client = null)
	{
		
		// To avoid doing duplicate database queries.
		static $languages = null;

		if (!isset($languages) || ($client !== null && !isset($languages[$client])))
		{
			if (!isset($languages))
			{
				$languages = array();
			}

			// TO DO: installation languages.
			$clients = ($client === null) ? array(0, 1) : array($client);
	
			foreach ($clients as $clientId)
			{
				if (isset($languages[$clientId]))
				{
					continue;
				}

				// Get all installed languages from the extensions.
				$db = JFactory::getDbo();
				$query = $db->getQuery(true)
					->select($db->quoteName(array('extension_id', 'client_id')))
					->select($db->quoteName('name', 'extension_name'))
					->select($db->quoteName('element', 'lang_code'))
					->select($db->quoteName('enabled', 'extension_enabled'))
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('type') . ' = ' . $db->quote('language'))
					->where($db->quoteName('client_id') . ' = ' . $clientId);

				$db->setQuery($query);

				$languages[$clientId] = $db->loadObjectList('lang_code');

				// Get all installed languages from the folders.
				$clientPath         = $clientId ? JPATH_ADMINISTRATOR : JPATH_BASE;
				$installedLanguages = JLanguage::getKnownLanguages($clientPath);

				foreach($installedLanguages as $langCode => $installedLanguage)
				{
					if (!isset($languages[$clientId][$langCode]))
					{
						$languages[$clientId][$langCode]            = new stdClass();
						$languages[$clientId][$langCode]->client_id = $clientId;
						$languages[$clientId][$langCode]->lang_code = $langCode;
					}
					$languages[$clientId][$langCode]->name       = $installedLanguage['name'];
					$languages[$clientId][$langCode]->rtl        = $installedLanguage['rtl'];
					$languages[$clientId][$langCode]->installed  = 1;
				}

				if ($clientId == 0)
				{
					$query = $db->getQuery(true)
						->select($db->quoteName(array('lang_id', 'lang_code', 'title', 'title_native', 'sef', 'image', 'published', 'access', 'ordering')))
						->from($db->quoteName('#__languages'));
					$db->setQuery($query);

					$contentLanguages = $db->loadObjectList('lang_code');

					foreach($contentLanguages as $langCode => $contentLanguage)
					{
						if (!isset($languages[$clientId][$langCode]))
						{
							$languages[$clientId][$langCode]            = new stdClass();
							$languages[$clientId][$langCode]->client_id = $clientId;
							$languages[$clientId][$langCode]->lang_code = $langCode;
						}
						$languages[$clientId][$langCode]->lang_id      = $contentLanguage->lang_id;
						$languages[$clientId][$langCode]->title        = $contentLanguage->title;
						$languages[$clientId][$langCode]->title_native = $contentLanguage->title_native;
						$languages[$clientId][$langCode]->sef          = $contentLanguage->sef;
						$languages[$clientId][$langCode]->image        = $contentLanguage->image;
						$languages[$clientId][$langCode]->published    = $contentLanguage->published;
						$languages[$clientId][$langCode]->access       = $contentLanguage->access;
						$languages[$clientId][$langCode]->ordering     = $contentLanguage->ordering;
					}

					$db = JFactory::getDbo();
					$query = $db->getQuery(true)
						->select($db->quoteName(array('id', 'language', 'access', 'published')))
						->from($db->quoteName('#__menu'))
						->where($db->quoteName('home') . ' = 1')
						->where($db->quoteName('client_id') . ' = 0');
					$db->setQuery($query);

					$homes = $db->loadObjectList();

					foreach($homes as $key => $home)
					{
						if (isset($languages[$clientId][$home->language]))
						{
							$languages[$clientId][$home->language]->home_id        = $home->id;
							$languages[$clientId][$home->language]->home_access    = $home->access;
							$languages[$clientId][$home->language]->home_published = $home->published;
						}
					}
				}

				// Check if language is available
				foreach($languages[$clientId] as $langCode => $language)
				{
					if (!isset($language->installed))
					{
						$language->installed = null;
					}
					if (!isset($language->extension_enabled))
					{
						$language->extension_enabled = null;
					}
					$languages[$clientId][$langCode]->available = 0;
					if ($clientId == 0)
					{
						if (!isset($language->published))
						{
							$language->published = null;
						}
						if ($language->published == 1 && $language->installed == 1 && $language->extension_enabled == 1)
						{
							$languages[$clientId][$langCode]->available = 1;
						}
					}
					elseif ($clientId == 1)
					{
						if ($language->installed == 1 && $language->extension_enabled == 1)
						{
							$languages[$clientId][$langCode]->available = 1;
						}
					}
				}
			}
		}

		return $languages;
	}

	/**
	 * Method to return a list of languages.
	 *
	 * @param   string  $clientId   Application client id
	 * @param   string  $available  Extension state
	 *
	 * @return  array of language objects.
	 *
	 * @since  3.7.0
	 */
	public static function getAvailableLanguages($client = null, $available = true)
	{
		$availableLanguages = self::getCmsLanguages();

		// Return only available languages.
		if ($available)
		{
			foreach($availableLanguages as $clientId => $lang)
			{
				foreach($lang as $langCode => $language)
				{
					if ($availableLanguages[$clientId][$langCode]->available != $available)
					{
						unset($availableLanguages[$clientId][$langCode]);
					}
				}
			}
		}

		if ($client === null)
		{
			return $availableLanguages;
		}

		return $availableLanguages[$clientId];
	}
}
