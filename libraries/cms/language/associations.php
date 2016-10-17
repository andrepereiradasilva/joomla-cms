<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Language
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

/**
 * Utitlity class for associations in multilang
 *
 * @since  3.1
 */
class JLanguageAssociations
{
	/**
	 * Get the associations.
	 *
	 * @param   string   $extension   The name of the component.
	 * @param   string   $tablename   The name of the table.
	 * @param   string   $context     The context
	 * @param   integer  $id          The primary key value.
	 * @param   string   $pk          The name of the primary key in the given $table.
	 * @param   string   $aliasField  If the table has an alias field set it here. Null to not use it
	 * @param   string   $catField    If the table has a catid field set it here. Null to not use it
	 *
	 * @return  array                The associated items
	 *
	 * @since   3.1
	 *
	 * @throws  Exception
	 */
	public static function getAssociations($extension, $tablename, $context, $id, $pk = 'id', $aliasField = 'alias', $catField = 'catid')
	{
		// To avoid doing duplicate database queries.
		static $multilanguageAssociations = array();

		// Multilanguage association array key. If the key is already in the array we don't need to run the query again, just return it.
		$queryKey = implode('|', func_get_args());
		if (!isset($multilanguageAssociations[$queryKey]))
		{
			$multilanguageAssociations[$queryKey] = array();

			$db = JFactory::getDbo();
			$categoriesExtraSql = (($tablename === '#__categories') ? ' AND c2.extension = ' . $db->quote($extension) : '');
			$query = $db->getQuery(true)
				->select($db->quoteName('c2.language'))
				->from($db->quoteName($tablename, 'c'))
				->join('INNER', $db->quoteName('#__associations', 'a') . ' ON a.id = c.' . $db->quoteName($pk) . ' AND a.context=' . $db->quote($context))
				->join('INNER', $db->quoteName('#__associations', 'a2') . ' ON a.key = a2.key')
				->join('INNER', $db->quoteName($tablename, 'c2') . ' ON a2.id = c2.' . $db->quoteName($pk) . $categoriesExtraSql);

			// Use alias field ?
			if (!empty($aliasField))
			{
				$query->select(
					$query->concatenate(
						array(
							$db->quoteName('c2.' . $pk),
							$db->quoteName('c2.' . $aliasField),
						),
						':'
					) . ' AS ' . $db->quoteName($pk)
				);
			}
			else
			{
				$query->select($db->quoteName('c2.' . $pk));
			}

			// Use catid field ?
			if (!empty($catField))
			{
				$query->join(
						'INNER',
						$db->quoteName('#__categories', 'ca') . ' ON ' . $db->quoteName('c2.' . $catField) . ' = ca.id AND ca.extension = ' . $db->quote($extension)
					)
					->select(
						$query->concatenate(
							array('ca.id', 'ca.alias'),
							':'
						) . ' AS ' . $db->quoteName($catField)
					);
			}

			$query->where('c.' . $pk . ' = ' . (int) $id);
			if ($tablename === '#__categories')
			{
				$query->where('c.extension = ' . $db->quote($extension));
			}

			$db->setQuery($query);

			try
			{
				$items = $db->loadObjectList('language');
			}
			catch (RuntimeException $e)
			{
				throw new Exception($e->getMessage(), 500, $e);
			}

			if ($items)
			{
				foreach ($items as $tag => $item)
				{
					// Do not return itself as result
					if ((int) $item->{$pk} != $id)
					{
						$multilanguageAssociations[$queryKey][$tag] = $item;
					}
				}
			}
		}

		return $multilanguageAssociations[$queryKey];
	}

	/**
	 * Method to determine if the language filter Items Associations parameter is enabled.
	 * This works for both site and administrator.
	 *
	 * @return  boolean  True if the parameter is implemented; false otherwise.
	 *
	 * @since   3.2
	 */
	public static function isEnabled()
	{
		// Flag to avoid doing multiple database queries.
		static $tested = false;

		// Status of language filter parameter.
		static $enabled = false;

		if (JLanguageMultilang::isEnabled())
		{
			// If already tested, don't test again.
			if (!$tested)
			{
				$plugin = JPluginHelper::getPlugin('system', 'languagefilter');

				if (!empty($plugin))
				{
					$params = new Registry($plugin->params);
					$enabled  = (boolean) $params->get('item_associations', true);
				}

				$tested = true;
			}
		}

		return $enabled;
	}

