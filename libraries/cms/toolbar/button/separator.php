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
 * Renders a button separator
 *
 * @since  3.0
 */
class JToolbarButtonSeparator extends JToolbarButton
{
	/**
	 * Button type
	 *
	 * @var   string
	 */
	protected $_name = 'Separator';

	/**
	 * Get the HTML for a separator in the toolbar
	 *
	 * @param   array  &$definition  Class name and custom width
	 *
	 * @return  string  The HTML for the separator
	 *
	 * @see     JToolbarButton::render()
	 * @since   3.0
	 */
	public function render(&$definition)
	{
		// Store all data to the options array for use with JLayout
		$options = array(
			'class' => empty($definition[1]) ? '' : $definition[1],
			'style' => empty($definition[2]) ? '' : ' style="width:' . (int) $definition[2] . 'px;"',
		);

		// Render the layout
		return JLayoutHelper::render('joomla.toolbar.separator', $options);
	}

	/**
	 * Empty implementation (not required for separator)
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function fetchButton()
	{
	}
}
