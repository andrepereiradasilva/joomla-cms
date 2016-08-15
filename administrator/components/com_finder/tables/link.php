<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Link table class for the Finder package.
 *
 * @since  2.5
 */
class FinderTableLink extends JTable
{
	/**
	 * Mapping of database columns alias.
	 *
	 * @var    array
	 * @since  __DEPLOY_VERSION__
	 */
	protected $_columnAlias = array(
		'id'           => 'link_id',
		'publish_up'   => 'publish_start_date',
		'publish_down' => 'publish_end_date',
	);

	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  JDatabaseDriver connector object.
	 *
	 * @since   2.5
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__finder_links', 'link_id', $db);
	}
}
