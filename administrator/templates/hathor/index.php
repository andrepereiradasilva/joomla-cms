<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Template.hathor
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var JDocumentHtml $this */

$app  = JFactory::getApplication();
$lang = JFactory::getLanguage();

// Output as HTML5
$this->setHtml5(true);

// jQuery needed by template.js
JHtml::_('jquery.framework');

// Add template js
JHtml::_('script', 'template.js', array('version' => 'auto', 'relative' => true));

// Add html5 shiv
JHtml::_('script', 'jui/html5.js', array('version' => 'auto', 'relative' => true, 'conditional' => 'lt IE 9'));

// Load optional RTL Bootstrap CSS
JHtml::_('bootstrap.loadCss', false, $this->direction);

// Load system style CSS
$this->addStyleSheetVersion($this->baseurl . '/templates/system/css/system.css');

// Load template CSS
$this->addStyleSheetVersion($this->baseurl . '/templates/' . $this->template . '/css/template.css');

// Load additional CSS styles for colors
if (!$this->params->get('colourChoice'))
{
	$colour = 'standard';
}
else
{
	$colour = htmlspecialchars($this->params->get('colourChoice'));
}

$this->addStyleSheetVersion($this->baseurl . '/templates/' . $this->template . '/css/colour_' . $colour . '.css');

// Load additional CSS styles for rtl sites
if ($this->direction == 'rtl')
{
	$this->addStyleSheetVersion($this->baseurl . '/templates/' . $this->template . '/css/template_rtl.css');
	$this->addStyleSheetVersion($this->baseurl . '/templates/' . $this->template . '/css/colour_' . $colour . '_rtl.css');
}

// Load additional CSS styles for bold Text
if ($this->params->get('boldText'))
{
	$this->addStyleSheetVersion($this->baseurl . '/templates/' . $this->template . '/css/boldtext.css');
}

// Load specific language related CSS
$languageCss = 'language/' . $lang->getTag() . '/' . $lang->getTag() . '.css';

if (file_exists($languageCss) && filesize($languageCss) > 0)
{
	$this->addStyleSheetVersion($languageCss);
}

// Load custom.css
$customCss = 'templates/' . $this->template . '/css/custom.css';

if (file_exists($customCss) && filesize($customCss) > 0)
{
	$this->addStyleSheetVersion($customCss);
}

// Logo file
if ($this->params->get('logoFile'))
{
	$logo = JUri::root() . $this->params->get('logoFile');
}
else
{
	$logo = $this->baseurl . '/templates/' . $this->template . '/images/logo.png';
}

$this->addScriptDeclaration("
	(function($){
		$(document).ready(function () {
			// Patches to fix some wrong render of chosen fields
			$('.chzn-container, .chzn-drop, .chzn-choices .search-field input').each(function (index) {
				$(this).css({
					'width': 'auto'
				});
			});
		});
	})(jQuery);
");
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<jdoc:include type="head" />
	<!--[if IE 8]><link href="<?php echo $this->baseurl; ?>/templates/<?php echo  $this->template; ?>/css/ie8.css" rel="stylesheet" /><![endif]-->
	<!--[if IE 7]><link href="<?php echo $this->baseurl; ?>/templates/<?php echo  $this->template; ?>/css/ie7.css" rel="stylesheet" /><![endif]-->
</head>
<body id="minwidth-body">
<div id="containerwrap" data-basepath="<?php echo JURI::root(true); ?>">
	<!-- Header Logo -->
	<div id="header">
		<!-- Site Title and Skip to Content -->
		<div class="title-ua">
			<h1 class="title"><?php echo $this->params->get('showSiteName') ? $app->get('sitename') . " " . JText::_('JADMINISTRATION') : JText::_('JADMINISTRATION'); ?></h1>
			<div id="skiplinkholder"><p><a id="skiplink" href="#skiptarget"><?php echo JText::_('TPL_HATHOR_SKIP_TO_MAIN_CONTENT'); ?></a></p></div>
		</div>
	</div><!-- end header -->
	<!-- Main Menu Navigation -->
	<div id="nav">
		<div id="module-menu">
			<h2 class="element-invisible"><?php echo JText::_('TPL_HATHOR_MAIN_MENU'); ?></h2>
			<jdoc:include type="modules" name="menu" />
		</div>
		<div class="clr"></div>
	</div><!-- end nav -->
	<!-- Status Module -->
	<div id="module-status">
		<jdoc:include type="modules" name="status"/>
	</div>
	<!-- Content Area -->
	<div id="content">
		<!-- Component Title -->
		<jdoc:include type="modules" name="title" />
		<!-- System Messages -->
		<jdoc:include type="message" />
		<!-- Sub Menu Navigation -->
		<div class="subheader">
			<?php if (!$app->input->getInt('hidemainmenu')) : ?>
				<h3 class="element-invisible"><?php echo JText::_('TPL_HATHOR_SUB_MENU'); ?></h3>
				<jdoc:include type="modules" name="submenu" style="xhtmlid" id="submenu-box" />
				<?php echo " " ?>
			<?php else : ?>
				<div id="no-submenu"></div>
			<?php endif; ?>
		</div>
		<!-- Toolbar Icon Buttons -->
		<div class="toolbar-box">
			<jdoc:include type="modules" name="toolbar" style="xhtml" />
			<div class="clr"></div>
		</div>
		<!-- Beginning of Actual Content -->
		<div id="element-box">
			<div id="container-collapse" class="container-collapse"></div>
			<p id="skiptargetholder"><a id="skiptarget" class="skip" tabindex="-1"></a></p>
			<!-- The main component -->
			<jdoc:include type="component" />
			<div class="clr"></div>
		</div><!-- end of element-box -->
		<noscript>
			<?php echo JText::_('JGLOBAL_WARNJAVASCRIPT'); ?>
		</noscript>
		<div class="clr"></div>
	</div><!-- end of content -->
	<div class="clr"></div>
</div><!-- end of containerwrap -->
<!-- Footer -->
<div id="footer">
	<jdoc:include type="modules" name="footer" style="none" />
	<p class="copyright">
		<?php
		// Fix wrong display of Joomla!® in RTL language
		if ($lang->isRtl())
		{
			$joomla = '<a href="https://www.joomla.org" target="_blank">Joomla!</a><sup>&#174;&#x200E;</sup>';
		}
		else
		{
			$joomla = '<a href="https://www.joomla.org" target="_blank">Joomla!</a><sup>&#174;</sup>';
		}
		echo JText::sprintf('JGLOBAL_ISFREESOFTWARE', $joomla);
		?>
	</p>
</div>
</body>
</html>
