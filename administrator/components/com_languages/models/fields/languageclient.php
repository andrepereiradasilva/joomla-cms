<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

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
		// Get client and language from the request to set the value.
		$app         = JFactory::getApplication();
		$clientId    = $app->getUserStateFromRequest('com_languages.overrides.client_id', 'client_id', 0, 'int');
		$languageTag = $app->getUserStateFromRequest('com_languages.overrides.filter_language', 'filter_language', '', 'cmd');

		// Get installed languages for the client.
		$clientPath = $clientId ? JPATH_ADMINISTRATOR : JPATH_SITE;
		$languages  = JLanguage::getKnownLanguages($clientPath);

		$options = array();

		foreach ($languages as $languageClientTag => $languageClient)
		{
			$options[] = JHtml::_('select.option', $languageClientTag, $languageClient['name']);
		}

		// Merge any additional options in the XML definition.
		return array_merge(parent::getOptions(), $options);
	}
}
