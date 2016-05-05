<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

require_once __DIR__ . '/../../helpers/overrides.php';

JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Joomla Framework.
 *
 * @since  3.5.2
 */
class JFormFieldLanguageClient extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var     string
	 * @since   3.5.2
	 */
	protected $type = 'LanguageClient';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects
	 *
	 * @since   3.5.2
	 */
	protected function getOptions()
	{
		$options = array();

		$languages = OverridesHelper::getLanguages();

		foreach ($languages as $languageClientTag => $languageClientText)
		{
			$options[] = JHtml::_('select.option', $languageClientTag, $languageClientText);
		}

		// Get client and language from the request to set the default value.
		$app      = JFactory::getApplication();
		$clientId = (int) $app->getUserStateFromRequest('com_languages.overrides.client_id', 'client_id', 0, 'int');
		$language = $app->getUserStateFromRequest('com_languages.overrides.language', 'language', 'en-GB', 'cmd');

		// Set the default value.
		$this->setValue($language . $clientId);

		// Merge any additional options in the XML definition.
		return array_merge(parent::getOptions(), $options);
	}
}
