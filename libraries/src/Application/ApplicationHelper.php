<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Application;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * Application helper functions
 *
 * @since  1.5
 */
class ApplicationHelper
{
	/**
	 * Client information array
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected static $_clients = array();

	/**
	 * Return the name of the request component [main component]
	 *
	 * @param   string  $default  The default option
	 *
	 * @return  string  Option (e.g. com_something)
	 *
	 * @since   1.6
	 */
	public static function getComponentName($default = null)
	{
		static $option;

		if ($option)
		{
			return $option;
		}

		$input = \JFactory::getApplication()->input;
		$option = strtolower($input->get('option'));

		if (empty($option))
		{
			$option = $default;
		}

		$input->set('option', $option);

		return $option;
	}

	/**
	 * Provides a secure hash based on a seed
	 *
	 * @param   string  $seed  Seed string.
	 *
	 * @return  string  A secure hash
	 *
	 * @since   3.2
	 */
	public static function getHash($seed)
	{
		return md5(\JFactory::getConfig()->get('secret') . $seed);
	}

	/**
	 * This method transliterates a string into a URL
	 * safe string or returns a URL safe UTF-8 string
	 * based on the global configuration
	 *
	 * @param   string  $string    String to process
	 * @param   string  $language  Language to transliterate to if unicode slugs are disabled
	 *
	 * @return  string  Processed string
	 *
	 * @since   3.2
	 */
	public static function stringURLSafe($string, $language = '')
	{
		if (\JFactory::getConfig()->get('unicodeslugs') == 1)
		{
			$output = \JFilterOutput::stringURLUnicodeSlug($string);
		}
		else
		{
			if ($language === '*' || $language === '')
			{
				$languageParams = ComponentHelper::getParams('com_languages');
				$language = $languageParams->get('site');
			}

			$output = \JFilterOutput::stringURLSafe($string, $language);
		}

		return $output;
	}

	/**
	 * Gets information on a specific client id.  This method will be useful in
	 * future versions when we start mapping applications in the database.
	 *
	 * This method will return a client information array if called
	 * with no arguments which can be used to add custom application information.
	 *
	 * @param   integer  $id      A client identifier
	 * @param   boolean  $byName  If True, find the client by its name
	 *
	 * @return  mixed  Object describing the client or false if not known
	 *
	 * @since   1.5
	 */
	public static function getClientInfo($id = null, $byName = false)
	{
		// Only create the array if it is empty
		if (empty(self::$_clients))
		{
			$obj = new \stdClass;

			// Site Client
			$obj->id = 0;
			$obj->name = 'site';
			$obj->path = JPATH_SITE;
			self::$_clients[0] = clone $obj;

			// Administrator Client
			$obj->id = 1;
			$obj->name = 'administrator';
			$obj->path = JPATH_ADMINISTRATOR;
			self::$_clients[1] = clone $obj;

			// Installation Client
			$obj->id = 2;
			$obj->name = 'installation';
			$obj->path = JPATH_INSTALLATION;
			self::$_clients[2] = clone $obj;
		}

		// If no client id has been passed return the whole array
		if ($id === null)
		{
			return self::$_clients;
		}

		// Are we looking for client information by id or by name?
		if (!$byName)
		{
			if (isset(self::$_clients[$id]))
			{
				return self::$_clients[$id];
			}
		}
		else
		{
			foreach (self::$_clients as $client)
			{
				if ($client->name == strtolower($id))
				{
					return $client;
				}
			}
		}

		return;
	}

