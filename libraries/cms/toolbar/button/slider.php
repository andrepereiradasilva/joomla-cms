<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Toolbar
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Renders a button to render an HTML element in a slider container
 *
 * @since  3.0
 */
class JToolbarButtonSlider extends JToolbarButton
{
	/**
	 * Button type
	 *
	 * @var    string
	 */
	protected $_name = 'Slider';

	/**
	 * Fetch the HTML for the button
	 *
	 * @param   string   $type     Unused string, formerly button type.
	 * @param   string   $name     Button name
	 * @param   string   $text     The link text
	 * @param   string   $url      URL for popup
	 * @param   integer  $width    Width of popup
	 * @param   integer  $height   Height of popup
	 * @param   string   $onClose  JavaScript for the onClose event.
	 *
	 * @return  string  HTML string for the button
	 *
	 * @since   3.0
	 */
	public function fetchButton($type = 'Slider', $name = '', $text = '', $url = '', $width = 640, $height = 480, $onClose = '')
	{
		JHtml::_('script', 'jui/cms.js', array('version' => 'auto', 'relative' => true));

		// Store all data to the options array for use with JLayout
		$options = array(
			'text'    => JText::_($text),
			'name'    => $name,
			'class'   => $this->fetchIconClass($name),
			'doTask'  => 'Joomla.setcollapse(\'' . $this->_getCommand($url) . '\', \'' . $name . '\', \'' . $height . '\');',
			'onClose' => $onClose ? ' rel="{onClose: function() {' . $onClose . '}}"' : '',
		);

		// Render the layout
		return JLayoutHelper::render('joomla.toolbar.slider', $options);
	}

	/**
	 * Get the button id
	 *
	 * @param   string  $type  Button type
	 * @param   string  $name  Button name
	 *
	 * @return  string	Button CSS Id
	 *
	 * @since   3.0
	 */
	public function fetchId($type, $name)
	{
		return $this->_parent->getName() . '-slider-' . $name;
	}

	/**
	 * Get the JavaScript command for the button
	 *
	 * @param   string  $url  URL for popup
	 *
	 * @return  string  JavaScript command string
	 *
	 * @since   3.0
	 */
	private function _getCommand($url)
	{
		if (substr($url, 0, 4) !== 'http')
		{
			return JUri::base() . $url;
		}

		return $url;
	}
}
