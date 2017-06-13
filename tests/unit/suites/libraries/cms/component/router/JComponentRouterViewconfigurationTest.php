<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  Component
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Test class for JComponentRouterViewconfiguration
 *
 * @package     Joomla.UnitTest
 * @subpackage  Component
 * @since       3.4
 */
class JComponentRouterViewconfigurationTest extends TestCase
{
	/**
	 * Object under test
	 *
	 * @var    JComponentRouterViewconfiguration
	 * @since  3.4
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->object = new JComponentRouterViewconfiguration('test');
	}

	/**
	 * Overrides the parent tearDown method.
	 *
	 * @return  void
	 *
	 * @see     \PHPUnit\Framework\TestCase::tearDown()
	 * @since   3.6
	 */
	protected function tearDown()
	{
		unset($this->object);
		parent::tearDown();
	}

	/**
	 * Test JComponentRouterViewconfiguration::__construct
	 *
	 * @return  void
	 *
	 * @since   3.4
	 * @covers  JComponentRouterViewconfiguration::__construct
	 */
	public function testConstruct()
	{
		$this->assertInstanceOf('JComponentRouterViewconfiguration', $this->object);
		$this->assertEquals('test', $this->object->name);
		$this->assertEquals(['test'], $this->object->path);
	}

	/**
	 * Test JComponentRouterViewconfiguration::setName
	 *
	 * @return  void
	 *
	 * @since   3.4
	 * @covers  JComponentRouterViewconfiguration::setName
	 */
	public function testSetName()
	{
		$this->assertEquals('test', $this->object->name);
		$this->assertEquals(['test'], $this->object->path);
		$this->assertEquals($this->object, $this->object->setName('name'));
		$this->assertEquals('name', $this->object->name);
		$this->assertEquals(['name'], $this->object->path);
		$object2 = new JComponentRouterViewconfiguration('parent');
		$this->object->setParent($object2);
		$this->assertEquals(['parent', 'name'], $this->object->path);
		$this->object->setName('name2');
		$this->assertEquals(['parent', 'name2'], $this->object->path);
	}

	/**
	 * Test JComponentRouterViewconfiguration::setViewKey
	 *
	 * @return  void
	 *
	 * @since   3.4
	 * @covers  JComponentRouterViewconfiguration::setKey
	 */
	public function testSetKey()
	{
		$this->assertFalse($this->object->key);
		$this->assertEquals($this->object, $this->object->setKey('id'));
		$this->assertEquals('id', $this->object->key);
	}

	/**
	 * Test JComponentRouterViewconfiguration::setParent
	 *
	 * @return  void
	 *
	 * @since   3.4
	 * @covers  JComponentRouterViewconfiguration::setParent
	 */
	public function testSetParent()
	{
		$parent = new JComponentRouterViewconfiguration('parent');

		// View has no parent
		$this->assertEquals(false, $this->object->parent);
		$this->assertEquals(false, $this->object->parent_key);
		$this->assertEquals([], $this->object->children);
		$this->assertEquals([], $this->object->child_keys);
		$this->assertEquals(['test'], $this->object->path);
		$this->assertEquals([], $parent->children);
		$this->assertEquals([], $parent->child_keys);

		// Assign View a parent
		$this->assertEquals($this->object, $this->object->setParent($parent));
		$this->assertEquals($parent, $this->object->parent);
		$this->assertEquals(false, $this->object->parent_key);
		$this->assertEquals([], $this->object->children);
		$this->assertEquals([$this->object], $parent->children);
		$this->assertEquals(['parent', 'test'], $this->object->path);
		$this->assertEquals([], $parent->child_keys);

		// Re-assign View a parent, this time with an ID
		$parent2 = new JComponentRouterViewconfiguration('category');
		$this->assertEquals($this->object, $this->object->setParent($parent2, 'catid'));
		$this->assertEquals($parent2, $this->object->parent);
		$this->assertEquals('catid', $this->object->parent_key);
		$this->assertEquals([], $this->object->children);
		$this->assertEquals([$this->object], $parent2->children);
		$this->assertEquals(['category', 'test'], $this->object->path);
		$this->assertEquals(['catid'], $parent2->child_keys);
		// Make sure that the original parent is cleaned up
		$this->assertEquals([], $parent->children);
		$this->assertEquals([], $parent->child_keys);

		// Re-assign View a parent, again with an ID
		$parent3 = new JComponentRouterViewconfiguration('form');
		$this->assertEquals($this->object, $this->object->setParent($parent3, 'formid'));
		$this->assertEquals($parent3, $this->object->parent);
		$this->assertEquals('formid', $this->object->parent_key);
		$this->assertEquals(['formid'], $parent3->child_keys);
		// Make sure that the original parent is cleaned up
		$this->assertEquals([], $parent2->children);
		$this->assertEquals([], $parent2->child_keys);
	}

	/**
	 * Test JComponentRouterViewconfiguration::setNestable
	 *
	 * @return  void
	 *
	 * @since   3.4
	 * @covers  JComponentRouterViewconfiguration::setNestable
	 */
	public function testSetNestable()
	{
		$this->assertFalse($this->object->nestable);
		$this->assertEquals($this->object, $this->object->setNestable());
		$this->assertTrue($this->object->nestable);
		$this->assertEquals($this->object, $this->object->setNestable(false));
		$this->assertFalse($this->object->nestable);
		$this->assertEquals($this->object, $this->object->setNestable(true));
		$this->assertTrue($this->object->nestable);
	}

	/**
	 * Test JComponentRouterViewconfiguration::addLayout
	 *
	 * @return  void
	 *
	 * @since   3.4
	 * @covers  JComponentRouterViewconfiguration::addLayout
	 */
	public function testAddLayout()
	{
		$this->assertEquals(['default'], $this->object->layouts);
		$this->assertEquals($this->object, $this->object->addLayout('form'));
		$this->assertEquals(['default', 'form'], $this->object->layouts);
		// Make sure that a layout can only be added once
		$this->object->addLayout('form');
		$this->assertEquals(['default', 'form'], $this->object->layouts);
	}

	/**
	 * Test JComponentRouterViewconfiguration::removeLayout
	 *
	 * @return  void
	 *
	 * @since   3.4
	 * @covers  JComponentRouterViewconfiguration::removeLayout
	 */
	public function testRemoveLayout()
	{
		$this->assertEquals(['default'], $this->object->layouts);
		$this->object->addLayout('form');
		$this->assertEquals($this->object, $this->object->removeLayout('default'));
		$this->assertEquals([1 => 'form'], $this->object->layouts);
		$this->assertEquals($this->object, $this->object->removeLayout('fake'));
		$this->assertEquals([1 => 'form'], $this->object->layouts);
		$this->object->removeLayout('form');
		$this->assertEquals([], $this->object->layouts);
	}
}
