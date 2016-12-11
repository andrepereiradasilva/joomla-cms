<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Extension
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

/**
 * Extension class
 *
 * @since  __DEPLOY_VERSION__
 */
class JExtension extends JObject
{
	/**
	 * Class constructor
	 *
	 * @param   array  $options  An array of extension options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($options = array())
	{
		// B/C will be removed in 4.0. Deprecated JExtension class placeholder. Use JInstallerExtension instead.
		if ($options instanceof SimpleXMLElement)
		{
			return new JInstallerExtension($element);
		}

		foreach ($options as $option => $value)
		{
			if ($option === 'params')
			{
				$value = new Registry($value);
			}

			$this->$option = $value;
		}
	}

	/**
	 * Checks if the extension is enabled.
	 *
	 * @return  boolean  True if extension is enabled, false otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function isEnabled()
	{
		return (boolean) $this->enabled;
	}

	/**
	 * Gets the parameter object for the extension.
	 *
	 * @return  Registry  The params Registry object.
	 *
	 * @see     Registry
	 * @since   __DEPLOY_VERSION__
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Save the parameters object for an extension.
	 *
	 * @param   string|Registry  $params   Params to save
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function saveParams($params)
	{
		// No extension installed, or invalid parameters sent. Return false.
		if (!$params || !$this->type || !$this->element)
		{
			return false;
		}

		// Convert to string if needed.
		$params = is_string($params) ?: $params->toString();

		// Save params in database.
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params') . ' = ' . $db->q($params))
			->where($db->qn('type') . ' = ' . $db->q($this->type))
			->where($db->qn('element') . ' = ' . $db->q($this->element));

		if ($this->folder)
		{
			$query->where($db->qn('folder') . ' = ' . $db->q($this->folder));
		}

		$db->setQuery($query)->execute();

		$this->params = new Registry($params);
	}
}
