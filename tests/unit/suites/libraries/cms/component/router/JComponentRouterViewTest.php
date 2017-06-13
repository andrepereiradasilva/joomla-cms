<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  Component
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

require_once __DIR__ . '/stubs/JComponentRouterViewInspector.php';
require_once __DIR__ . '/stubs/componentrouterrule.php';
require_once __DIR__ . '/stubs/JCategoriesMock.php';

/**
 * Test class for JComponentRouterAdvanced.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Component
 * @since       3.5
 */
class JComponentRouterViewTest extends TestCaseDatabase
{
	/**
	 * Object under test
	 *
	 * @var    JComponentRouterView
	 * @since  3.5
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 *
	 * @since   3.5
	 */
	protected function setUp()
	{
		parent::setUp();

		$app = $this->getMockCmsApp();

		JFactory::$application = $app;
		JFactory::$session = $this->getMockSession();

		$this->object = new JComponentRouterViewInspector($app, $app->getMenu());
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
		unset($app);
		parent::tearDown();
	}

	/**
	 * Gets the data set to be loaded into the database during setup
	 *
	 * @return  PHPUnit_Extensions_Database_DataSet_CsvDataSet
	 *
	 * @since   3.2
	 */
	protected function getDataSet()
	{
		$dataSet = new PHPUnit_Extensions_Database_DataSet_CsvDataSet(',', "'", '\\');

		$dataSet->addTable('jos_categories', JPATH_TEST_DATABASE . '/jos_categories.csv');

		return $dataSet;
	}

	/**
	 * Tests the registerView() method
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @covers  JComponentRouterView::registerView
	 */
	public function testRegisterView()
	{
		$views = $this->getComContentViews();

		foreach ($views as $view)
		{
			$this->object->registerView($view);
		}

		$this->assertEquals($views, $this->object->get('views'));
	}

	/**
	 * Tests the getViews() method
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @covers  JComponentRouterView::getViews
	 */
	public function testGetViews()
	{
		$views = $this->getComContentViews();

		foreach ($views as $view)
		{
			$this->object->registerView($view);
		}

		$this->assertEquals($views, $this->object->getViews());
	}

	/**
	 * Cases for testGetPath
	 *
	 * @return  array
	 *
	 * @since   3.5
	 */
	public function casesGetPath()
	{
		$cases   = [];
		// No view, so we don't have a path to return.
		$cases[] = [
			['task' => 'edit'],
			[]
		];

		// View without any parents and children
		$cases[] = [
			['view' => 'form'],
			['form' => true]
		];

		// View without any parents, but with children
		$cases[] = [
			['view' => 'categories'],
			['categories' => []]
		];

		// View with parent and children
		$cases[] = [
			[
				'view' => 'category',
				'id'   => '9'
			],
			[
				'category'   => [9 => '9:uncategorised'],
				'categories' => [9 => '9:uncategorised']
			]
		];

		//View with parent, no children
		$cases[] = [
			[
				'view'  => 'article',
				'id'    => '42:question-for-everything',
				'catid' => '9'
			],
			[
				'article'    => [42 => '42:question-for-everything'],
				'category'   => [9 => '9:uncategorised'],
				'categories' => [9 => '9:uncategorised']
			]
		];

		//View with parent, no children and nested view
		$cases[] = [
			[
				'view'  => 'article',
				'id'    => '42:question-for-everything',
				'catid' => '20'
			],
			[
				'article' => [
					42 => '42:question-for-everything'
				],
				'category' => [
					20 => '20:extensions',
					19 => '19:joomla',
					14 => '14:sample-data-articles'
				],
				'categories' => [
					20 => '20:extensions',
					19 => '19:joomla',
					14 => '14:sample-data-articles'
				]
			]
		];

		return $cases;
	}

	/**
	 * Tests the getPath() method
	 *
	 * @return  void
	 *
	 * @dataProvider  casesGetPath
	 * @since   3.5
	 * @covers  JComponentRouterView::getPath
	 */
	public function testGetPath($input, $result)
	{
		// This test requires an application registered to JFactory
		$this->saveFactoryState();
		JFactory::$application = $this->object->app;

		$views = $this->getComContentViews();
		$this->object->set('name', 'unittest');

		foreach ($views as $view)
		{
			$this->object->registerView($view);
		}

		$this->assertEquals($result, $this->object->getPath($input));

		$this->restoreFactoryState();
	}

