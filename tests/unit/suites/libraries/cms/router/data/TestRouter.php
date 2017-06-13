<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Router
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
class TestRouter implements JComponentRouterInterface
{
	public function preprocess($query)
	{
		$query['testvar'] = 'testvalue';

		return $query;
	}

	public function parse(&$segments)
	{
		return [];
	}

	public function build(&$query)
	{
		return [];
	}
}

class Test2Router implements JComponentRouterInterface
{
	public function preprocess($query)
	{
		return $query;
	}

	public function parse(&$segments)
	{
		return ['testvar' => 'testvalue'];
	}

	public function build(&$query)
	{
		return ['router-test', 'another-segment'];
	}
}

class Test3Router implements JComponentRouterInterface
{
	public function preprocess($query)
	{
		return $query;
	}

	public function parse(&$segments)
	{
		return [];
	}

	public function build(&$query)
	{
		unset($query['Itemid']);

		return [];
	}
}
