<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

/**
 * Utility class for JavaScript behaviors
 *
 * @since  1.5
 */
abstract class JHtmlBehavior
{
	/**
	 * Array containing information for loaded files
	 *
	 * @var    array
	 * @since  2.5
	 */
	protected static $loaded = array();

	/**
	 * Method to load the MooTools framework into the document head
	 *
	 * If debugging mode is on an uncompressed version of MooTools is included for easier debugging.
	 *
	 * @param   boolean  $extras  Flag to determine whether to load MooTools More in addition to Core
	 * @param   mixed    $debug   Is debugging mode on? [optional]
	 *
	 * @return  void
	 *
	 * @since   1.6
	 * @deprecated 4.0 Update scripts to jquery
	 */
	public static function framework($extras = false, $debug = null)
	{
		$type = $extras ? 'more' : 'core';

		// Only load once
		if (isset(static::$loaded[__METHOD__][$type]))
		{
			return;
		}

		Log::add('JHtmlBehavior::framework is deprecated. Update to jquery scripts.', Log::WARNING, 'deprecated');

		// If no debugging value is set, use the configuration setting
		$debug = (boolean) ($debug === null ? JDEBUG : $debug);

		if ($type !== 'core' && !isset(static::$loaded[__METHOD__]['core']))
		{
			HTMLHelper::_('behavior.framework', false, $debug);
		}

		HTMLHelper::_('script', 'system/mootools-' . $type . '.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false, 'detectDebug' => $debug));

		// Keep loading core.js for BC reasons
		HTMLHelper::_('behavior.core');

		static::$loaded[__METHOD__][$type] = true;
	}

	/**
	 * Method to load core.js into the document head.
	 *
	 * Core.js defines the 'Joomla' namespace and contains functions which are used across extensions
	 *
	 * @return  void
	 *
	 * @since   3.3
	 */
	public static function core()
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		HTMLHelper::_('form.csrf');
		HTMLHelper::_('script', 'system/core.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));

		// Add core and base uri paths so javascript scripts can use them.
		Factory::getDocument()->addScriptOptions('system.paths', array('root' => JUri::root(true), 'base' => JUri::base(true)));

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Add unobtrusive JavaScript support for image captions.
	 *
	 * @param   string  $selector  The selector for which a caption behaviour is to be applied.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 *
	 * @Deprecated 4.0 Use native HTML figure tags.
	 */
	public static function caption($selector = 'img.caption')
	{
		Log::add('JHtmlBehavior::caption is deprecated. Use native HTML figure tags.', Log::WARNING, 'deprecated');

		// Only load once
		if (isset(static::$loaded[__METHOD__][$selector]))
		{
			return;
		}

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		HTMLHelper::_('script', 'system/caption.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));

		// Attach caption to document
		Factory::getDocument()->addScriptDeclaration(
			"jQuery(window).on('load',  function() {
				new JCaption('" . $selector . "');
			});"
		);

		// Set static array
		static::$loaded[__METHOD__][$selector] = true;
	}

	/**
	 * Add unobtrusive JavaScript support for form validation.
	 *
	 * To enable form validation the form tag must have class="form-validate".
	 * Each field that needs to be validated needs to have class="validate".
	 * Additional handlers can be added to the handler for username, password,
	 * numeric and email. To use these add class="validate-email" and so on.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 *
	 * @Deprecated 3.4 Use formvalidator instead
	 */
	public static function formvalidation()
	{
		Log::add('The use of formvalidation is deprecated use formvalidator instead.', Log::WARNING, 'deprecated');

		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		// Include MooTools framework
		HTMLHelper::_('behavior.framework');

		// Load the new jQuery code
		HTMLHelper::_('behavior.formvalidator');
	}

	/**
	 * Add unobtrusive JavaScript support for form validation.
	 *
	 * To enable form validation the form tag must have class="form-validate".
	 * Each field that needs to be validated needs to have class="validate".
	 * Additional handlers can be added to the handler for username, password,
	 * numeric and email. To use these add class="validate-email" and so on.
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	public static function formvalidator()
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		// Include core
		HTMLHelper::_('behavior.core');

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		// Add validate.js language strings
		Text::script('JLIB_FORM_FIELD_INVALID');

		HTMLHelper::_('script', 'system/punycode.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));
		HTMLHelper::_('script', 'system/validate.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Add unobtrusive JavaScript support for submenu switcher support
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public static function switcher()
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		HTMLHelper::_('script', 'system/switcher.js', array('framework' => true, 'version' => 'auto', 'relative' => true, 'detectBrowser' => false));

		Factory::getDocument()->addScriptDeclaration("
			document.switcher = null;
			jQuery(function($){
				var toggler = document.getElementById('submenu');
				var element = document.getElementById('config-document');
				if (element) {
					document.switcher = new JSwitcher(toggler, element);
				}
		});");

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Add unobtrusive JavaScript support for a combobox effect.
	 *
	 * Note that this control is only reliable in absolutely positioned elements.
	 * Avoid using a combobox in a slider or dynamic pane.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public static function combobox()
	{
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		// Include core
		HTMLHelper::_('behavior.core');

		HTMLHelper::_('script', 'system/combobox.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Add unobtrusive JavaScript support for a hover tooltips.
	 *
	 * Add a title attribute to any element in the form
	 * title="title::text"
	 *
	 * Uses the core Tips class in MooTools.
	 *
	 * @param   string  $selector  The class selector for the tooltip.
	 * @param   array   $params    An array of options for the tooltip.
	 *                             Options for the tooltip can be:
	 *                             - maxTitleChars  integer   The maximum number of characters in the tooltip title (defaults to 50).
	 *                             - offsets        object    The distance of your tooltip from the mouse (defaults to {'x': 16, 'y': 16}).
	 *                             - showDelay      integer   The millisecond delay the show event is fired (defaults to 100).
	 *                             - hideDelay      integer   The millisecond delay the hide hide is fired (defaults to 100).
	 *                             - className      string    The className your tooltip container will get.
	 *                             - fixed          boolean   If set to true, the toolTip will not follow the mouse.
	 *                             - onShow         function  The default function for the show event, passes the tip element
	 *                               and the currently hovered element.
	 *                             - onHide         function  The default function for the hide event, passes the currently
	 *                               hovered element.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public static function tooltip($selector = '.hasTip', $params = array())
	{
		$sig = md5($selector . serialize($params));

		if (isset(static::$loaded[__METHOD__][$sig]))
		{
			return;
		}

		// Include MooTools framework
		HTMLHelper::_('behavior.framework', true);

		// Setup options object. Note: Offsets needs an array in the format: array('x'=>20, 'y'=>30)
		$options = array(
			'maxTitleChars' => isset($params['maxTitleChars']) && $params['maxTitleChars'] ? (int) $params['maxTitleChars'] : 50,
			'offset'        => isset($params['offset']) && is_array($params['offset']) ? $params['offset'] : null,
			'showDelay'     => isset($params['showDelay']) ? (int) $params['showDelay'] : null,
			'hideDelay'     => isset($params['hideDelay']) ? (int) $params['hideDelay'] : null,
			'className'     => isset($params['className']) ? $params['className'] : null,
			'fixed'         => isset($params['fixed']) && $params['fixed'],
			'onShow'        => isset($params['onShow']) ? '\\' . $params['onShow'] : null,
			'onHide'        => isset($params['onHide']) ? '\\' . $params['onHide'] : null,
		);

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		// Attach tooltips to document
		Factory::getDocument()->addScriptDeclaration(
			"jQuery(function($) {
			 $('$selector').each(function() {
				var title = $(this).attr('title');
				if (title) {
					var parts = title.split('::', 2);
					var mtelement = document.id(this);
					mtelement.store('tip:title', parts[0]);
					mtelement.store('tip:text', parts[1]);
				}
			});
			var JTooltips = new Tips($('$selector').get(), " . HTMLHelper::getJSObject($options) . ");
		});");

		// Set static array
		static::$loaded[__METHOD__][$sig] = true;
	}

	/**
	 * Add unobtrusive JavaScript support for modal links.
	 *
	 * @param   string  $selector  The selector for which a modal behaviour is to be applied.
	 * @param   array   $params    An array of parameters for the modal behaviour.
	 *                             Options for the modal behaviour can be:
	 *                            - ajaxOptions
	 *                            - size
	 *                            - shadow
	 *                            - overlay
	 *                            - onOpen
	 *                            - onClose
	 *                            - onUpdate
	 *                            - onResize
	 *                            - onShow
	 *                            - onHide
	 *
	 * @return  void
	 *
	 * @since   1.5
	 * @deprecated 4.0  Use the modal equivalent from bootstrap
	 */
	public static function modal($selector = 'a.modal', $params = array())
	{
		// Load the necessary files if they haven't yet been loaded
		if (!isset(static::$loaded[__METHOD__]))
		{
			// Include MooTools framework
			HTMLHelper::_('behavior.framework');

			// Load the JavaScript and css
			HTMLHelper::_('script', 'system/modal.js', array('framework' => true, 'version' => 'auto', 'relative' => true, 'detectBrowser' => false));
			HTMLHelper::_('stylesheet', 'system/modal.css', array('version' => 'auto', 'relative' => true));
		}

		$sig = md5($selector . serialize($params));

		if (isset(static::$loaded[__METHOD__][$sig]))
		{
			return;
		}

		Log::add('JHtmlBehavior::modal is deprecated. Use the modal equivalent from bootstrap.', Log::WARNING, 'deprecated');

		// Setup options object
		$options = array(
			'ajaxOptions'   => isset($params['ajaxOptions']) && is_array($params['ajaxOptions']) ? $params['ajaxOptions'] : null,
			'handler'       => isset($params['handler']) ? $params['handler'] : null,
			'parseSecure'   => isset($params['parseSecure']) ? (bool) $params['parseSecure'] : null,
			'closable'      => isset($params['closable']) ? (bool) $params['closable'] : null,
			'closeBtn'      => isset($params['closeBtn']) ? (bool) $params['closeBtn'] : null,
			'iframePreload' => isset($params['iframePreload']) ? (bool) $params['iframePreload'] : null,
			'iframeOptions' => isset($params['iframeOptions']) && is_array($params['iframeOptions']) ? $params['iframeOptions'] : null,
			'size'          => isset($params['size']) && is_array($params['size']) ? $params['size'] : null,
			'shadow'        => isset($params['shadow']) ? $params['shadow'] : null,
			'overlay'       => isset($params['overlay']) ? $params['overlay'] : null,
			'onOpen'        => isset($params['onOpen']) ? $params['onOpen'] : null,
			'onClose'       => isset($params['onClose']) ? $params['onClose'] : null,
			'onUpdate'      => isset($params['onUpdate']) ? $params['onUpdate'] : null,
			'onResize'      => isset($params['onResize']) ? $params['onResize'] : null,
			'onMove'        => isset($params['onMove']) ? $params['onMove'] : null,
			'onShow'        => isset($params['onShow']) ? $params['onShow'] : null,
			'onHide'        => isset($params['onHide']) ? $params['onHide'] : null,
		);

		if (isset($params['fullScreen']) && (bool) $params['fullScreen'])
		{
			$options['size'] = array('x' => '\\jQuery(window).width() - 80', 'y' => '\\jQuery(window).height() - 80');
		}

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		// Attach modal behavior to document
		Factory::getDocument()->addScriptDeclaration("
		jQuery(function($) {
			SqueezeBox.initialize(" . HTMLHelper::getJSObject($options) . ");
			SqueezeBox.assign($('" . $selector . "').get(), {
				parse: 'rel'
			});
		});

		window.jModalClose = function () {
			SqueezeBox.close();
		};
		
		// Add extra modal close functionality for tinyMCE-based editors
		document.onreadystatechange = function () {
			if (document.readyState == 'interactive' && typeof tinyMCE != 'undefined' && tinyMCE)
			{
				if (typeof window.jModalClose_no_tinyMCE === 'undefined')
				{	
					window.jModalClose_no_tinyMCE = typeof(jModalClose) == 'function'  ?  jModalClose  :  false;
					
					jModalClose = function () {
						if (window.jModalClose_no_tinyMCE) window.jModalClose_no_tinyMCE.apply(this, arguments);
						tinyMCE.activeEditor.windowManager.close();
					};
				}
		
				if (typeof window.SqueezeBoxClose_no_tinyMCE === 'undefined')
				{
					if (typeof(SqueezeBox) == 'undefined')  SqueezeBox = {};
					window.SqueezeBoxClose_no_tinyMCE = typeof(SqueezeBox.close) == 'function'  ?  SqueezeBox.close  :  false;
		
					SqueezeBox.close = function () {
						if (window.SqueezeBoxClose_no_tinyMCE)  window.SqueezeBoxClose_no_tinyMCE.apply(this, arguments);
						tinyMCE.activeEditor.windowManager.close();
					};
				}
			}
		};
		");

		// Set static array
		static::$loaded[__METHOD__][$sig] = true;
	}

	/**
	 * JavaScript behavior to allow shift select in grids
	 *
	 * @param   string  $id  The id of the form for which a multiselect behaviour is to be applied.
	 *
	 * @return  void
	 *
	 * @since   1.7
	 */
	public static function multiselect($id = 'adminForm')
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__][$id]))
		{
			return;
		}

		// Include core
		HTMLHelper::_('behavior.core');

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		HTMLHelper::_('script', 'system/multiselect.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));

		// Attach multiselect to document
		Factory::getDocument()->addScriptDeclaration(
			"jQuery(document).ready(function() {
				Joomla.JMultiSelect('" . $id . "');
			});"
		);

		// Set static array
		static::$loaded[__METHOD__][$id] = true;
	}

	/**
	 * Add unobtrusive javascript support for a collapsible tree.
	 *
	 * @param   string  $id      An index
	 * @param   array   $params  An array of options.
	 * @param   array   $root    The root node
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public static function tree($id, $params = array(), $root = array())
	{
		// Include MooTools framework
		HTMLHelper::_('behavior.framework');

		HTMLHelper::_('script', 'system/mootree.js', array('framework' => true, 'version' => 'auto', 'relative' => true, 'detectBrowser' => false));
		HTMLHelper::_('stylesheet', 'system/mootree.css', array('version' => 'auto', 'relative' => true));

		if (isset(static::$loaded[__METHOD__][$id]))
		{
			return;
		}

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		// Setup options object
		$options = array(
			'div'      => array_key_exists('div', $params) ? $params['div'] : $id . '_tree',
			'mode'     => array_key_exists('mode', $params) ? $params['mode'] : 'folders',
			'grid'     => array_key_exists('grid', $params) ? '\\' . $params['grid'] : true,
			'theme'    => array_key_exists('theme', $params) ? $params['theme'] : HTMLHelper::_('image', 'system/mootree.gif', '', array(), true, true),
			// Event handlers
			'onExpand' => array_key_exists('onExpand', $params) ? '\\' . $params['onExpand'] : null,
			'onSelect' => array_key_exists('onSelect', $params) ? '\\' . $params['onSelect'] : null,
			'onClick'  => array_key_exists('onClick', $params) ? '\\' . $params['onClick']
				: '\\function(node){  window.open(node.data.url, node.data.target != null ? node.data.target : \'_self\'); }',
		);

		// Setup root node
		$rootNode = array(
			'text'     => array_key_exists('text', $root) ? $root['text'] : 'Root',
			'id'       => array_key_exists('id', $root) ? $root['id'] : null,
			'color'    => array_key_exists('color', $root) ? $root['color'] : null,
			'open'     => array_key_exists('open', $root) ? '\\' . $root['open'] : true,
			'icon'     => array_key_exists('icon', $root) ? $root['icon'] : null,
			'openicon' => array_key_exists('openicon', $root) ? $root['openicon'] : null,
			'data'     => array_key_exists('data', $root) ? $root['data'] : null,
		);

		$treeName = array_key_exists('treeName', $params) ? $params['treeName'] : '';

		// Attach tooltips to document
		Factory::getDocument()->addScriptDeclaration('jQuery(function(){
			tree' . $treeName . ' = new MooTreeControl(' . HTMLHelper::getJSObject($options) . ',' . HTMLHelper::getJSObject($rootNode) . ');
			tree' . $treeName . '.adopt(\'' . $id . '\');})'
		);

		// Set static array
		static::$loaded[__METHOD__][$id] = true;
	}

	/**
	 * Add unobtrusive JavaScript support for a calendar control.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 *
	 * @deprecated 4.0
	 */
	public static function calendar()
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		Log::add('JHtmlBehavior::calendar is deprecated as the static assets are being loaded in the relative layout.', Log::WARNING, 'deprecated');

		$tag      = Factory::getLanguage()->getTag();
		$attribs  = array('title' => Text::_('JLIB_HTML_BEHAVIOR_GREEN'), 'media' => 'all');

		HTMLHelper::_('stylesheet', 'system/calendar-jos.css', array('version' => 'auto', 'relative' => true), $attribs);
		HTMLHelper::_('script', $tag . '/calendar.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));
		HTMLHelper::_('script', $tag . '/calendar-setup.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));

		$translation = static::calendartranslation();

		if ($translation)
		{
			Factory::getDocument()->addScriptDeclaration($translation);
		}

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Add unobtrusive JavaScript support for a color picker.
	 *
	 * @return  void
	 *
	 * @since   1.7
	 *
	 * @deprecated 4.0 Use directly the field or the layout
	 */
	public static function colorpicker()
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		HTMLHelper::_('script', 'jui/jquery.minicolors.min.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));
		HTMLHelper::_('stylesheet', 'jui/jquery.minicolors.css', array('version' => 'auto', 'relative' => true));

		Factory::getDocument()->addScriptDeclaration("
				jQuery(document).ready(function (){
					jQuery('.minicolors').each(function() {
						jQuery(this).minicolors({
							control: jQuery(this).attr('data-control') || 'hue',
							format: jQuery(this).attr('data-validate') === 'color'
								? 'hex'
								: (jQuery(this).attr('data-format') === 'rgba'
									? 'rgb'
									: jQuery(this).attr('data-format'))
								|| 'hex',
							keywords: jQuery(this).attr('data-keywords') || '',
							opacity: jQuery(this).attr('data-format') === 'rgba' ? true : false || false,
							position: jQuery(this).attr('data-position') || 'default',
							theme: 'bootstrap'
						});
					});
				});
			"
		);

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Add unobtrusive JavaScript support for a simple color picker.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 *
	 * @deprecated 4.0 Use directly the field or the layout
	 */
	public static function simplecolorpicker()
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		HTMLHelper::_('script', 'jui/jquery.simplecolors.min.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));
		HTMLHelper::_('stylesheet', 'jui/jquery.simplecolors.css', array('version' => 'auto', 'relative' => true));

		Factory::getDocument()->addScriptDeclaration("
				jQuery(document).ready(function (){
					jQuery('select.simplecolors').simplecolors();
				});
			"
		);

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Keep session alive, for example, while editing or creating an article.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public static function keepalive()
	{
		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		$session = Factory::getSession();

		// If the handler is not 'Database', we set a fixed, small refresh value (here: 5 min)
		$refreshTime = 300;

		if ($session->storeName === 'database')
		{
			$lifeTime    = $session->getExpire();
			$refreshTime = $lifeTime <= 60 ? 45 : $lifeTime - 60;

			// The longest refresh period is one hour to prevent integer overflow.
			if ($refreshTime > 3600 || $refreshTime <= 0)
			{
				$refreshTime = 3600;
			}
		}

		// If we are in the frontend or logged in as a user, we can use the ajax component to reduce the load
		$uri = 'index.php' . (Factory::getApplication()->isClient('site') || !Factory::getUser()->guest ? '?option=com_ajax&format=json' : '');

		// Include core and polyfill for browsers lower than IE 9.
		HTMLHelper::_('behavior.core');
		HTMLHelper::_('behavior.polyfill', 'event', 'lt IE 9');

		// Add keepalive script options.
		Factory::getDocument()->addScriptOptions('system.keepalive', array('interval' => $refreshTime * 1000, 'uri' => JRoute::_($uri)));

		// Add script.
		HTMLHelper::_('script', 'system/keepalive.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Highlight some words via Javascript.
	 *
	 * @param   array   $terms      Array of words that should be highlighted.
	 * @param   string  $start      ID of the element that marks the begin of the section in which words
	 *                              should be highlighted. Note this element will be removed from the DOM.
	 * @param   string  $end        ID of the element that end this section.
	 *                              Note this element will be removed from the DOM.
	 * @param   string  $className  Class name of the element highlights are wrapped in.
	 * @param   string  $tag        Tag that will be used to wrap the highlighted words.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public static function highlighter(array $terms, $start = 'highlighter-start', $end = 'highlighter-end', $className = 'highlight', $tag = 'span')
	{
		$sig = md5($start . $end . serialize($terms));

		if (isset(static::$loaded[__METHOD__][$sig]))
		{
			return;
		}

		$terms = array_filter($terms, 'strlen');

		// Nothing to Highlight
		if (empty($terms))
		{
			static::$loaded[__METHOD__][$sig] = true;

			return;
		}

		// Include core
		HTMLHelper::_('behavior.core');

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		HTMLHelper::_('script', 'system/highlighter.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));

		foreach ($terms as $i => $term)
		{
			$terms[$i] = OutputFilter::stringJSSafe($term);
		}

		Factory::getDocument()->addScriptDeclaration("
			jQuery(function ($) {
				var start = document.getElementById('" . $start . "');
				var end = document.getElementById('" . $end . "');
				if (!start || !end || !Joomla.Highlighter) {
					return true;
				}
				highlighter = new Joomla.Highlighter({
					startElement: start,
					endElement: end,
					className: '" . $className . "',
					onlyWords: false,
					tag: '" . $tag . "'
				}).highlight([\"" . implode('","', $terms) . "\"]);
				$(start).remove();
				$(end).remove();
			});
		");

		static::$loaded[__METHOD__][$sig] = true;
	}

	/**
	 * Break us out of any containing iframes
	 *
	 * @return  void
	 *
	 * @since   1.5
	 *
	 * @deprecated  4.0  Add a X-Frame-Options HTTP Header with the SAMEORIGIN value instead.
	 */
	public static function noframes()
	{
		Log::add(__METHOD__ . ' is deprecated, add a X-Frame-Options HTTP Header with the SAMEORIGIN value instead.', Log::WARNING, 'deprecated');

		// Only load once
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		// Include core
		HTMLHelper::_('behavior.core');

		// Include jQuery
		HTMLHelper::_('jquery.framework');

		$document = Factory::getDocument();
		$document->addStyleDeclaration('html { display:none }');
		$document->addScriptDeclaration('jQuery(function () {
			if (top == self) {
				document.documentElement.style.display = "block";
			}
			else
			{
				top.location = self.location;
			}

			// Firefox fix
			jQuery("input[autofocus]").focus();
		})');

		Factory::getApplication()->setHeader('X-Frame-Options', 'SAMEORIGIN');

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Internal method to get a JavaScript object notation string from an array
	 *
	 * @param   array  $array  The array to convert to JavaScript object notation
	 *
	 * @return  string  JavaScript object notation representation of the array
	 *
	 * @since       1.5
	 * @deprecated  4.0 - Use HTMLHelper::getJSObject() instead.
	 */
	protected static function _getJSObject($array = array())
	{
		Log::add('JHtmlBehavior::_getJSObject() is deprecated. HTMLHelper::getJSObject() instead..', Log::WARNING, 'deprecated');

		return HTMLHelper::getJSObject($array);
	}

	/**
	 * Add unobtrusive JavaScript support to keep a tab state.
	 *
	 * Note that keeping tab state only works for inner tabs if in accordance with the following example:
	 *
	 * ```
	 * parent tab = permissions
	 * child tab = permission-<identifier>
	 * ```
	 *
	 * Each tab header `<a>` tag also should have a unique href attribute
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public static function tabstate()
	{
		if (isset(static::$loaded[__METHOD__]))
		{
			return;
		}

		// Include jQuery
		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('behavior.polyfill', array('filter', 'xpath'));
		HTMLHelper::_('script', 'system/tabs-state.js', array('version' => 'auto', 'relative' => true, 'detectBrowser' => false));

		static::$loaded[__METHOD__] = true;
	}

	/**
	 * Add javascript polyfills.
	 *
	 * @param   string|array  $polyfillTypes       The polyfill type(s). Examples: event, array('event', 'classlist').
	 * @param   string        $conditionalBrowser  An IE conditional expression. Example: lt IE 9 (lower than IE 9).
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	public static function polyfill($polyfillTypes = null, $conditionalBrowser = null)
	{
		if ($polyfillTypes === null)
		{
			return;
		}

		foreach ((array) $polyfillTypes as $polyfillType)
		{
			$sig = $polyfillType . ($conditionalBrowser !== null ? $conditionalBrowser : '');

			// Only load once
			if (isset(static::$loaded[__METHOD__][$sig]))
			{
				continue;
			}

			// If include according to browser.
			$scriptOptions = array('version' => 'auto', 'relative' => true, 'detectBrowser' => false);

			if ($conditionalBrowser !== null)
			{
				$scriptOptions['conditional'] = $conditionalBrowser;
			}

			HTMLHelper::_('script', 'system/polyfill.' . $polyfillType . '.js', $scriptOptions);

			// Set static array
			static::$loaded[__METHOD__][$sig] = true;
		}
	}

	/**
	 * Internal method to translate the JavaScript Calendar
	 *
	 * @return  string  JavaScript that translates the object
	 *
	 * @since   1.5
	 */
	protected static function calendartranslation()
	{
		if (isset(static::$loaded[__METHOD__]))
		{
			return false;
		}

		static::$loaded[__METHOD__] = true;

		// To keep the code simple here, run strings through Text::_() using array_map()
		$callback = array('JText', '_');
		$weekdays_full = array_map(
			$callback, array(
				'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY',
			)
		);
		$weekdays_short = array_map(
			$callback,
			array(
				'SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN',
			)
		);
		$months_long = array_map(
			$callback, array(
				'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
				'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER',
			)
		);
		$months_short = array_map(
			$callback, array(
				'JANUARY_SHORT', 'FEBRUARY_SHORT', 'MARCH_SHORT', 'APRIL_SHORT', 'MAY_SHORT', 'JUNE_SHORT',
				'JULY_SHORT', 'AUGUST_SHORT', 'SEPTEMBER_SHORT', 'OCTOBER_SHORT', 'NOVEMBER_SHORT', 'DECEMBER_SHORT',
			)
		);

		// This will become an object in Javascript but define it first in PHP for readability
		$today = " " . Text::_('JLIB_HTML_BEHAVIOR_TODAY') . " ";
		$text = array(
			'INFO'           => Text::_('JLIB_HTML_BEHAVIOR_ABOUT_THE_CALENDAR'),
			'ABOUT'          => "DHTML Date/Time Selector\n"
				. "(c) dynarch.com 20022005 / Author: Mihai Bazon\n"
				. "For latest version visit: http://www.dynarch.com/projects/calendar/\n"
				. "Distributed under GNU LGPL.  See http://gnu.org/licenses/lgpl.html for details."
				. "\n\n"
				. Text::_('JLIB_HTML_BEHAVIOR_DATE_SELECTION')
				. Text::_('JLIB_HTML_BEHAVIOR_YEAR_SELECT')
				. Text::_('JLIB_HTML_BEHAVIOR_MONTH_SELECT')
				. Text::_('JLIB_HTML_BEHAVIOR_HOLD_MOUSE'),
			'ABOUT_TIME'      => "\n\n"
				. "Time selection:\n"
				. " Click on any of the time parts to increase it\n"
				. " or Shiftclick to decrease it\n"
				. " or click and drag for faster selection.",
			'PREV_YEAR'       => Text::_('JLIB_HTML_BEHAVIOR_PREV_YEAR_HOLD_FOR_MENU'),
			'PREV_MONTH'      => Text::_('JLIB_HTML_BEHAVIOR_PREV_MONTH_HOLD_FOR_MENU'),
			'GO_TODAY'        => Text::_('JLIB_HTML_BEHAVIOR_GO_TODAY'),
			'NEXT_MONTH'      => Text::_('JLIB_HTML_BEHAVIOR_NEXT_MONTH_HOLD_FOR_MENU'),
			'SEL_DATE'        => Text::_('JLIB_HTML_BEHAVIOR_SELECT_DATE'),
			'DRAG_TO_MOVE'    => Text::_('JLIB_HTML_BEHAVIOR_DRAG_TO_MOVE'),
			'PART_TODAY'      => $today,
			'DAY_FIRST'       => Text::_('JLIB_HTML_BEHAVIOR_DISPLAY_S_FIRST'),
			'WEEKEND'         => Factory::getLanguage()->getWeekEnd(),
			'CLOSE'           => Text::_('JLIB_HTML_BEHAVIOR_CLOSE'),
			'TODAY'           => Text::_('JLIB_HTML_BEHAVIOR_TODAY'),
			'TIME_PART'       => Text::_('JLIB_HTML_BEHAVIOR_SHIFT_CLICK_OR_DRAG_TO_CHANGE_VALUE'),
			'DEF_DATE_FORMAT' => "%Y%m%d",
			'TT_DATE_FORMAT'  => Text::_('JLIB_HTML_BEHAVIOR_TT_DATE_FORMAT'),
			'WK'              => Text::_('JLIB_HTML_BEHAVIOR_WK'),
			'TIME'            => Text::_('JLIB_HTML_BEHAVIOR_TIME'),
		);

		return 'Calendar._DN = ' . json_encode($weekdays_full) . ';'
			. ' Calendar._SDN = ' . json_encode($weekdays_short) . ';'
			. ' Calendar._FD = 0;'
			. ' Calendar._MN = ' . json_encode($months_long) . ';'
			. ' Calendar._SMN = ' . json_encode($months_short) . ';'
			. ' Calendar._TT = ' . json_encode($text) . ';';
	}
}
