<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Form Field class for the Joomla Platform.
 * Field for assigning permissions to groups for a given asset
 *
 * @see    JAccess
 * @since  11.1
 */
class JFormFieldRules extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Rules';

	/**
	 * The section.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $section;

	/**
	 * The component.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $component;

	/**
	 * The assetField.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $assetField;

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   3.2
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'section':
			case 'component':
			case 'assetField':
				return $this->$name;
		}

		return parent::__get($name);
	}

	/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to the the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'section':
			case 'component':
			case 'assetField':
				$this->$name = (string) $value;
				break;

			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   3.2
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->section    = $this->element['section'] ? (string) $this->element['section'] : '';
			$this->component  = $this->element['component'] ? (string) $this->element['component'] : '';
			$this->assetField = $this->element['asset_field'] ? (string) $this->element['asset_field'] : 'asset_id';
		}

		return $return;
	}

	/**
	 * Method to get the field input markup for Access Control Lists.
	 * Optionally can be associated with a specific component and section.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 * @todo:   Add access check.
	 */
	protected function getInput()
	{
		// Initialise some field attributes.
		$section   = $this->section;
		$component = !$this->component ? 'root.1' : $this->component;

		// Get the actions for the asset.
		$actions = JAccess::getActions($component, $section);

		// Iterate over the children and add to the actions.
		foreach ($this->element->children() as $el)
		{
			if ($el->getName() == 'action')
			{
				$actions[] = (object) array(
					'name' => (string) $el['name'],
					'title' => (string) $el['title'],
					'description' => (string) $el['description'],
				);
			}
		}

		// Get the asset id.
		// Note that for global configuration, com_config injects asset_id = 1 into the form.
		$assetId = $this->form->getValue($this->assetField);

		// If it's a new item tell the user to save before changing permissions.
		if (!$assetId && $component !== 'root.1' && $section !== 'component')
		{
			return '<div class="alert alert-info">' . JText::_('JLIB_RULES_NOTICE_SAVE_TO_EDIT_PERMISSIONS') . '</div>';
		}

		// Load the asset.
		$asset = JTable::getInstance('Asset');

		// For items.
		if ($assetId)
		{
			$asset->load($assetId);
		}
		// For components and global config.
		else
		{
			$asset->load(array('name' => $component));
		}

		// There is no asset in the database, inform the user to save before trying to change permissions.
		if (!$asset->id)
		{
			return '<div class="alert alert-warning">' . JText::_('JLIB_RULES_WARNING_ASSET_NOT_FOUND') . '</div>';
		}

		// Render the permisisons tabs.
		JHtml::_('bootstrap.tooltip');

		// Add Javascript for permission change
		JHtml::_('script', 'system/permissions.js', array('version' => 'auto', 'relative' => true));

		// Load JavaScript message titles
		JText::script('ERROR');
		JText::script('WARNING');
		JText::script('NOTICE');
		JText::script('MESSAGE');

		// Add strings for JavaScript error translations.
		JText::script('JLIB_JS_AJAX_ERROR_CONNECTION_ABORT');
		JText::script('JLIB_JS_AJAX_ERROR_NO_CONTENT');
		JText::script('JLIB_JS_AJAX_ERROR_OTHER');
		JText::script('JLIB_JS_AJAX_ERROR_PARSE');
		JText::script('JLIB_JS_AJAX_ERROR_TIMEOUT');

		// Full width format.

		// Get the rules for just this asset (non-recursive).
		$assetRules = JAccess::getAssetRules($asset->id, false, false);

		// Get the available user groups.
		$groups = JHelperUsergroups::getInstance()->getAll();

		// Ajax request data.
		$ajaxUri = JRoute::_('index.php?option=com_config&task=config.store&format=json&' . JSession::getFormToken() . '=1');

		// Prepare output
		$html = array();

		// Description
		$html[] = '<p class="rule-desc">' . JText::_('JLIB_RULES_SETTINGS_DESC') . '</p>';

		// Begin tabs
		$html[] = '<div class="tabbable tabs-left" data-ajaxuri="' . $ajaxUri . '" id="permissions-sliders">';

		// Building tab nav
		$html[] = '<ul class="nav nav-tabs">';

		foreach ($groups as $group)
		{
			// Initial Active Tab
			$active = '';

			if ((int) $group->id === 1)
			{
				$active = 'active';
			}

			$html[] = '<li class="' . $active . '">';
			$html[] = '<a href="#permission-' . $group->id . '" data-toggle="tab">';
			$html[] = JLayoutHelper::render('joomla.html.treeprefix', array('level' => $group->level + 1)) . $group->title;
			$html[] = '</a>';
			$html[] = '</li>';
		}

		$html[] = '</ul>';

		$html[] = '<div class="tab-content">';

		// Start a row for each user group.
		foreach ($groups as $group)
		{
			// Initial Active Pane
			$active = '';

			if ((int) $group->id === 1)
			{
				$active = ' active';
			}

			$html[] = '<div class="tab-pane' . $active . '" id="permission-' . $group->id . '">';
			$html[] = '<table class="table table-striped">';
			$html[] = '<thead>';
			$html[] = '<tr>';

			$html[] = '<th class="actions" id="actions-th' . $group->id . '">';
			$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_ACTION') . '</span>';
			$html[] = '</th>';

			$html[] = '<th class="settings" id="settings-th' . $group->id . '">';
			$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_SELECT_SETTING') . '</span>';
			$html[] = '</th>';

			$html[] = '<th id="aclactionth' . $group->id . '">';
			$html[] = '<span class="acl-action">' . JText::_('JLIB_RULES_CALCULATED_SETTING') . '</span>';
			$html[] = '</th>';

			$html[] = '</tr>';
			$html[] = '</thead>';
			$html[] = '<tbody>';

			foreach ($actions as $action)
			{
				$html[] = '<tr>';
				$html[] = '<td headers="actions-th' . $group->id . '">';
				$html[] = '<label for="' . $this->id . '_' . $action->name . '_' . $group->id . '" class="hasTooltip" title="'
					. JHtml::_('tooltipText', $action->title, $action->description) . '">';
				$html[] = JText::_($action->title);
				$html[] = '</label>';
				$html[] = '</td>';

				$html[] = '<td headers="settings-th' . $group->id . '">';

				$html[] = '<select onchange="sendPermissions.call(this, event)" data-chosen="true" class="input-small novalidate"'
					. ' name="' . $this->name . '[' . $action->name . '][' . $group->id . ']"'
					. ' id="' . $this->id . '_' . $action->name	. '_' . $group->id . '"'
					. ' title="' . strip_tags(JText::sprintf('JLIB_RULES_SELECT_ALLOW_DENY_GROUP', JText::_($action->title), trim($group->title))) . '">';

				/**
				 * Possible values:
				 * null = not set means inherited
				 * false = denied
				 * true = allowed
				 */

				// Get the actual setting for the action for this group.
				$ruleValue = $assetRules->allow($action->name, $group->id);

				// Build the dropdowns for the permissions sliders

				// The parent group has "Not Set", all children can rightly "Inherit" from that.
				$html[] = '<option value=""' . ($ruleValue === null ? ' selected="selected"' : '') . '>'
					. JText::_(empty($group->parent_id) && $asset->parent_id === 0 ? 'JLIB_RULES_NOT_SET' : 'JLIB_RULES_INHERITED') . '</option>';
				$html[] = '<option value="1"' . ($ruleValue === true ? ' selected="selected"' : '') . '>' . JText::_('JLIB_RULES_ALLOWED')
					. '</option>';
				$html[] = '<option value="0"' . ($ruleValue === false ? ' selected="selected"' : '') . '>' . JText::_('JLIB_RULES_DENIED')
					. '</option>';

				$html[] = '</select>&#160; ';

				$html[] = '<span id="icon_' . $this->id . '_' . $action->name . '_' . $group->id . '"' . '></span>';
				$html[] = '</td>';

				// Build the Calculated Settings column.
				$html[] = '<td headers="aclactionth' . $group->id . '">';

				// Get the calculated permission data.
				$calculated = JAccess::getCalculatedPermission($asset->id, $asset->parent_id, $group->id, $group->parent_id, $action->name);
				$locked     = $calculated['locked'] ? '<span class="icon-lock icon-white"></span>' : '';
				$allowed    = $calculated['allowed'] ? 'label-success' : 'label-important';

				$html[] = '<span class="label ' . $allowed . '">' . $locked . $calculated['text'] . '</span>';
				$html[] = '</td>';
				$html[] = '</tr>';
			}

			$html[] = '</tbody>';
			$html[] = '</table></div>';
		}

		$html[] = '</div></div>';
		$html[] = '<div class="clr"></div>';
		$html[] = '<div class="alert">';
		$html[] = $section === 'component' || $section === null ? JText::_('JLIB_RULES_SETTING_NOTES') : JText::_('JLIB_RULES_SETTING_NOTES_ITEM');
		$html[] = '</div>';

		return implode("\n", $html);
	}

	/**
	 * Get a list of the user groups.
	 *
	 * @return  array
	 *
	 * @since   11.1
	 * @deprecated   __DEPLOY_VERSION__ no replacement. Use JHelperUsergroups::getInstance()->getAll() instead.
	 */
	protected function getUserGroups()
	{
		$options = JHelperUsergroups::getInstance()->getAll();

		foreach ($options as &$option)
		{
			$option->value = $option->id;
			$option->text  = $option->title;
		}

		return array_values($options);
	}
}
