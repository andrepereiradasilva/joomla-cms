<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Form Field class for the Joomla Platform.
 * Provides a list of published site languages
 *
 * @see    JFormFieldLanguage for a select list of application languages.
 * @since  3.5
 */
class JFormFieldFrontend_Language extends JFormAbstractlist
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  3.5
	 */
	public $type = 'Frontend_Language';

	/**
	 * Method to get the field options for published site languages.
	 *
	 * @return  array  The options the field is going to show.
	 *
	 * @since   3.5
	 */
	protected function getOptions()
	{
		$languagesList = JLanguageHelper::createLanguageList(null, JPATH_SITE, false, true);

		foreach ($languagesList as &$language)
		{
			$language = ArrayHelper::toObject($language, 'stdClass');
		}

		return array_merge(parent::getOptions(), $languagesList);
	}
}
