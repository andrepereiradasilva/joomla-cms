<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Access
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Access helper class
 *
 * @since  __DEPLOY_VERSION___
 */
class JAccessHelper
{
	/**
	 * Method to get the assets that need clean up.
	 *
	 * @return  array  Array of assets that can be cleaned.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getListOfAssetsToClean()
	{
		$ListOfAssetsToClean = array();

		$db = JFactory::getDbo();

		// Get all rules
		$query = $db->getQuery(true)
			->select($db->qn(array('id', 'name', 'parent_id', 'rules')))
			->from($db->qn('#__assets'));

		$assets = $db->setQuery($query)->loadObjectList('id');

		foreach($assets as $assetId => $asset)
		{
			// No need to check empty rules.
			if ($asset->rules === '{}')
			{
				continue;
			}

			// No need to check empty rules.
			if ($asset->rules === '[]')
			{
				$ListOfAssetsToClean[] = array(
					'id'        => $asset->id,
					'name'      => $asset->name,
					'rules-old' => '[]',
					'rules-new' => '{}',
				);
				continue;
			}

			// Get the possible actions for this asset.
			if (strpos($asset->name, '.') !== false)
			{
				$parts           = explode('.', $asset->name);
				$originalActions = JAccess::getActionsFromFile(JPATH_ADMINISTRATOR . '/components/' . $parts[0] . '/access.xml', '/access/section[@name=\'' . $parts[1] . '\']/');
			}
			else
			{
				$originalActions = JAccess::getActionsFromFile(JPATH_ADMINISTRATOR . '/components/' . $asset->name . '/access.xml');
			}

			$actions = array();

			if ($originalActions !== false)
			{
				foreach($originalActions as $action)
				{
					$actions[$action->name] = 1;
				}
			}

			// Get asset rules.
			$rules = json_decode($asset->rules, true);

			// Remove non existent actions. We need to check the actions because some compoennts don't have the access.xml.
			if ($actions !== array())
			{
				$rules = array_intersect_key($rules, $actions);
			}

			// For each permissions, remove empty rules and rule if parent asset as the same value.
			foreach ($rules as $permission => $values)
			{
				// Remove empty rules for the permission.
				if ($values === array())
				{
					unset($rules[$permission]);
					continue;
				}

				// Remove rules if parent asset as the same value.
				$parentAsset = isset($assets[$asset->parent_id]) ? $assets[$asset->parent_id] : false;

				while ($parentAsset)
				{
					// Get the asset parent rules.
					$parentRules = json_decode($parentAsset->rules, true);

					if (isset($parentRules[$permission]))
					{
						if ($rules[$permission] === $parentRules[$permission])
						{
							unset($rules[$permission]);
							continue 2;
						}
					}

					// Go to the next parent.
					$parentAsset = isset($assets[$parentAsset->parent_id]) ? $assets[$parentAsset->parent_id] : false;
				}
			}

			$rules = json_encode($rules, JSON_FORCE_OBJECT);

			// If there are changes update the assets table.
			if ($asset->rules !== $rules)
			{
				$ListOfAssetsToClean[] = array(
					'id'        => $asset->id,
					'name'      => $asset->name,
					'rules-old' => $asset->rules,
					'rules-new' => $rules,
				);
			}
		}

		return $ListOfAssetsToClean;
	}

	/**
	 * Method to clean the assets table.
	 *
	 * @param   array  Array of assets to be cleaned.
	 *
	 * @return  integer  The number of assets cleaned.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function cleanAssets(array $assets)
	{
		if ($assets === array())
		{
			return 0;
		}

		$db            = JFactory::getDbo();
		$query         = $db->getQuery(true);
		$assetsCleaned = 0;

		foreach($assets as $asset)
		{
			$query->clear()
				->update($db->qn('#__assets'))
				->set($db->qn('rules') . ' = ' . $db->q($asset['rules-new']))
				->where($db->qn('id') . ' = ' . $asset['id']);

			$db->setQuery($query)->execute();

			$assetsCleaned++;
		}

		return $assetsCleaned;
	}
}
