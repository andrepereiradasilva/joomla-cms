<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Languages overrides helper.
 *
 * @since  3.5.2
 */
class OverridesHelper
{
	/**
	 * Method to get the current languages.
	 *
	 * @return  integer
	 */
	public static function getLanguages()
	{
		static $languages = null;

		// Try to load the data from internal storage.
		if (!is_null($languages))
		{
			return $languages;
		}

		// Get all languages of frontend and backend.
		$languages       = array();
		$site_languages  = JLanguage::getKnownLanguages(JPATH_SITE);
		$admin_languages = JLanguage::getKnownLanguages(JPATH_ADMINISTRATOR);

		// Create a single array of them.
		foreach ($site_languages as $tag => $language)
		{
			$languages[$tag . '0'] = JText::sprintf('COM_LANGUAGES_VIEW_OVERRIDES_LANGUAGES_BOX_ITEM', $language['name'], JText::_('JSITE'));
		}

		foreach ($admin_languages as $tag => $language)
		{
			$languages[$tag . '1'] = JText::sprintf('COM_LANGUAGES_VIEW_OVERRIDES_LANGUAGES_BOX_ITEM', $language['name'], JText::_('JADMINISTRATOR'));
		}

		// Sort it by language tag and by client after that.
		ksort($languages);

		return $languages;
	}
}
