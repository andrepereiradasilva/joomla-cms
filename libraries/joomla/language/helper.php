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

		return null;
	}

	/**
	 * Get available languages.
	 *
	 * @param   string  $key  Array key
	 *
	 * @return  array  An array of published languages
	 *
	 * @since   11.1
	 */
	public static function getLanguages($key = 'default')
	{
		static $languages = null;

		if (is_null($languages))
		{
			$languages = array();
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
				// Fetch all languages in the languages table.
				$db = JFactory::getDbo();
				$query = $db->getQuery(true)
					->select('*')
					->from($db->quoteName('#__languages'))
					->where($db->quoteName('published') . ' = 1')
					->order($db->quoteName('ordering') . ' ASC');
				$db->setQuery($query);
				$languages['lang_code'] = $db->loadObjectList('lang_code');

				// Add the default language
				$defaultLanguage = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
				foreach ($languages['lang_code'] as $language)
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
				foreach ($languages['lang_code'] as $language)
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
				foreach ($languages['lang_code'] as $language)
				{
					$language->homeid     = (!empty($homepages[$language->lang_code]->id)) ? $homepages[$language->lang_code]->id : 0;
					$language->homeaccess = (!empty($homepages[$language->lang_code]->level)) ? $homepages[$language->lang_code]->level : 0;
				}

				$languages['sef'] = array();
				$languages['default'] = array();

				if (count($languages['lang_code']) > 0)
				{
					foreach ($languages['lang_code'] as $lang)
					{
						$languages['sef'][$lang->sef] = $lang;
						$languages['default'][] = $lang;
					}
				}
			}
		}

		return $languages[$key];
	}

	/**
	 * Get view languages. An active language is published, the extension is enabled,
	 * has a homepage and the user can view the language and the homepage.	 
	 *
	 * @param   string  $key  Array key
	 *
	 * @return  array  An array with all active view languages.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getViewLanguages($key = 'default')
	{
		static $languages = array();

		if (!isset($languages[$key]))
		{
			$languages[$key] = self::getLanguages($key);
			$levels          = JFactory::getUser()->getAuthorisedViewLevels();

			foreach ($languages[$key] as $index => $language)
			{
				if (empty($language->extension_enabled))
				{
					unset($languages[$key][$index]);
				}
				if (empty($language->homeid))
				{
					unset($languages[$key][$index]);
				}
				if (isset($language->access) && $language->access != 0 && !in_array($language->access, $levels))
				{
					unset($languages[$key][$index]);
				}
				if (isset($language->homeaccess) && $language->homeaccess != 0 && !in_array($language->homeaccess, $levels))
				{
					unset($languages[$key][$index]);
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
		$languages = self::getLanguages('lang_code');
		foreach ($languages as $index => $language)
		{
			if ($language->default)
			{
				return $languages[$index];
			}
		}

		return new stdClass();
	}
}
