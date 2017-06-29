<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_menu
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$id = '';

if ($tagId = $params->get('tag_id', ''))
{
	$id = ' id="' . $tagId . '"';
}

$pathSize    = count($path);
$pathFlipped = array_flip($path);

// The menu class is deprecated. Use nav instead
?>
<ul<?php echo $id; ?> class="nav flex-column<?php echo $class_sfx; ?>">
<?php foreach ($list as $i => $item)
{
	$aliasId = $item->type === 'alias' ? (int) $item->params->get('aliasoptions') : null;
	$class   = 'nav-item';

	if ($item->id === $default_id)
	{
		$class .= ' default';
	}

	if ($item->id === $active_id || ($aliasId !== null && $aliasId === $active_id))
	{
		$class .= ' current';
	}

	if (isset($pathFlipped[$item->id]) === true)
	{
		$class .= ' active';
	}
	elseif ($item->type === 'alias')
	{
		if ($pathSize > 0 && $aliasId === $path[$pathSize - 1])
		{
			$class .= ' active';
		}
		elseif (isset($pathFlipped[$aliasId]) === true)
		{
			$class .= ' alias-parent-active';
		}
	}

	if ($item->type === 'separator')
	{
		$class .= ' divider';
	}

	if ($item->deeper === true)
	{
		$class .= ' deeper';
	}

	if ($item->parent === true)
	{
		$class .= ' parent';
	}

	echo '<li class="' . $class . '">';

	switch ($item->type) :
		case 'separator':
		case 'component':
		case 'heading':
		case 'url':
			require JModuleHelper::getLayoutPath('mod_menu', 'default_' . $item->type);
			break;

		default:
			require JModuleHelper::getLayoutPath('mod_menu', 'default_url');
			break;
	endswitch;

	// The next item is deeper.
	if ($item->deeper === true)
	{
		echo '<ul class="list-unstyled small">';
	}
	// The next item is shallower.
	elseif ($item->shallower === true)
	{
		echo '</li>';
		echo str_repeat('</ul></li>', $item->level_diff);
	}
	// The next item is on the same level.
	else
	{
		echo '</li>';
	}
}
?></ul>
