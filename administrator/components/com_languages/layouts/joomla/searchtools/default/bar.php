<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

$data = $displayData;

if ($data['view'] instanceof LanguagesViewInstalled)
{
	// We will get the client filter & remove it from the form filters
	$clientIdField = $data['view']->filterForm->getField('client_id');
?>
	<div class="js-stools-field-filter js-stools-client_id">
		<?php echo $clientIdField->input; ?>
	</div>
<?php
}

if ($data['view'] instanceof LanguagesViewOverrides)
{
	// We will get the client filter & remove it from the form filters
	$LanguageClientIdField = $data['view']->filterForm->getField('language_client');
?>
	<div class="js-stools-field-filter js-stools-language_client">
		<?php echo $LanguageClientIdField->input; ?>
	</div>
<?php
}
// Display the main joomla layout
echo JLayoutHelper::render('joomla.searchtools.default.bar', $data, null, array('component' => 'none'));
