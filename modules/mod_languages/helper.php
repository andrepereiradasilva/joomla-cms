<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_languages
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

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
		$languages = JLanguageHelper::getAvailableSiteLanguages('lang_code');
		$app       = JFactory::getApplication();
		$menu      = $app->getMenu();

		// If not a multilingual site, all language point always to default home page.
		if (!JLanguageMultilang::isEnabled())
		{
			$defaultHomeUri = JRoute::_('index.php?Itemid=' . $menu->getDefault('*')->id);

			foreach ($languages as $i => $language)
			{
				$language->link = $defaultHomeUri;
			}

			return $languages;
		}

		$currentParameters     = $app->getRouter()->getVars();
		$currentInternalUrl    = 'index.php?' . http_build_query($currentParameters);
		$active                = $menu->getActive();
		$isHome                = $active && $active->home && JRoute::_($active->link . '&Itemid=' . $active->id) == JRoute::_($currentInternalUrl);
		$currentLanguage       = JFactory::getLanguage();
		$currentLanguageCode   = $currentLanguage->getTag();
		$associationsEnabled   = JLanguageAssociations::isEnabled();
		$menuItemAssociations  = array();
		$componentAssociations = array();

		// Check if associations are enabled, if so fetch them.
		if ($associationsEnabled)
		{
			// If in a menu item, check if we are on home item and, if not, get the menu associations.
			if (!$isHome && $active)
			{
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
		foreach ($languages as $i => $language)
		{
			$language->active = $language->lang_code === $currentLanguageCode;

			// If current language get the rtl from current JLanguage metadata
			if ($language->active)
			{
				$language->rtl = $currentLanguage->isRtl();
			}
			// If not loaded language fetch rtl directly for performance
			else
			{
				$languageMetadata = JLanguage::getMetadata($language->lang_code);
				$language->rtl    = $languageMetadata['rtl'];
			}

			switch (true)
			{
				// Language home page, the association is the other language home page.
				case ($isHome):
					$language->link = JRoute::_('index.php?Itemid=' . $language->home_id . '&lang=' . $language->sef);
					break;

				// If current language use the current url.
				case ($language->active):
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
					$language->link = JRoute::_('index.php?Itemid=' . $language->home_id . '&lang=' . $language->sef);
					break;
			}
		}

		return $languages;
	}
}
