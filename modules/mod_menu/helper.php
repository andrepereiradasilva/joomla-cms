<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_menu
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_menu
 *
 * @package     Joomla.Site
 * @subpackage  mod_menu
 * @since       1.5
 */
class ModMenuHelper
{
	/**
	 * Get a list of the menu items.
	 *
	 * @param   \Joomla\Registry\Registry  $params  The module options.
	 *
	 * @return  array
	 *
	 * @since   1.5
	 */
	public static function getList($params)
	{
		// Get active menu item
		$base   = self::getBase($params);
		$levels = array_unique(JFactory::getUser()->getAuthorisedViewLevels());
		asort($levels);
		$key   = 'menu_items' . $params . implode(',', $levels) . '.' . $base->id;
		$cache = JFactory::getCache('mod_menu', '');

		// Already in cache.
		if ($cache->contains($key))
		{
			return $cache->get($key);
		}

		$items = JFactory::getApplication()->getMenu()->getItems(['menutype'], [$params->get('menutype', '')]);

		// No items in the menu.
		if (!$items)
		{
			$cache->store($items, $key);

			return [];
		}

		$path            = array_reverse($base->tree);
		$start           = (int) $params->get('startLevel', 1);
		$end             = (int) $params->get('endLevel', 0);
		$showAllChildren = (int) $params->get('showAllChildren', 1);
		$hiddenParents   = [];
		$lastitem        = 0;

		foreach ($items as $i => $item)
		{
			$itemParams               = new \Joomla\Registry\Registry; //$item->params;
			$showMenuItem             = (int) $itemParams->get('menu_show', 1);
			$lastMenuItem             = isset($items[$lastitem]) === true ? $items[$lastitem] : null;
			$items[$lastitem]->parent = $lastMenuItem !== null && $showMenuItem === 1 && $lastMenuItem->id === $item->parent_id;

			// Exclude items according to parameters.
			if (($start !== 0 && $start > $item->level)
				|| ($end !== 0 && $item->level > $end)
				|| ($showAllChildren === 0 && $item->level > 1 && isset($path[$item->parent_id]) === false)
				|| ($start > 1 && isset($path[$item->tree[$start - 2]]) === false))
			{
				unset($items[$i]);
				continue;
			}

			// Exclude item with menu item option set to exclude from menu modules
			if ($showMenuItem === 0 || isset($hiddenParents[$item->parent_id]) === true)
			{
				$hiddenParents[$item->id] = 1;
				unset($items[$i]);
				continue;
			}

			$item->deeper     = false;
			$item->shallower  = false;
			$item->level_diff = 0;

			if ($lastMenuItem !== null)
			{
				$items[$lastitem]->deeper     = $item->level > $lastMenuItem->level;
				$items[$lastitem]->shallower  = $item->level < $lastMenuItem->level;
				$items[$lastitem]->level_diff = $lastMenuItem->level - $item->level;
			}

			$item->flink          = $item->link;
			$convertUriSchemeFlag = null;

			// Route the menu item link.
			switch ($item->type)
			{
				// No further action needed.
				case 'separator':
				case 'heading':
					break;

				// If this is an internal Joomla link, ensure the Itemid is set.
				case 'url':
					// If this is an internal Joomla link.
					if (strpos($item->flink, 'index.php?') !== false)
					{
						// Ensure the Itemid is set.
						if (strpos($item->link, 'Itemid=') === false)
						{
							$item->flink = $item->link . '&Itemid=' . $item->id;
						}

						// Check if a conversion of the url scheme in absolute url is needed (https to http or https to http).
						if (($secure = $itemParams->get('secure')) !== null && stripos($item->flink, 'http:') === 0)
						{
							$convertUriSchemeFlag = $secure;
						}
					}

					break;

				case 'alias':
					$item->flink = 'index.php?Itemid=' . $itemParams->get('aliasoptions', '');
					break;

				default:
					$item->flink = 'index.php?Itemid=' . $item->id;
					break;
			}

			//$item->flink = JRoute::_($item->flink, true, $convertUriSchemeFlag);

			$menuAnchorCss   = $itemParams->get('menu-anchor_css');
			$menuAnchorTitle = $itemParams->get('menu-anchor_title');
			$menuAnchorRel   = $itemParams->get('menu-anchor_rel');
			$menuImage       = $itemParams->get('menu_image');

			// We prevent the double encoding because for some reason the $item is shared for menu modules and we get double encoding
			// when the cause of that is found the argument should be removed
			$item->title        = htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false);
			$item->anchor_css   = $menuAnchorCss !== null ? htmlspecialchars($menuAnchorCss, ENT_COMPAT, 'UTF-8', false) : '';
			$item->anchor_title = $menuAnchorTitle !== null ? htmlspecialchars($menuAnchorTitle, ENT_COMPAT, 'UTF-8', false) : '';
			$item->anchor_rel   = $menuAnchorRel !== null ? htmlspecialchars($menuAnchorRel, ENT_COMPAT, 'UTF-8', false) : '';
			$item->menu_image   = $menuImage !== null ? htmlspecialchars($menuImage, ENT_COMPAT, 'UTF-8', false) : '';

			// Preserve the last item key for the next loop.
			$lastitem = $i;
		}

		if ($lastMenuItem !== null)
		{
			$level                        = $start ?: 1;
			$items[$lastitem]->deeper     = $level > $lastMenuItem->level;
			$items[$lastitem]->shallower  = $level < $lastMenuItem->level;
			$items[$lastitem]->level_diff = $lastMenuItem->level - $level;
			$items[$lastitem]->parent     = false;
		}

		// Store in cache.
		$cache->store($items, $key);

		return $items;
	}

	/**
	 * Get base menu item.
	 *
	 * @param   \Joomla\Registry\Registry  $params  The module options.
	 *
	 * @return  object
	 *
	 * @since	3.0.2
	 */
	public static function getBase($params)
	{
		// Get base menu item from parameters
		$base = $params->get('base');

		// Use active menu item if no base found
		return $base !== null ? JFactory::getApplication()->getMenu()->getItem($base) : self::getActive();
	}

	/**
	 * Get active menu item.
	 *
	 * @return  object
	 *
	 * @since	3.0.2
	 */
	public static function getActive()
	{
		$active = JFactory::getApplication()->getMenu()->getActive();

		return $active ?: self::getDefault();
	}

	/**
	 * Get default menu item (home page) for current language.
	 *
	 * @return  object
	 */
	public static function getDefault()
	{
		$app  = JFactory::getApplication();
		$menu = $app->getMenu();

		return JLanguageMultilang::isEnabled() === true ? $menu->getDefault($app->getLanguage()->getTag()) : $menu->getDefault();
	}
}