	/**
	 * Method to get the language associations for the current url in all available languages.
	 *
	 * @return  array  Array of languages.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getCurrentUriAssociations()
	{
		static $currentUriAssociations = null;

		$app                   = JFactory::getApplication();
		$currentParameters     = $app->getRouter()->getVars();
		$currentInternalUrl    = 'index.php?' . http_build_query($currentParameters);

		$key = md5($currentInternalUrl);

		if (!isset($currentUriAssociations[$key]))
		{
			$menu                         = $app->getMenu();
			$currentUriAssociations[$key] = JLanguageMultilang::getAvailableSiteLanguages('lang_code');
			$active                       = $menu->getActive();
			$isHome                       = $active && $active->home && JRoute::_($active->link . '&Itemid=' . $active->id) == JRoute::_($currentInternalUrl);
			$currentLanguage              = JFactory::getLanguage();
			$currentLanguageCode          = $currentLanguage->getTag();
			$associationsEnabled          = JLanguageAssociations::isEnabled();
			$multilanguageEnabled         = JLanguageMultilang::isEnabled();
			$menuItemAssociations         = array();
			$componentAssociations        = array();
			$defaultHomeUri               = JRoute::_('index.php?Itemid=' . $menu->getDefault('*')->id);

			// Check if associations are enabled, if so fetch them.
			if ($multilanguageEnabled && $associationsEnabled)
			{
				// If in a menu item, check if we are on home item and, if not, get the menu associations.
				if (!$isHome && $active)
				{
					JLoader::register('MenusHelper', JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');
					$menuItemAssociations = MenusHelper::getAssociations($active->id);
				}

				// If not in home, load component associations.
				if (!$isHome)
				{
					$option     = strtolower($app->input->get('option', '', 'string'));
					$helperFile = JPATH_ROOT . '/components/' . $option . '/helpers/association.php';

					if (file_exists($helperFile))
					{
						$componentClass = ucfirst(str_replace('com_', '', $option)) . 'HelperAssociation';
						JLoader::register($componentClass, JPath::clean($helperFile));

						if (class_exists($componentClass) && is_callable(array($componentClass, 'getAssociations')))
						{
							$componentAssociations = call_user_func(array($componentClass, 'getAssociations'));
						}
					}
				}
			}

			// Fetch the association link for each available site content languages.
			foreach ($currentUriAssociations[$key] as $i => $language)
			{
				$language->association = 1;

				switch (true)
				{
					case (!$multilanguageEnabled):
						$language->link        = $defaultHomeUri;
						$language->association = 0;
						break;

					// Language home page, the association is the other language home page.
					case ($isHome):
						$language->link = JRoute::_('index.php?Itemid=' . $language->home_id . '&lang=' . $language->sef);
						break;

					// If current language use the current url.
					case ($language->lang_code === $currentLanguageCode):
						$language->link = JRoute::_($currentInternalUrl);
						break;

					// A component item association exists. Use it.
					case ($associationsEnabled && isset($componentAssociations[$i])):
						$language->link = JRoute::_($componentAssociations[$i] . '&lang=' . $language->sef);
						break;

					// A menu item association exists. Use it.
					case ($associationsEnabled && isset($menuItemAssociations[$i]) && ($item = $menu->getItem($menuItemAssociations[$i]))):
						$language->link = JRoute::_($item->link . '&Itemid=' . $item->id . '&lang=' . $language->sef);
						break;

					// If is a component without menu item (no active menu, ex: /en/component/content/).
					case ($associationsEnabled && !isset($active)):
						$urlParameters  = array_replace($currentParameters, array('lang' => $language->sef));
						$language->link = JRoute::_('index.php?' . http_build_query($urlParameters));
						break;

					// No association. Fallback to language home.
					default:
						$language->link        = JRoute::_('index.php?Itemid=' . $language->home_id . '&lang=' . $language->sef);
						$language->association = 0;
						break;
				}
			}
		}

		return $currentUriAssociations[$key];
	}
}
