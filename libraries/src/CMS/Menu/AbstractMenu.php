<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Menu;

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

/**
 * Menu class
 *
 * @since  1.5
 */
abstract class AbstractMenu
{
	/**
	 * Array to hold the menu items
	 *
	 * @var    MenuItem[]
	 * @since  __DEPLOY_VERSION__
	 */
	protected $items = [];

	/**
	 * Identifier of the default menu item
	 *
	 * @var    integer
	 * @since  __DEPLOY_VERSION__
	 */
	protected $default = [];

	/**
	 * Identifier of the active menu item
	 *
	 * @var    integer
	 * @since  __DEPLOY_VERSION__
	 */
	protected $active = 0;

	/**
	 * Menu instances container.
	 *
	 * @var    AbstractMenu[]
	 * @since  1.7
	 */
	protected static $instances = [];

	/**
	 * User object to check access levels for
	 *
	 * @var    \JUser
	 * @since  3.5
	 */
	protected $user;

	/**
	 * Class constructor
	 *
	 * @param   array  $options  An array of configuration options.
	 *
	 * @since   1.5
	 */
	public function __construct($options = [])
	{
		// Load the menu items
		$this->load();

		foreach ($this->getMenu() as $item)
		{
			if ($item->home)
			{
				$this->default[trim($item->language)] = $item->id;
			}
		}

		$this->user = isset($options['user']) && $options['user'] instanceof \JUser ? $options['user'] : \JFactory::getUser();
	}

	/**
	 * Returns a Menu object
	 *
	 * @param   string  $client   The name of the client
	 * @param   array   $options  An associative array of options
	 *
	 * @return  AbstractMenu  A menu object.
	 *
	 * @since   1.5
	 * @throws  \Exception
	 */
	public static function getInstance($client, $options = array())
	{
		if (!$client)
		{
			throw new \Exception(\JText::sprintf('JLIB_APPLICATION_ERROR_MENU_LOAD', $client), 500);
		}

		if (empty(self::$instances[$client]))
		{
			self::$instances[$client] = \JFactory::getContainer()->get(MenuFactoryInterface::class)->createMenu($client, $options);
		}

		return self::$instances[$client];
	}

	/**
	 * Get menu item by id
	 *
	 * @param   integer  $id  The item id
	 *
	 * @return  MenuItem|null  The item object if the ID exists or null if not found
	 *
	 * @since   1.5
	 */
	public function getItem($id)
	{
		return isset($this->getMenu()[$id]) ? $this->getMenu()[$id] : null;
	}

	/**
	 * Set the default item by id and language code.
	 *
	 * @param   integer  $id        The menu item id.
	 * @param   string   $language  The language code (since 1.6).
	 *
	 * @return  boolean  True if a menu item with the given ID exists
	 *
	 * @since   1.5
	 */
	public function setDefault($id, $language = '*')
	{
		if (isset($this->getMenu()[$id]))
		{
			$this->default[$language] = $id;

			return true;
		}

		return false;
	}

	/**
	 * Get the default item by language code.
	 *
	 * @param   string  $language  The language code, default value of * means all.
	 *
	 * @return  MenuItem|null  The item object or null when not found for given language
	 *
	 * @since   1.5
	 */
	public function getDefault($language = '*')
	{
		if (isset($this->default[$language]))
		{
			return $this->getMenu()[$this->default[$language]];
		}

		if (isset($this->default['*']))
		{
			return $this->getMenu()[$this->default['*']];
		}
	}

	/**
	 * Set the default item by id
	 *
	 * @param   integer  $id  The item id
	 *
	 * @return  MenuItem|null  The menu item representing the given ID if present or null otherwise
	 *
	 * @since   1.5
	 */
	public function setActive($id)
	{
		if (isset($this->getMenu()[$id]))
		{
			$this->active = $id;

			return $this->getMenu()[$id];
		}
	}

	/**
	 * Get menu item by id.
	 *
	 * @return  MenuItem|null  The item object if an active menu item has been set or null
	 *
	 * @since   1.5
	 */
	public function getActive()
	{
		return $this->active ? $this->getMenu()[$this->active] : null;
	}

	/**
	 * Gets menu items by attribute
	 *
	 * @param   mixed    $attributes  The field name(s).
	 * @param   mixed    $values      The value(s) of the field. If an array, need to match field names
	 *                                each attribute may have multiple values to lookup for.
	 * @param   boolean  $firstOnly   If true, only returns the first item found
	 *
	 * @return  MenuItem|MenuItem[]  An array of menu item objects or a single object if the $firstOnly parameter is true
	 *
	 * @since   1.5
	 */
	public function getItems($attributes, $values, $firstOnly = false)
	{
		$items            = $this->getMenu();
		$searchAttributes = array_combine((array) $attributes, (array) $values);

		foreach ($items as $k => $item)
		{
			if (!is_object($item))
			{
				continue;
			}

			foreach ($searchAttributes as $attribute => $value)
			{
				$isArrayOfValues = is_array($value) === true;

				if (($isArrayOfValues === false && $item->{$attribute} != $value)
					|| ($isArrayOfValues === true && !in_array($item->{$attribute}, $value)))
				{
					unset($items[$k]);
					continue 2;
				}
			}

			if ($firstOnly === true)
			{
				return $item;
			}
		}

		return $items;
	}

	/**
	 * Gets the parameter object for a certain menu item
	 *
	 * @param   integer  $id  The item id
	 *
	 * @return  Registry
	 *
	 * @since   1.5
	 */
	public function getParams($id)
	{
		if ($menu = $this->getItem($id))
		{
			return $menu->params;
		}

		return new Registry;
	}

	/**
	 * Getter for the menu array
	 *
	 * @return  MenuItem[]
	 *
	 * @since   1.5
	 */
	public function getMenu()
	{
		return $this->items;
	}

	/**
	 * Method to check Menu object authorization against an access control object and optionally an access extension object
	 *
	 * @param   integer  $id  The menu id
	 *
	 * @return  boolean
	 *
	 * @since   1.5
	 */
	public function authorise($id)
	{
		if ($menu = $this->getItem($id))
		{
			return in_array((int) $menu->access, $this->user->getAuthorisedViewLevels());
		}

		return true;
	}

	/**
	 * Loads the menu items
	 *
	 * @return  array
	 *
	 * @since   1.5
	 */
	abstract public function load();
}
