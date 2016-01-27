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
		$lang      = JFactory::getLanguage();
		$langTag   = $lang->getTag();
		$langRtl   = $lang->isRtl();		
		$languages = JLanguageHelper::getViewLanguages('lang_code');
		$multilang = JLanguageMultilang::isEnabled();

		// Load the association links.
		$associationLinks = JLanguageAssociations::getAssociationsLinks(true);

		// Add rtl an the association link to languages array
		foreach ($languages as $i => $language)
		{
			// If multilanguage is not enabled, use only the default language link
			if (!$multilang && !$language->default)
			{
				unset($languages[$i]);
			}
	
			$language->active = ($language->lang_code == $langTag);

			// Fetch language rtl
			// If already loaded language get it from current JLanguage metadata
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

			$language->link = $associationLinks[$language->lang_code];
		}

		return $languages;
	}
}
