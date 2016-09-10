<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_languages
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('MenusHelper', JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');

/**
 * Helper for mod_languages
 *
 * @package     Joomla.Site
 * @subpackage  mod_languages
 *
 * @since       1.6.0
 */
abstract class ModLanguagesHelper
{
	/**
	 * Gets a list of available languages
	 *
	 * @param   \Joomla\Registry\Registry  &$params  module params
	 *
	 * @return  array
	 */
	public static function getList(&$params)
	{
		$languages = JLanguageHelper::getAvailableSiteLanguages();
		$app       = JFactory::getApplication();
		$menu      = $app->getMenu();

		// Load associations
		$assoc = JLanguageAssociations::isEnabled();

		if ($assoc)
		{
			$active = $menu->getActive();

			if ($active)
			{
				$associations = MenusHelper::getAssociations($active->id);
			}

			// Load component associations
			$class = str_replace('com_', '', $app->input->get('option')) . 'HelperAssociation';
			JLoader::register($class, JPATH_COMPONENT_SITE . '/helpers/association.php');

			if (class_exists($class) && is_callable(array($class, 'getAssociations')))
			{
				$cassociations = call_user_func(array($class, 'getAssociations'));
			}
		}

		$multilang   = JLanguageMultilang::isEnabled();
		$defaultHome = $menu->getDefault('*');

		// Filter allowed languages
		foreach ($languages as $i => $language)
		{
			if (!$multilang)
			{
				$language->link = JRoute::_('index.php?Itemid=' . $defaultHome->id);
				continue;
			}

			// Do not display language without specific home menu
			if (isset($cassociations[$language->lang_code]))
			{
				$language->link = JRoute::_($cassociations[$language->lang_code] . '&lang=' . $language->sef);
				continue;
			}
			
			if (isset($associations[$language->lang_code]) && $menu->getItem($associations[$language->lang_code]))
			{
				$language->link = JRoute::_('index.php?lang=' . $language->sef . '&Itemid=' . $associations[$language->lang_code]);
				continue;
			}

			if ($language->active)
			{
				$language->link = JUri::getInstance()->toString(array('path', 'query'));
				continue;
			}

			$languageHome = $menu->getDefault($language->lang_code);
			$itemid       = $languageHome ? $languageHome->id : $defaultHome->id;
			$language->link = JRoute::_('index.php?lang=' . $language->sef . '&Itemid=' . $itemid);
		}

		return $languages;
	}
}
