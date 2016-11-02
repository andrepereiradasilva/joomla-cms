<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Utility class working with content language select lists
 *
 * @since  1.6
 */
abstract class JHtmlContentLanguage
{
	/**
	 * Get a list of the available content language items.
	 *
	 * @param   boolean  $all        True to include All (*)
	 * @param   boolean  $translate  True to translate All
	 *
	 * @return  string
	 *
	 * @see     JFormFieldContentLanguage
	 * @since   1.6
	 */
	public static function existing($all = false, $translate = false)
	{
		static $items = null;

		if ($items === null)
		{
			$items            = array();
			$contentLanguages = JLanguageHelper::getContentLanguages(false, true, 'lang_code', 'title', 'ASC');

			foreach($contentLanguages as $key => $language)
			{
				$extra                     = $language->published ? '' : ' [' . JText::_('JUNPUBLISHED') . ']';
				$items[$key]               = new stdClass;
				$items[$key]->value        = $language->lang_code;
				$items[$key]->text         = $language->title . $extra;
				$items[$key]->title_native = $language->title_native . $extra;
			}
		}

		if ($all)
		{
			$allItem        = new stdClass;
			$allItem->value = '*';
			$allItem->text  = $translate ? JText::alt('JALL', 'language') : 'JALL_LANGUAGE';

			return array_replace(array($allItem), $items);
		}

		return $items;
	}
}
