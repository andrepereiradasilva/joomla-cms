<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.isis
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$app             = JFactory::getApplication();
$doc             = JFactory::getDocument();
$lang            = JFactory::getLanguage();
$this->language  = $doc->language;
$this->direction = $doc->direction;

$assetOptions = array('relative' => true, 'version' => 'auto');

// Add JavaScript Frameworks.
JHtml::_('bootstrap.framework');

// Add template javascript code
JHtml::_('script', 'js/template.js', $assetOptions);

// Add template style file.
JHtml::_('stylesheet', 'css/template' . ($this->direction == 'rtl' ? '-rtl' : '') . '.css', $assetOptions);

// Load language related style file (if exists).
JHtml::_('stylesheet', 'language/' . $lang->getTag() . '/' . $lang->getTag() . '.css', $assetOptions);

// Add the custom style file (if exists).
JHtml::_('stylesheet', 'css/custom.css', $assetOptions);

// Add rtl bootstrap style (if text direction is rtl).
JHtml::_('bootstrap.loadCss', false, $this->direction);
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<jdoc:include type="head" />
	<!--[if lt IE 9]>
		<script src="<?php echo JUri::root(true); ?>/media/jui/js/html5.js"></script>
	<![endif]-->

	<!-- Link color -->
	<?php if ($this->params->get('linkColor')) : ?>
		<style type="text/css">
			a
			{
				color: <?php echo $this->params->get('linkColor'); ?>;
			}
		</style>
	<?php endif; ?>
</head>
<body class="contentpane component">
	<jdoc:include type="message" />
	<jdoc:include type="component" />
</body>
</html>
