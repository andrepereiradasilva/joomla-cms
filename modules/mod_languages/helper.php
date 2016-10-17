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
		// If multilanguage is not enabled there are no languages.
		if (!JLanguageMultilang::isEnabled())
		{
			return array();
		}

		// Fetch the association links for each available site content languages.
		$languages           = JLanguageAssociations::getCurrentUriAssociations();
		$currentLanguage     = JFactory::getLanguage();
		$currentLanguageCode = $currentLanguage->getTag();

		// Fetch the association link for each available site content languages.
		foreach ($languages as $i => $language)
		{
			$language->active = $language->lang_code === $currentLanguageCode;

			// If current language get the rtl from current JLanguage metadata
			if ($language->active)
			{
				$language->rtl = $currentLanguage->isRtl();
				continue;
			}

			// If not loaded language fetch from metadata.
			$languageMetadata = JLanguage::getMetadata($language->lang_code);
			$language->rtl    = $languageMetadata['rtl'];
		}

		return $languages;
	}
}
