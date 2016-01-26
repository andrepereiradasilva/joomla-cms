<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Language
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

/**
 * Utitlity class for associations in multilang
 *
 * @since  3.1
 */
class JLanguageAssociations
{
	/**
	 * Get the associations.
	 *
	 * @param   string   $extension   The name of the component.
	 * @param   string   $tablename   The name of the table.
	 * @param   string   $context     The context
	 * @param   integer  $id          The primary key value.
	 * @param   string   $pk          The name of the primary key in the given $table.
	 * @param   string   $aliasField  If the table has an alias field set it here. Null to not use it
	 * @param   string   $catField    If the table has a catid field set it here. Null to not use it
	 * @param   boolean  $onlyIds     If the method should only return the ids.
	 *
	 * @return  array                The associated items
	 *
	 * @since   3.1
	 *
	 * @throws  Exception
	 */
	public static function getAssociations($extension, $tablename, $context, $id, $pk = 'id', $aliasField = 'alias', $catField = 'catid', $onlyIds = false)
	{
		// To avoid doing duplicate database queries.
		static $multilanguageAssociations = array();

		// Multilanguage association memory key.
		$queryKey = md5(implode('|', func_get_args()));

		// If tested before, don't test again.
		if (!isset($multilanguageAssociations[$queryKey]))
		{
			// Set it as an empty array by default.
			$multilanguageAssociations[$queryKey] = array();

			$db = JFactory::getDbo();
			$categoriesExtraSql = (($tablename === '#__categories') ? ' AND c2.extension = ' . $db->quote($extension) : '');
			$query = $db->getQuery(true)
				->select($db->quoteName('c2.language'))
				->from($db->quoteName($tablename, 'c'))
				->join('INNER', $db->quoteName('#__associations', 'a') . ' ON a.id = c.' . $db->quoteName($pk) . ' AND a.context=' . $db->quote($context))
				->join('INNER', $db->quoteName('#__associations', 'a2') . ' ON a.key = a2.key')
				->join('INNER', $db->quoteName($tablename, 'c2') . ' ON a2.id = c2.' . $db->quoteName($pk) . $categoriesExtraSql);

			// Use alias field ?
			if (!empty($aliasField))
			{
				$query->select(
					$query->concatenate(
						array(
							$db->quoteName('c2.' . $pk),
							$db->quoteName('c2.' . $aliasField)
						),
						':'
					) . ' AS ' . $db->quoteName($pk)
				);
			}
			else
			{
				$query->select($db->quoteName('c2.' . $pk));
			}

			// Use catid field ?
			if (!empty($catField))
			{
				$query->join(
						'INNER',
						$db->quoteName('#__categories', 'ca') . ' ON ' . $db->quoteName('c2.' . $catField) . ' = ca.id AND ca.extension = ' . $db->quote($extension)
					)
					->select(
						$query->concatenate(
							array('ca.id', 'ca.alias'),
							':'
						) . ' AS ' . $db->quoteName($catField)
					);
			}

			$query->where('c.' . $pk . ' = ' . (int) $id);
			if ($tablename === '#__categories')
			{
				$query->where('c.extension = ' . $db->quote($extension));
			}

			$db->setQuery($query);

			try
			{
				$items = $db->loadObjectList('language');
			}
			catch (RuntimeException $e)
			{
				throw new Exception($e->getMessage(), 500, $e);
			}

			if ($items)
			{
				foreach ($items as $tag => $item)
				{
					// Do not return itself as result
					if ((int) $item->{$pk} != $id)
					{
						$multilanguageAssociations[$queryKey][$tag] = ($onlyIds) ? $item->id : $item;
					}
				}
			}
		}

		return $multilanguageAssociations[$queryKey];
	}

	/**
	 * Get the component associations.
	 *
	 * @param   string  $component   The name of the component or extension id.
	 *
	 * @return  array                The associated items.
	 *
	 * @since   3.6
	 */
	public static function getComponentAssociations($component = 'com_content')
	{
		static $associations = array();

		// It was called by the extension id, not the component name. Gets the component name.
		if (strpos($component, '.') !== false)
		{
			$component = array_shift(explode('.', $component));
		}

		// If tested before, don't test again. Return the previous result.
		if (isset($associations[$component]))
		{
			return $associations[$component];
		}

		// Set it as an empty array by default.
		$associations[$component] = array();

		// If component allows associations return the associations.
		if (JLanguageAssociations::isEnabled())
		{
			$className = JString::ucfirst(JString::str_ireplace('com_', '', $component)) . 'HelperAssociation';
			if (!(class_exists($className) && is_callable(array($className, 'getAssociations'))))
			{
				JLoader::register($className, JPath::clean(JPATH_COMPONENT_SITE . '/helpers/association.php'));
			}
			$associations[$component] = call_user_func(array($className, 'getAssociations'));
		}

		return $associations[$component];
	}

	/**
	 * Check if a component allows language associations.
	 *
	 * @param   string  $component   The name of the component or extension id.
	 *
	 * @return  boolean              True if component allows associations; false otherwise.
	 *
	 * @since   3.6
	 */
	public static function allowsAssociations($component = 'com_content')
	{
		static $associations = array();

		// It was called by the extension id, not the component name. Gets the component name.
		if (strpos($component, '.') !== false)
		{
			$component = array_shift(explode('.', $component));
		}

		// If tested before, don't test again. Return the previous result.
		if (isset($associations[$component]))
		{
			return $associations[$component];
		}

		// Set it as false by default.
		$associations[$component] = false;

		// If language associations are enabled check if there is a associations class in the component folder.
		if (JLanguageAssociations::isEnabled())
		{
			$className = JString::ucfirst(JString::str_ireplace('com_', '', $component)) . 'HelperAssociation';
			if (!(class_exists($className) && is_callable(array($className, 'getAssociations'))))
			{
				JLoader::register($className, JPath::clean(JPATH_COMPONENT_SITE . '/helpers/association.php'));
			}
			$associations[$component] = class_exists($className) && is_callable(array($className, 'getAssociations'));
		}

		return $associations[$component];
	}

	/**
	 * Method to determine if the language filter Items Associations parameter is enabled.
	 * This works for both site and administrator.
	 *
	 * @return  boolean  True if the parameter is implemented; false otherwise.
	 *
	 * @since   3.2
	 */
	public static function isEnabled()
	{
		static $enabled = null;

		// If already tested, don't test again. Return the previous result.
		if (!is_null($enabled))
		{
			return $enabled;
		}

		// Set it as false by default.
		$enabled = false;

		// If multilanguage is enabled, languague filter plugin is enabled and item_association param is set, return true.
		if (JLanguageMultilang::isEnabled())
		{
			$plugin = JPluginHelper::getPlugin('system', 'languagefilter');

			if (!empty($plugin))
			{
				$params = new Registry($plugin->params);
				$enabled  = (boolean) $params->get('item_associations', true);
			}
		}

		return $enabled;
	}
}
