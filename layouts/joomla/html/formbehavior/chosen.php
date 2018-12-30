<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Layout variables
 * ---------------------
 *
 * @var  string   $selector       The id of the field
 * @var  array    $options        The options array
 * @var  boolean  $debug          Are we in debug mode?
 */

extract($displayData);

// Include jQuery
HTMLHelper::_('jquery.framework');
HTMLHelper::_('script', 'jui/chosen.jquery.min.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false, 'detectDebug' => $debug));
HTMLHelper::_('stylesheet', 'jui/chosen.css', array('version' => 'auto', 'relative' => true));

// Options array to json options string
$options_str = json_encode($options, ($debug && defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : false));

Factory::getDocument()->addScriptDeclaration(
	'
	jQuery(function ($) {
		initChosen();
		$("body").on("subform-row-add", initChosen);

		function initChosen(event, container)
		{
			container = container || document;
			$(container).find(' . json_encode($selector) . ').chosen(' . $options_str . ');
		}
	});
	'
);