	/**
	 * Tests the getRules() method
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @covers  JComponentRouterView::getRules
	 */
	public function testGetRules()
	{
		$rule = new TestComponentRouterRule($this->object);
		$this->object->attachRule($rule);
		$this->assertEquals([$rule], $this->object->getRules());
	}

	/**
	 * Tests the attachRules() method
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @covers  JComponentRouterView::attachRules
	 */
	public function testAttachRules()
	{
		$rule = new TestComponentRouterRule($this->object);
		$this->assertEquals([], $this->object->getRules());
		$this->object->attachRules([$rule]);
		$this->assertEquals([$rule], $this->object->getRules());
	}

	/**
	 * Tests the attachRule() method
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @covers  JComponentRouterView::attachRule
	 */
	public function testAttachRule()
	{
		$rule = new TestComponentRouterRule($this->object);
		$this->assertEquals([], $this->object->get('rules'));
		$this->object->attachRule($rule);
		$this->assertEquals([$rule], $this->object->get('rules'));
		$this->object->attachRule($rule);
		$this->assertEquals([$rule, $rule], $this->object->get('rules'));
	}

	/**
	 * Tests the detachRule() method
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @covers  JComponentRouterView::detachRule
	 */
	public function testDetachRule()
	{
		$rule = new TestComponentRouterRule($this->object);
		$this->object->attachRule($rule);
		$this->assertEquals([$rule], $this->object->get('rules'));
		$this->assertTrue($this->object->detachRule($rule));
		$this->assertEquals([], $this->object->get('rules'));
		$this->assertFalse($this->object->detachRule($rule));
	}

	/**
	 * Tests the preprocess() method
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @covers  JComponentRouterView::preprocess
	 */
	public function testPreprocess()
	{
		$rule = new TestComponentRouterRule($this->object);
		$this->object->attachRule($rule);
		$this->assertEquals(['key' => 'value', 'testrule' => 'yes'], $this->object->preprocess(['key' => 'value']));
	}

	/**
	 * Tests the build() method
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @covers  JComponentRouterView::build
	 */
	public function testBuild()
	{
		$rule = new TestComponentRouterRule($this->object);
		$this->object->attachRule($rule);
		$query = ['key' => 'value', 'test' => 'true'];
		$this->assertEquals(['testrule-run'], $this->object->build($query));
		$this->assertEquals(['key' => 'value'], $query);
	}

	/**
	 * Tests the parse() method
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @covers  JComponentRouterView::parse
	 */
	public function testParse()
	{
		$rule = new TestComponentRouterRule($this->object);
		$this->object->attachRule($rule);
		$segments = ['testrun', 'getsdropped'];
		$this->assertEquals(['testparse' => 'run'], $this->object->parse($segments));
		$this->assertEquals(['testrun'], $segments);
	}

	/**
	 * Tests the getName() method
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @covers  JComponentRouterView::getName
	 */
	public function testGetName()
	{
		$this->object->set('name', 'test');
		$this->assertEquals('test', $this->object->getName());
		$this->object->set('name', null);
		$this->assertEquals('jcomponent', $this->object->getName());
	}

	/**
	 * Tests the getName() method and if it throws the right Exception
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @expectedException Exception
	 * @covers  JComponentRouterView::getName
	 */
	public function testGetNameException()
	{
		$object = new FakeComponentURLCreator($this->object->app, $this->object->menu);
		$object->getName();
	}

	/**
	 * As view testdata, use the view configuration of com_content
	 *
	 * @return array|JComponentRouterViewconfiguration
	 */
	protected function getComContentViews()
	{
		$categories = new JComponentRouterViewconfiguration('categories');
		$categories->setKey('id');
		$category = new JComponentRouterViewconfiguration('category');
		$category->setKey('id')->setParent($categories, 'catid')->setNestable()->addLayout('blog');
		$article = new JComponentRouterViewconfiguration('article');
		$article->setKey('id')->setParent($category, 'catid');
		$archive = new JComponentRouterViewconfiguration('archive');
		$featured = new JComponentRouterViewconfiguration('featured');
		$form = new JComponentRouterViewconfiguration('form');

		return [
			'categories' => $categories,
			'category'   => $category,
			'article'    => $article,
			'archive'    => $archive,
			'featured'   => $featured,
			'form'       => $form
		];
	}
}
