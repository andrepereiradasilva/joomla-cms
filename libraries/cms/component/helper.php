<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Component
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

/**
 * Component helper class
 *
 * @since  1.5
 */
class JComponentHelper
{
	/**
	 * The component list cache
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected static $components = null;

	/**
	 * Get the component information.
	 *
	 * @param   string   $element  The component element name.
	 * @param   boolean  $strict   If set and the component does not exist, the enabled attribute will be set to false.
	 *
	 * @return  stdClass   An object with the information for the component.
	 *
	 * @since   1.5
	 */
	public static function getComponent($element, $strict = false)
	{
		static::load();

		if (isset(static::$components[$element]))
		{
			return static::$components[$element];
		}

		$result = new stdClass;
		$result->enabled = $strict ? false : true;
		$result->params = new Registry;

		return $result;
	}

	/**
	 * Checks if the component is enabled
	 *
	 * @param   string  $element  The component element name.
	 *
	 * @return  boolean
	 *
	 * @since   1.5
	 */
	public static function isEnabled($element)
	{
		return JExtensionHelper::isEnabled('component', $element);
	}

	/**
	 * Checks if a component is installed
	 *
	 * @param   string  $element  The component element name.
	 *
	 * @return  integer
	 *
	 * @since   3.4
	 */
	public static function isInstalled($element)
	{
		return (int) JExtensionHelper::isInstalled('component', $element);
	}

	/**
	 * Gets the parameter object for the component
	 *
	 * @param   string   $element  The component element name.
	 *
	 * @return  Registry  A Registry object.
	 *
	 * @see     Registry
	 * @since   1.5
	 */
	public static function getParams($element)
	{
		return JExtensionHelper::getParams('component', $element);
	}

	/**
	 * Applies the global text filters to arbitrary text as per settings for current user groups
	 *
	 * @param   string  $text  The string to filter
	 *
	 * @return  string  The filtered string
	 *
	 * @since   2.5
	 */
	public static function filterText($text)
	{
		// Punyencoding utf8 email addresses
		$text = JFilterInput::getInstance()->emailToPunycode($text);

		// Filter settings
		$config     = static::getParams('com_config');
		$user       = JFactory::getUser();
		$userGroups = JAccess::getGroupsByUser($user->get('id'));

		$filters = $config->get('filters');

		$blackListTags       = array();
		$blackListAttributes = array();

		$customListTags       = array();
		$customListAttributes = array();

		$whiteListTags       = array();
		$whiteListAttributes = array();

		$whiteList  = false;
		$blackList  = false;
		$customList = false;
		$unfiltered = false;

		// Cycle through each of the user groups the user is in.
		// Remember they are included in the Public group as well.
		foreach ($userGroups as $groupId)
		{
			// May have added a group by not saved the filters.
			if (!isset($filters->$groupId))
			{
				continue;
			}

			// Each group the user is in could have different filtering properties.
			$filterData = $filters->$groupId;
			$filterType = strtoupper($filterData->filter_type);

			if ($filterType == 'NH')
			{
				// Maximum HTML filtering.
			}
			elseif ($filterType == 'NONE')
			{
				// No HTML filtering.
				$unfiltered = true;
			}
			else
			{
				// Blacklist or whitelist.
				// Preprocess the tags and attributes.
				$tags           = explode(',', $filterData->filter_tags);
				$attributes     = explode(',', $filterData->filter_attributes);
				$tempTags       = array();
				$tempAttributes = array();

				foreach ($tags as $tag)
				{
					$tag = trim($tag);

					if ($tag)
					{
						$tempTags[] = $tag;
					}
				}

				foreach ($attributes as $attribute)
				{
					$attribute = trim($attribute);

					if ($attribute)
					{
						$tempAttributes[] = $attribute;
					}
				}

				// Collect the blacklist or whitelist tags and attributes.
				// Each list is cummulative.
				if ($filterType == 'BL')
				{
					$blackList           = true;
					$blackListTags       = array_merge($blackListTags, $tempTags);
					$blackListAttributes = array_merge($blackListAttributes, $tempAttributes);
				}
				elseif ($filterType == 'CBL')
				{
					// Only set to true if Tags or Attributes were added
					if ($tempTags || $tempAttributes)
					{
						$customList           = true;
						$customListTags       = array_merge($customListTags, $tempTags);
						$customListAttributes = array_merge($customListAttributes, $tempAttributes);
					}
				}
				elseif ($filterType == 'WL')
				{
					$whiteList           = true;
					$whiteListTags       = array_merge($whiteListTags, $tempTags);
					$whiteListAttributes = array_merge($whiteListAttributes, $tempAttributes);
				}
			}
		}

		// Remove duplicates before processing (because the blacklist uses both sets of arrays).
		$blackListTags        = array_unique($blackListTags);
		$blackListAttributes  = array_unique($blackListAttributes);
		$customListTags       = array_unique($customListTags);
		$customListAttributes = array_unique($customListAttributes);
		$whiteListTags        = array_unique($whiteListTags);
		$whiteListAttributes  = array_unique($whiteListAttributes);

		// Unfiltered assumes first priority.
		if ($unfiltered)
		{
			// Dont apply filtering.
		}
		else
		{
			// Custom blacklist precedes Default blacklist
			if ($customList)
			{
				$filter = JFilterInput::getInstance(array(), array(), 1, 1);

				// Override filter's default blacklist tags and attributes
				if ($customListTags)
				{
					$filter->tagBlacklist = $customListTags;
				}

				if ($customListAttributes)
				{
					$filter->attrBlacklist = $customListAttributes;
				}
			}
			// Blacklists take second precedence.
			elseif ($blackList)
			{
				// Remove the whitelisted tags and attributes from the black-list.
				$blackListTags       = array_diff($blackListTags, $whiteListTags);
				$blackListAttributes = array_diff($blackListAttributes, $whiteListAttributes);

				$filter = JFilterInput::getInstance($blackListTags, $blackListAttributes, 1, 1);

				// Remove whitelisted tags from filter's default blacklist
				if ($whiteListTags)
				{
					$filter->tagBlacklist = array_diff($filter->tagBlacklist, $whiteListTags);
				}
				// Remove whitelisted attributes from filter's default blacklist
				if ($whiteListAttributes)
				{
					$filter->attrBlacklist = array_diff($filter->attrBlacklist, $whiteListAttributes);
				}
			}
			// Whitelists take third precedence.
			elseif ($whiteList)
			{
				// Turn off XSS auto clean
				$filter = JFilterInput::getInstance($whiteListTags, $whiteListAttributes, 0, 0, 0);
			}
			// No HTML takes last place.
			else
			{
				$filter = JFilterInput::getInstance();
			}

			$text = $filter->clean($text, 'html');
		}

		return $text;
	}

