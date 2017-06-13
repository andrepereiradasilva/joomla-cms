<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Data set class for JHtmlSelect.
 *
 * @package     Joomla.UnitTest
 * @subpackage  HTML
 * @since       3.1
 */
class JHtmlSelectTest_DataSet
{
	public static $genericTest = [
		// @todo remove: [$expected, $data, $name, $attribs = null, $optKey = 'value', $optText = 'text',
		// 						$selected = null, $idtag = false, $translate = false]
		[
			"<select id=\"myName\" name=\"myName\">\n\t<option value=\"1\">Foo</option>\n\t<option value=\"2\">Bar</option>\n</select>\n",
			[
				[
					'value' => '1',
					'text'  => 'Foo',
				],
				[
					'value' => '2',
					'text'  => 'Bar',
				],
			],
			'myName',
		],
		[
			"<select id=\"myId\" name=\"myName\">\n\t<option value=\"1\">Foo</option>\n\t<option value=\"2\" selected=\"selected\">Bar</option>\n</select>\n",
			[
				[
					'value' => '1',
					'text'  => 'Foo',
				],
				[
					'value' => '2',
					'text'  => 'Bar',
				],
			],
			'myName',
			null,
			'value',
			'text',
			'2',
			'myId',
		],
		[
			"<select id=\"myId\" name=\"myName\">\n\t<option value=\"1\">Foo</option>\n\t<option value=\"2\" selected=\"selected\">Bar</option>\n</select>\n",
			[
				[
					'value' => '1',
					'text'  => 'Foo',
				],
				[
					'value' => '2',
					'text'  => 'Bar',
				],
			],
			'myName',
			[
				'id'          => 'myId',
				'list.select' => '2',
			],
		],
	];

	public static $radioTest = [
		// @todo remove: [$expected, $data, $name, $attribs = null, $optKey = 'value', $optText = 'text', $selected = null, $idtag = false,
		// 						$translate = false]
		[
			"<div class=\"controls\">
	<label for=\"yesId\" id=\"yesId-lbl\" class=\"radio\">
	
	<input type=\"radio\" name=\"myRadioListName\" id=\"yesId\" value=\"1\">Yes
	</label>
	<label for=\"myRadioListName0\" id=\"myRadioListName0-lbl\" class=\"radio\">
	
	<input type=\"radio\" name=\"myRadioListName\" id=\"myRadioListName0\" value=\"0\">No
	</label>
	<label for=\"myRadioListName-1\" id=\"myRadioListName-1-lbl\" class=\"radio\">
	
	<input type=\"radio\" name=\"myRadioListName\" id=\"myRadioListName-1\" value=\"-1\">Maybe
	</label>
</div>
",
			[
				[
					'value' => '1',
					'text'  => 'Yes',
					'id'    => "yesId",
				],
				[
					'value' => '0',
					'text'  => 'No',
				],
				[
					'value' => '-1',
					'text'  => 'Maybe',
				],
			],
			"myRadioListName"
		],
		[
			"<div class=\"controls\">
	<label for=\"fooId\" id=\"fooId-lbl\" class=\"radio\">
	
	<input type=\"radio\" name=\"myFooBarListName\" id=\"fooId\" value=\"foo\" class=\"i am radio\" onchange=\"jsfunc();\">FOO
	</label>
	<label for=\"myFooBarListNamebar\" id=\"myFooBarListNamebar-lbl\" class=\"radio\">
	
	<input type=\"radio\" name=\"myFooBarListName\" id=\"myFooBarListNamebar\" value=\"bar\" class=\"i am radio\" onchange=\"jsfunc();\">BAR
	</label>
</div>
",
			[
				[
					'key' => 'foo',
					'val' => 'FOO',
					'id'  => "fooId",
				],
				[
					'key' => 'bar',
					'val' => 'BAR',
				],
			],
			"myFooBarListName",
			[
				'class'    => 'i am radio',
				'onchange' => 'jsfunc();',
			],
			'key',
			'val',
		],
	];

	public static $optionsTest = [
		// @todo remove: [$expected, $arr, $optKey = 'value', $optText = 'text', $selected = null, $translate = false]
		[
			"<option value=\"1\">&nbsp;Test</option>\n",
			[
				[
					'value' => '1',
					'text'  => '&nbsp;Test',
				],
			],
		],
		[
			"<option value=\"1\" disabled=\"disabled\">&nbsp;Test</option>\n",
			[
				[
					'value'   => '1',
					'text'    => '&nbsp;Test',
					'disable' => true,
				],
			],
		],
		[
			"<option value=\"1\">&nbsp;Test</option>\n",
			[
				[
					'optionValue' => '1',
					'optionText'  => '&nbsp;Test',
				],
			],
			[
				'option.key'  => 'optionValue',
				'option.text' => 'optionText'
			],
		],
		[
			"<option value=\"1\" id=\"myId\" label=\"My Label\" readonly>&nbsp;Test</option>\n",
			[
				[
					'value'       => '1',
					'text'        => '&nbsp;Test -         ',
					'label'       => 'My Label',
					'id'          => 'myId',
					'extraAttrib' => 'readonly',
				],
			],
			[
				'option.label' => 'label',
				'option.id'    => 'id',
				'option.attr'  => 'extraAttrib',
			],
		],
		[
			"<option value=\"1\" class=\"foo bar\" style=\"color:red;\">&nbsp;Test</option>\n",
			[
				[
					'value' => '1',
					'text'  => '&nbsp;Test -         ',
					'label' => 'My Label',
					'id'    => 'myId',
					'attrs' => ['class' => "foo bar",'style' => 'color:red;',],
				],
			],
			[
				'option.attr' => 'attrs',
			],
		],
	];

	public static $optionTest = [
		// @todo remove: [$expected, $value, $text = '', $optKey = 'value', $optText = 'text', $disable = false]
		[
			[
				'value'   => 'optionValue',
				'text'    => 'optionText',
				'disable' => false,
			],
			'optionValue',
			'optionText'
		],
		[
			[
				'fookey'  => 'optionValue',
				'bartext' => 'optionText',
				'disable' => false,
			],
			'optionValue',
			'optionText',
			'fookey',
			'bartext',
		],
		[
			[
				'value'   => 'optionValue',
				'text'    => 'optionText',
				'disable' => true,
			],
			'optionValue',
			'optionText',
			'value',
			'text',
			true,
		],
		[
			[
				'optionValue'    => 'optionValue',
				'optionText'     => 'optionText',
				'foobarDisabled' => false,
				'lebal'          => 'My Label',
				'class'          => 'foo bar',
			],
			'optionValue',
			'optionText',
			[
				'option.disable' => 'foobarDisabled',
				'option.attr'    => 'class',
				'attr'           => 'foo bar',
				'option.label'   => 'lebal',
				'label'          => "My Label",
				'option.key'     => 'optionValue',
				'option.text'    => 'optionText',
			],
		],
	];
}
