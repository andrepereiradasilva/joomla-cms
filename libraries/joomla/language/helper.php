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
		$languages = static::getKnownLanguages($basePath);

		if ($installed)
		{
			$installedLanguages = static::getInstalledLanguages($basePath == JPATH_ADMINISTRATOR ? 1 : 0);

			// Remove not installed languages.
			$languages = array_intersect_key($languages, $installedLanguages);
		}

		$list = array();

		foreach ($languages as $languageCode => $metadata)
		{
			$list[] = array(
				'text'     => $metadata['name'],
				'value'    => $languageCode,
				'selected' => $languageCode == $actualLanguage ? 'selected="selected"' : null,
			);
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
			$systemLangs  = static::getLanguages();

			foreach ($browserLangs as $browserLang)
			{
				// Slice out the part before ; on first step, the part before - on second, place into array
				$browserLang         = substr($browserLang, 0, strcspn($browserLang, ';'));
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
				$knownLangs = static::getKnownLanguages(JPATH_BASE);

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
	 * Get a list of installed languages.
	 *
	 * @param   integer  $clientId  The client app id.
	 *
	 * @return  array  Array with the language code and name.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getInstalledLanguages($clientId = null)
	{
		static $installedLanguages = null;

		if (is_null($installedLanguages))
		{
			$db = JFactory::getDbo();

			$query = $db->getQuery(true)
				->select($db->quoteName(array('element', 'name', 'client_id', 'extension_id')))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('language'))
				->where($db->quoteName('state') . ' = 0')
				->where($db->quoteName('enabled') . ' = 1');

			$installedLanguages = $db->setQuery($query)->loadObjectList('element');
		}

		$languages = $installedLanguages;

		if (!is_null($clientId))
		{
			foreach($languages as $languageCode => $language)
			{
				if ((int) $language->client_id !== $clientId)
				{
					unset($languages[$languageCode]);
				}
			}
		}

		return $languages;
	}

	/**
	 * Checks if a language exists.
	 *
	 * This is a simple, quick check for the directory that should contain language files for the given user.
	 *
	 * @param   string  $lang      Language to check.
	 * @param   string  $basePath  Optional path to check.
	 *
	 * @return  boolean  True if the language exists.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function exists($lang, $basePath = JPATH_BASE)
	{
		static $paths = array();

		// Return false if no language was specified
		if (!$lang)
		{
			return false;
		}

		$path = $basePath . '/language/' . $lang;

		// Return previous check results if it exists
		if (isset($paths[$path]))
		{
			return $paths[$path];
		}

		// Check if the language exists
		$paths[$path] = is_dir($path);

		return $paths[$path];
	}

	/**
	 * Returns an associative array holding the metadata.
	 *
	 * @param   string  $languageCode  The name of the language.
	 *
	 * @return  mixed  If language code exists return key/value pair with the language metadata, otherwise return NULL.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getMetadata($languageCode)
	{
		try
		{
			return self::parseXMLLanguageFile(static::getLanguagePath(JPATH_BASE, $languageCode) . '/' . $languageCode . '.xml');
		}
		catch (RuntimeException $e)
		{
			return null;
		}
	}

	/**
	 * Returns a list of known languages for an area
	 *
	 * @param   string  $basePath  The basepath to use
	 *
	 * @return  array  key/value pair with the language file and real name.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getKnownLanguages($basePath = JPATH_BASE)
	{
		return static::parseLanguageFiles(static::getLanguagePath($basePath));
	}

	/**
	 * Get the path to a language
	 *
	 * @param   string  $basePath  The basepath to use.
	 * @param   string  $language  The language tag.
	 *
	 * @return  string  language related path or null.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getLanguagePath($basePath = JPATH_BASE, $language = null)
	{
		return $basePath . '/language' . (!empty($language) ? '/' . $language : '');
	}

	/**
	 * Searches for language directories within a certain base dir.
	 *
	 * @param   string  $dir  directory of files.
	 *
	 * @return  array  Array holding the found languages as filename => real name pairs.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function parseLanguageFiles($dir = null)
	{
		$languages = array();

		// Search main language directory for subdirectories
		foreach (glob($dir . '/*', GLOB_NOSORT | GLOB_ONLYDIR) as $directory)
		{
			// But only directories with lang code format
			if (preg_match('#/[a-z]{2,3}-[A-Z]{2}$#', $directory))
			{
				$languageCode = pathinfo($directory, PATHINFO_FILENAME);

				// Get installed language metadata from xml file and merge it with lang array
				try
				{
					$languages[$languageCode] = self::parseXMLLanguageFile($directory . '/' . $languageCode . '.xml');
				}
				catch (RuntimeException $e)
				{
					// Do nothing.
				}
			}
		}

		return $languages;
	}

	/**
	 * Parse XML file for language information.
	 *
	 * @param   string  $path  Path to the XML files.
	 *
	 * @return  array  Array holding the found metadata as a key => value pair.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  RuntimeException
	 */
	public static function parseXMLLanguageFile($path)
	{
		if (!is_readable($path))
		{
			throw new RuntimeException('File not found or not readable');
		}

		// Try to load the file
		$xml = simplexml_load_string(file_get_contents($path));

		if (!$xml)
		{
			return;
		}

		// Check that it's a metadata file
		if ((string) $xml->getName() != 'metafile')
		{
			return;
		}

		$metadata = array();

		foreach ($xml->metadata->children() as $child)
		{
			$metadata[$child->getName()] = (string) $child;
		}

		return $metadata;
	}
}
