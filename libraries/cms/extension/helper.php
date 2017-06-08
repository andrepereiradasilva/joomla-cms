<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Extension Helper
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Extension Helper class.
 *
 * @since  __DEPLOY_VERSION__
 */
class JExtensionHelper
{
	protected static $coreExtensions = array(
		// Core component extensions
		'component' => array(
			'com_mailto',
			'com_wrapper',
			'com_admin',
			'com_banners',
			'com_cache',
			'com_categories',
			'com_checkin',
			'com_contact',
			'com_cpanel',
			'com_installer',
			'com_languages',
			'com_login',
			'com_media',
			'com_menus',
			'com_messages',
			'com_modules',
			'com_newsfeeds',
			'com_plugins',
			'com_search',
			'com_templates',
			'com_content',
			'com_config',
			'com_redirect',
			'com_users',
			'com_finder',
			'com_joomlaupdate',
			'com_tags',
			'com_contenthistory',
			'com_ajax',
			'com_postinstall',
			'com_fields',
			'com_associations',
		),
		// Core library extensions
		'library' => array(
			'phputf8',
			'joomla',
			'idna_convert',
			'fof',
			'phpass',
		),
		// Core module extensions
		'module' => array(
			'site' => array(
				'mod_articles_archive',
				'mod_articles_latest',
				'mod_articles_popular',
				'mod_banners',
				'mod_breadcrumbs',
				'mod_custom',
				'mod_feed',
				'mod_footer',
				'mod_login',
				'mod_menu',
				'mod_articles_news',
				'mod_random_image',
				'mod_related_items',
				'mod_search',
				'mod_stats',
				'mod_syndicate',
				'mod_users_latest',
				'mod_whosonline',
				'mod_wrapper',
				'mod_articles_category',
				'mod_articles_categories',
				'mod_languages',
				'mod_finder',
				'mod_tags_popular',
				'mod_tags_similar',
			),
			'administrator' => array(
				'mod_custom',
				'mod_feed',
				'mod_latest',
				'mod_logged',
				'mod_login',
				'mod_menu',
				'mod_popular',
				'mod_quickicon',
				'mod_status',
				'mod_submenu',
				'mod_title',
				'mod_toolbar',
				'mod_multilangstatus',
				'mod_version',
				'mod_stats_admin',
			),
		),
		// Core plugin extensions
		'plugin' => array(
			'system' => array(
				'languagefilter',
				'p3p',
				'cache',
				'debug',
				'log',
				'redirect',
				'remember',
				'sef',
				'logout',
				'languagecode',
				'highlight',
				'updatenotification',
				'stats',
				'fields',
			),
			'content' => array(
				'contact',
				'emailcloak',
				'loadmodule',
				'pagebreak',
				'pagenavigation',
				'vote',
				'joomla',
				'finder',
				'fields',
			),
			'user' => array(
				'contactcreator',
				'joomla',
				'profile',
			),
			'quickicon' => array(
				'joomlaupdate',
				'extensionupdate',
				'phpversioncheck',
			),
			'editors' => array(
				'codemirror',
				'none',
				'tinymce',
			),
			'editors-xtd' => array(
				'article',
				'image',
				'pagebreak',
				'readmore',
				'module',
				'menu',
				'contact',
				'fields',
			),
			'authentication' => array(
				'gmail',
				'joomla',
				'ldap',
				'cookie',
			),
			'twofactorauth' => array(
				'totp',
				'yubikey',
			),
			'installer' => array(
				'packageinstaller',
				'folderinstaller',
				'urlinstaller',
			),
			'extension' => array(
				'joomla',
			),
			'captcha' => array(
				'recaptcha',
			),
			'search' => array(
				'categories',
				'contacts',
				'content',
				'newsfeeds',
				'tags',
			),
			'finder' => array(
				'categories',
				'contacts',
				'content',
				'newsfeeds',
				'tags',
			),
			'fields' => array(
				'calendar',
				'checkboxes',
				'color',
				'editor',
				'imagelist',
				'integer',
				'list',
				'media',
				'radio',
				'sql',
				'text',
				'textarea',
				'url',
				'user',
				'usergrouplist',
			),
		),
		// Core template extensions
		'template' => array(
			'site' => array(
				'beez3',
				'protostar',
			),
			'administrator' => array(
				'hathor',
				'isis',
			),
		),
		// Core language extensions
		'language' => array(
			'site' => array(
				'en-GB',
			),
			'administrator' => array(
				'en-GB',
			),
		),
		// Core file extensions
		'file' => array(
			'joomla',
		),
		// Core package extensions
		'package' => array(
			'pkg_en-GB',
		),
	);

	/**
	 * Gets the core extensions ids.
	 *
	 * @return  array  Array of core extension ids.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getCoreExtensionIds()
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where('1 = 2');

		$clientIds = array(
			'site'          => 0,
			'administrator' => 1,
		);

		foreach (self::$coreExtensions as $extensionType => $extensionBlock)
		{
			// For plugin extensions that also have folders.
			if (in_array($extensionType, array('plugin'), true) === true)
			{
				foreach ($extensionBlock as $extensionFolder => $extensions)
				{
					$query->orWhere(array(
						$db->qn('type') . ' = ' . $db->q($extensionType),
						$db->qn('folder') . ' = ' . $db->q($extensionFolder),
						$db->qn('element') . ' IN (' . implode(', ', $db->q($extensions)) . ')',
					), 'AND');
				}

				continue;
			}

			// For module, template and language extensions that can be site or administrator.
			if (in_array($extensionType, array('module', 'template', 'language'), true) === true)
			{
				foreach ($extensionBlock as $extensionClient => $extensions)
				{
					$query->orWhere(array(
						$db->qn('type') . ' = ' . $db->q($extensionType),
						$db->qn('client_id') . ' = ' . $db->q($clientIds[$extensionClient]),
						$db->qn('element') . ' IN (' . implode(', ', $db->q($extensions)) . ')',
					), 'AND');
				}

				continue;
			}

			// All other extension types.
			$query->orWhere(array(
				$db->qn('type') . ' = ' . $db->q($extensionType),
				$db->qn('element') . ' IN (' . implode(', ', $db->q($extensionBlock)) . ')',
			), 'AND');
		}

		return $db->setQuery($query)->loadColumn();
	}

}
