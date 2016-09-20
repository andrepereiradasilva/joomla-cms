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
	 * Cached array of the content language items.
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected static $items = null;

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
		if (is_null($items))
		{
			$contentLanguages = JLanguageHelper::getContentLanguages(true, null, 'title', 'ASC');

			$items = array();

			foreach($contentLanguages as $key => $language)
			{
				$items[$key]->value         = $language->lang_code;
				$items[$key]->text          = $language->title;
				$items[$key]->title_native  = $language->title_native;
			}
		}

		if ($all)
		{
			$all_option = array(new JObject(array('value' => '*', 'text' => $translate ? JText::alt('JALL', 'language') : 'JALL_LANGUAGE')));

			return array_merge($all_option, $items);
		}
		else
		{
			return $items;
		}
	}
}
