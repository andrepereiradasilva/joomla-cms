<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Associations table.
 *
 * @since  __DEPLOY_VERSION__
 */
class JTableAssociation extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  $db  Database driver object.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($db)
	{
		parent::__construct('#__associations', array('id', 'context'), $db);
	}

	/**
	 * Adds associations.
	 *
	 * @param   string  $context       True to update fields even if they are null.
	 * @param   array   $associations  Array of associations.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function association($context = 'com_content.item', array $associations = array())
	{
		// Unset any invalid associations
		$associations = ArrayHelper::toInteger($associations);

		// Unset any invalid associations
		foreach ($associations as $tag => $id)
		{
			if (!$id)
			{
				unset($associations[$tag]);
			}
		}

		// Adds the new associations.
		$key = md5(json_encode($associations));

		foreach ($associations as $languageCode => $id)
		{
			// Bind, check and store the data.
			if (!$this->bind(array('id' => $id, 'context' => $context, 'key' => $key)) || !$this->check() || !$this->store())
			{
				return false;
			}
		}

		return true;
	}
}