	/**
	 * Render the component.
	 *
	 * @param   string  $element  The component element name.
	 * @param   array   $params   The component parameters
	 *
	 * @return  string
	 *
	 * @since   1.5
	 * @throws  Exception
	 */
	public static function renderComponent($element, $params = array())
	{
		$app = JFactory::getApplication();

		// Load template language files.
		$template = $app->getTemplate(true)->template;
		$lang = JFactory::getLanguage();
		$lang->load('tpl_' . $template, JPATH_BASE, null, false, true)
			|| $lang->load('tpl_' . $template, JPATH_THEMES . "/$template", null, false, true);

		if (empty($element))
		{
			throw new Exception(JText::_('JLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'), 404);
		}

		if (JDEBUG)
		{
			JProfiler::getInstance('Application')->mark('beforeRenderComponent ' . $element);
		}

		// Record the scope
		$scope = $app->scope;

		// Set scope to component name
		$app->scope = $element;

		// Build the component path.
		$element = preg_replace('/[^A-Z0-9_\.-]/i', '', $element);
		$file    = substr($element, 4);

		// Define component path.
		if (!defined('JPATH_COMPONENT'))
		{
			define('JPATH_COMPONENT', JPATH_BASE . '/components/' . $element);
		}

		if (!defined('JPATH_COMPONENT_SITE'))
		{
			define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/' . $element);
		}

		if (!defined('JPATH_COMPONENT_ADMINISTRATOR'))
		{
			define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/' . $element);
		}

		$path = JPATH_COMPONENT . '/' . $file . '.php';

		// If component is disabled throw error
		if (!static::isEnabled($element) || !file_exists($path))
		{
			throw new Exception(JText::_('JLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'), 404);
		}

		// Load common and local language files.
		$lang->load($element, JPATH_BASE, null, false, true) || $lang->load($element, JPATH_COMPONENT, null, false, true);

		// Handle template preview outlining.
		$contents = null;

		// Execute the component.
		$contents = static::executeComponent($path);

		// Revert the scope
		$app->scope = $scope;

		if (JDEBUG)
		{
			JProfiler::getInstance('Application')->mark('afterRenderComponent ' . $element);
		}

		return $contents;
	}

	/**
	 * Execute the component.
	 *
	 * @param   string  $path  The component path.
	 *
	 * @return  string  The component output
	 *
	 * @since   1.7
	 */
	protected static function executeComponent($path)
	{
		ob_start();
		require_once $path;
		$contents = ob_get_clean();

		return $contents;
	}

	/**
	 * Load the installed components into the components property.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.5
	 * @deprecated  4.0  Use JComponentHelper::load() instead
	 */
	protected static function _load()
	{
		return static::load();
	}

	/**
	 * Load the installed components into the components property.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.2
	 */
	protected static function load()
	{
		if (static::$components !== null)
		{
			return static::$components;
		}

		// Load all components.
		$components = JExtensionHelper::getExtensions('component');

		static::$components = array();

		foreach ($components as $component)
		{
			// Use the already used terms.
			$component->id     = $component->extension_id;
			$component->option = $component->element;

			static::$components[$component->option] = $component;
		}

		return true;
	}

	/**
	 * Get installed components
	 *
	 * @return  array  The components property
	 *
	 * @since   3.6.3
	 */
	public static function getComponents()
	{
		static::load();

		return static::$components;
	}
}