	/**
	 * Get the cookie parameters.
	 *
	 * @param   array  $cookieParameters  The cookie parameters.
	 *
	 * @return  array  The cookie parameters.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getCookieParameters($cookieParameters = array())
	{
		// Use expires in cookie set and lifetime in other methods
		$timeKey = array_key_exists('lifetime', $cookieParameters) === true ? 'lifetime' : 'expires';

		$config = Factory::getConfig();

		if ((int) $config->get('cookie_auto', '0') === 0)
		{
			// Manual cookie parameters
			return array(
				$timeKey   => isset($cookieParameters[$timeKey]) ? $cookieParameters[$timeKey] : 0,
				'path'     => isset($cookieParameters['path']) ? $cookieParameters['path'] : $config->get('cookie_path', ''),
				'domain'   => isset($cookieParameters['domain']) ? $cookieParameters['domain'] : $config->get('cookie_domain', ''),
				'secure'   => isset($cookieParameters['secure']) ? $cookieParameters['secure'] : Factory::getApplication()->isHttpsForced(),
				'httponly' => isset($cookieParameters['httponly']) ? $cookieParameters['httponly'] : true,
				'samesite' => isset($cookieParameters['samesite']) ? $cookieParameters['samesite'] : $config->get('cookie_samesite', ''),
			);
		}

		// In auto cookie parameters path is dynamic. Takes in consideration: application being used, shared sessions and joomla directory of installation.
		$cookiePath = rtrim(Uri::base(true), '/');

		// In administrator application with shared sessions, the cookie path is the same for site and administrator applications.
		if (defined('JPATH_ROOT') === true && defined('JPATH_BASE') === true && defined('JPATH_ADMINISTRATOR') === true
			&& JPATH_BASE === JPATH_ADMINISTRATOR && (int) $config->get('shared_session', '0') === 1)
		{
			$cookiePath = str_replace(str_replace(JPATH_ROOT, '', JPATH_BASE), '', $cookiePath);
		}

		$cookiePath .= '/';

		// Auto cookie parameters, only expires/lifetime and secure parameters can be changed.
		return array(
			$timeKey   => isset($cookieParameters[$timeKey]) ? $cookieParameters[$timeKey] : 0,
			'path'     => $cookiePath,
			'domain'   => '',
			'secure'   => isset($cookieParameters['secure']) ? $cookieParameters['secure'] : Factory::getApplication()->isHttpsForced(),
			'httponly' => true,
			'samesite' => 'Strict',
		);
	}

	/**
	 * Destroy cookie.
	 *
	 * @param   string  $cookieName  Cookie name
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function destroyCookie($cookieName = '')
	{
		Factory::getApplication()->input->cookie->set(
			$cookieName,
			'',
			self::getCookieParameters(array('expires' => 1))
		);
	}

	/**
	 * Adds information for a client.
	 *
	 * @param   mixed  $client  A client identifier either an array or object
	 *
	 * @return  boolean  True if the information is added. False on error
	 *
	 * @since   1.6
	 */
	public static function addClientInfo($client)
	{
		if (is_array($client))
		{
			$client = (object) $client;
		}

		if (!is_object($client))
		{
			return false;
		}

		$info = self::getClientInfo();

		if (!isset($client->id))
		{
			$client->id = count($info);
		}

		self::$_clients[$client->id] = clone $client;

		return true;
	}

	/**
	 * Parse a XML install manifest file.
	 *
	 * XML Root tag should be 'install' except for languages which use meta file.
	 *
	 * @param   string  $path  Full path to XML file.
	 *
	 * @return  array  XML metadata.
	 *
	 * @since       1.5
	 * @deprecated  4.0 Use \JInstaller::parseXMLInstallFile instead.
	 */
	public static function parseXMLInstallFile($path)
	{
		\JLog::add('ApplicationHelper::parseXMLInstallFile is deprecated. Use \JInstaller::parseXMLInstallFile instead.', \JLog::WARNING, 'deprecated');

		return \JInstaller::parseXMLInstallFile($path);
	}

	/**
	 * Parse a XML language meta file.
	 *
	 * XML Root tag  for languages which is meta file.
	 *
	 * @param   string  $path  Full path to XML file.
	 *
	 * @return  array  XML metadata.
	 *
	 * @since       1.5
	 * @deprecated  4.0 Use \JInstaller::parseXMLInstallFile instead.
	 */
	public static function parseXMLLangMetaFile($path)
	{
		\JLog::add('ApplicationHelper::parseXMLLangMetaFile is deprecated. Use \JInstaller::parseXMLInstallFile instead.', \JLog::WARNING, 'deprecated');

		// Check if meta file exists.
		if (!file_exists($path))
		{
			return false;
		}

		// Read the file to see if it's a valid component XML file
		$xml = simplexml_load_file($path);

		if (!$xml)
		{
			return false;
		}

		/*
		 * Check for a valid XML root tag.
		 *
		 * Should be 'metafile'.
		 */
		if ($xml->getName() !== 'metafile')
		{
			unset($xml);

			return false;
		}

		$data = array();

		$data['name'] = (string) $xml->name;
		$data['type'] = $xml->attributes()->type;

		$data['creationDate'] = ((string) $xml->creationDate) ?: \JText::_('JLIB_UNKNOWN');
		$data['author'] = ((string) $xml->author) ?: \JText::_('JLIB_UNKNOWN');

		$data['copyright'] = (string) $xml->copyright;
		$data['authorEmail'] = (string) $xml->authorEmail;
		$data['authorUrl'] = (string) $xml->authorUrl;
		$data['version'] = (string) $xml->version;
		$data['description'] = (string) $xml->description;
		$data['group'] = (string) $xml->group;

		return $data;
	}
}
