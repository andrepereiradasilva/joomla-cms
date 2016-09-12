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
		// Fetch the association links for each available site content languages.
		$languages           = JLanguageAssociations::getCurrentUriAssociations();
		$currentLanguage     = JFactory::getLanguage();

		// Fetch the association link for each available site content languages.
		foreach ($languages as $i => $language)
		{
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
		}

		return $languages;
	}
}
