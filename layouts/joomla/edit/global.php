<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

$app       = JFactory::getApplication();
$form      = $displayData->getForm();
$input     = $app->input;
$component = $input->getCmd('option', 'com_content');

if ($component === 'com_categories')
{
	$extension = $input->getCmd('extension', 'com_content');
	$parts     = explode('.', $extension);
	$component = $parts[0];
}

$saveHistory = JComponentHelper::getParams($component)->get('save_history', 0);

$fields = $displayData->get('fields') ?: [
	['parent', 'parent_id'],
	['published', 'state', 'enabled'],
	['category', 'catid'],
	'featured',
	'sticky',
	'access',
	'language',
	'tags',
	'note',
	'version_note',
];

$hiddenFields = $displayData->get('hidden_fields') ?: [];

if (!$saveHistory)
{
	$hiddenFields[] = 'version_note';
}

$html   = [];
$html[] = '<fieldset class="form-vertical form-no-margin">';

foreach ($fields as $field)
{
	foreach ((array) $field as $f)
	{
		if ($form->getField($f))
		{
			if (in_array($f, $hiddenFields))
			{
				$form->setFieldAttribute($f, 'type', 'hidden');
			}

			$html[] = $form->renderField($f);
			break;
		}
	}
}

$html[] = '</fieldset>';

echo implode('', $html);
