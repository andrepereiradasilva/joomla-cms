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
	public function storeAll($context = 'com_content.item', array $associations = array())
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
		$key = json_encode($associations);

		foreach ($associations as $languageCode => $id)
		{
			// Bind, check and store the data.
			if (!$this->save(array('id' => $id, 'context' => $context, 'key' => $key)))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Loads associations.
	 *
	 * @param   mixed    $keys   Primary key value to load the row by, or an array of fields to match.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function loadAll($keys = array(), $tableName = '#__content')
	{
		// If no context don't load.
		if (!isset($keys['context']))
		{
			return false;
		}

		// If searching for current id we need to get the key first.
		if (isset($keys['id']))
		{
			if (!$this->load(array('id' => (int) $keys['id'], 'context' => $keys['context'])))
			{
				return false;
			}

			$keys['key'] = $this->key;
		}

		if (!isset($keys['key']))
		{
			return false;
		}

		$value = json_decode($keys['key']);

		// B/C key field with md5 hash
		if ($value === null)
		{
			$query = $this->_db->getQuery(true)
				->select($this->_db->qn('a.id'))
				->from($this->_db->qn('#__associations', 'a'))
				->where($this->_db->qn('a.context') . ' = ' . $this->_db->q($keys['context']))
				->where($this->_db->qn('a.key') . ' = ' . $this->_db->q($keys['key']));

			if ($tableName)
			{
				$query->select($this->_db->qn('l.lang_code', 'language'))
					->join('INNER', $this->_db->qn($tableName, 't') . ' ON ' . $this->_db->qn('a.id') . ' = ' . $this->_db->qn('t.id'))
					->join('INNER', $this->_db->qn('#__languages', 'l') . ' ON ' . $this->_db->qn('t.language') . ' = ' . $this->_db->qn('l.lang_code'));
			}

			$value = $this->_db->setQuery($query)->loadAssocList('language');
		}

		return $value;
	}

	/**
	 * Overloaded method to disable behaviour.
	 *
	 * @param   integer  $userId  The Id of the user checking out the row.
	 * @param   mixed    $pk      An optional primary key value to check out.  If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function checkOut($userId, $pk = null)
	{
		return true;
	}

	/**
	 * Overloaded method to disable behaviour.
	 *
	 * @param   mixed  $pk  An optional primary key value to check out.  If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function checkIn($pk = null)
	{
		return true;
	}

	/**
	 * Overloaded method to disable behaviour.
	 *
	 * @param   mixed  $pk  An optional primary key value to increment. If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function hit($pk = null)
	{
		return true;
	}

	/**
	 * Overloaded method to disable behaviour.
	 *
	 * @param   integer  $with     The user ID to preform the match with, if an item is checked out by this user the function will return false.
	 * @param   integer  $against  The user ID to perform the match against when the function is used as a static function.
	 *
	 * @return  boolean  True if checked out.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function isCheckedOut($with = 0, $against = null)
	{
		return true;
	}

	/**
	 * Overloaded method to disable behaviour.
	 *
	 * @param   string  $where  WHERE clause to use for selecting the MAX(ordering) for the table.
	 *
	 * @return  integer  The next ordering value.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getNextOrder($where = '')
	{
		return 1;
	}

	/**
	 * Overloaded method to disable behaviour.
	 *
	 * @param   string  $where  WHERE clause to use for limiting the selection of rows to compact the ordering values.
	 *
	 * @return  mixed  Boolean  True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function reorder($where = '')
	{
		return true;
	}

	/**
	 * Overloaded method to disable behaviour.
	 *
	 * @param   integer  $delta  The direction and magnitude to move the row in the ordering sequence.
	 * @param   string   $where  WHERE clause to use for limiting the selection of rows to compact the ordering values.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function move($delta, $where = '')
	{
		return true;
	}

	/**
	 * Overloaded method to disable behaviour.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update. If not set the instance property value is used.
	 * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
	 * @param   integer  $userId  The user ID of the user performing the operation.
	 *
	 * @return  boolean  True on success; false if $pks is empty.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function publish($pks = null, $state = 1, $userId = 0)
	{
		return true;
	}
}
