<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

$data = $displayData;

// Receive overridable options
$data['options'] = !empty($data['options']) ? $data['options'] : array();

$noResultsText     = '';
$hideActiveFilters = false;
$showFilterButton  = false;
$showSelector      = false;
$selectorField     = isset($data['options']['selectorField']) ? $data['options']['selectorField'] : 'client_id';

// If a filter form exists.
if (isset($data['view']->filterForm) && !empty($data['view']->filterForm))
{
	// Checks if a selector (e.g. client_id) exists.
	$showSelector = $data['view']->filterForm->getFieldAttribute($selectorField, 'filtermode', '', '') == 'selector' ? true : false;

	// Checks if the filters button should exist.
	$filters = $data['view']->filterForm->getGroup('filter');
	$showFilterButton = isset($filters['filter_search']) && count($filters) === 1 ? false : true;

	// Checks if it should show the be hidden.
	$hideActiveFilters = empty($data['view']->activeFilters) ? true : false;

	// Check if the no results message should appear.
	if (isset($data['view']->total) && (int) $data['view']->total === 0)
	{
		$noResults = $data['view']->filterForm->getFieldAttribute('search', 'noresults', '', 'filter');
		if (!empty($noResults))
		{
			$noResultsText = JText::_($noResults);
		}
	}
}

// Set some basic options.
$customOptions = array(
	'filtersHidden'       => isset($data['options']['filtersHidden']) && $data['options']['filtersHidden'] ? $data['options']['filtersHidden'] : $hideActiveFilters,
	'filterButton'        => isset($data['options']['filterButton']) && $data['options']['filterButton'] ? $data['options']['filterButton'] : $showFilterButton,
	'defaultLimit'        => isset($data['options']['defaultLimit']) ? $data['options']['defaultLimit'] : JFactory::getApplication()->get('list_limit', 20),
	'searchFieldSelector' => '#filter_search',
	'selectorField'       => $selectorField,
	'orderFieldSelector'  => '#list_fullordering',
	'showNoResults'       => !empty($noResultsText) ? true : false,
	'noResultsText'       => !empty($noResultsText) ? $noResultsText : '',
	'formSelector'        => !empty($data['options']['formSelector']) ? $data['options']['formSelector'] : '#adminForm',
);

// Merge custom options in the options array.
$data['options'] = array_merge($customOptions, $data['options']);

// Add class to hide the active filters if needed.
$filtersActiveClass = $hideActiveFilters ? '' : ' js-stools-container-filters-visible';

// Load search tools
JHtml::_('searchtools.form', $data['options']['formSelector'], $data['options']);
?>
<div class="js-stools clearfix">
	<div class="clearfix">
		<?php if ($showSelector) : ?>
		<div class="js-stools-container-selector">
			<?php echo JLayoutHelper::render('joomla.searchtools.default.selector', $data); ?>
		</div>
		<?php endif; ?>
		<div class="js-stools-container-bar">
			<?php echo JLayoutHelper::render('joomla.searchtools.default.bar', $data); ?>
		</div>
		<div class="js-stools-container-list hidden-phone hidden-tablet">
			<?php echo JLayoutHelper::render('joomla.searchtools.default.list', $data); ?>
		</div>
	</div>
	<?php if ($data['options']['filterButton']) : ?>
	<div class="js-stools-container-filters hidden-phone clearfix<?php echo $filtersActiveClass; ?>">
		<?php echo JLayoutHelper::render('joomla.searchtools.default.filters', $data); ?>
	</div>
	<?php endif; ?>
</div>
<?php if ($data['options']['showNoResults']) : ?>
	<?php echo JLayoutHelper::render('joomla.searchtools.default.noitems', $data); ?>
<?php endif; ?>
