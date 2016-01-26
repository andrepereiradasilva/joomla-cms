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
		$app        = JFactory::getApplication();
		$menu       = $app->getMenu();
		$lang       = JFactory::getLanguage();
		$langTag    = $lang->getTag();
		$langRtl    = $lang->isRtl();		
		$languages  = JLanguageHelper::getLanguages();
		$levels     = JFactory::getUser()->getAuthorisedViewLevels();
		$sitelangs  = JLanguageMultilang::getSiteLangs();
		$multilang  = JLanguageMultilang::isEnabled();
		$homepages  = JLanguageMultilang::getSiteHomePages();
		$currentUri = JUri::getInstance()->toString(array('path', 'query'));

		// If multilanguage and language associations enabled, load language associations.
		// Note that if the associations are not loaded yet by the language filter plugin onAfterDispatch event
		// or other previous event, they will be loaded for the first time here so it will take some more time to load.
		// This happens, for instance, when alternate_meta param of language filter plugin is set to false.
		if ($multilang)
		{
			$associations  = array();
			$cassociations = array();

			if (JLanguageAssociations::isEnabled())
			{
				// Current menu item language associations.
				if ($active = $menu->getActive())
				{
					$associations = JLanguageAssociations::getAssociations('com_menus', '#__menu', 'com_menus.item', $active->id, 'id', '', '');
				}

				// Current component language associations.
				$cassociations = JLanguageAssociations::getComponentAssociations($app->input->get('option'));
			}
		}

		// Filter allowed languages
		foreach ($languages as $i => $language)
		{
			// Do not display language without frontend UI
			if (!array_key_exists($language->lang_code, $sitelangs))
			{
				unset($languages[$i]);
			}
			// Do not display language without specific home menu
			elseif (!isset($homepages[$language->lang_code]))
			{
				unset($languages[$i]);
			}
			// Do not display language without authorized access level
			elseif (isset($language->access) && $language->access && !in_array($language->access, $levels))
			{
				unset($languages[$i]);
			}
			else
			{
				$language->active = ($language->lang_code == $langTag);

				// Fetch language rtl
				// If loaded language get from current JLanguage metadata
				if ($language->active)
				{
					$language->rtl = $langRtl;
				}
				// If not loaded language fetch metadata directly for performance
				else
				{
					$languageMetadata = JLanguage::getMetadata($language->lang_code);
					$language->rtl    = $languageMetadata['rtl'];
				}

				if ($multilang)
				{
					if (isset($cassociations[$language->lang_code]))
					{
						$language->link = JRoute::_($cassociations[$language->lang_code] . '&lang=' . $language->sef);
					}
					elseif (isset($associations[$language->lang_code]) && ($item = $menu->getItem($associations[$language->lang_code]->id)))
					{
						$language->link = JRoute::_($item->link . '&Itemid=' . $item->id . '&lang=' . $language->sef);
					}
					else
					{
						if ($language->active)
						{
							$language->link = $currentUri;
						}
						else
						{
							$itemid = isset($homepages[$language->lang_code]) ? $homepages[$language->lang_code]->id : $homepages['*']->id;
							$language->link = JRoute::_('index.php?lang=' . $language->sef . '&Itemid=' . $itemid);
						}
					}
				}
				else
				{
					$language->link = JRoute::_('&Itemid=' . $homepages['*']->id);
				}
			}
		}

		return $languages;
	}
}
