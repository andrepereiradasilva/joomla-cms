<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

require_once __DIR__ . '/stubs/JHtmlBootstrapInspector.php';
require_once __DIR__ . '/stubs/JHtmlJqueryInspector.php';

/**
 * Test class for JHtmlBootstrap.
 * Generated by PHPUnit on 2012-08-16 at 17:39:35.
 */
class JHtmlBootstrapTest extends TestCase
{
	/**
	 * Backup of the SERVER superglobal
	 *
	 * @var    array
	 * @since  3.1
	 */
	protected $backupServer;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	protected function setUp()
	{
		// Ensure the loaded states are reset
		JHtmlBootstrapInspector::resetLoaded();
		JHtmlJqueryInspector::resetLoaded();

		parent::setUp();

		$this->saveFactoryState();

		JFactory::$application = $this->getMockCmsApp();
		JFactory::$config = $this->getMockConfig();
		JFactory::$document = $this->getMockDocument();

		$this->backupServer = $_SERVER;

		$_SERVER['HTTP_HOST'] = 'example.com';
		$_SERVER['SCRIPT_NAME'] = '';
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	protected function tearDown()
	{
		$_SERVER = $this->backupServer;
		unset($this->backupServer);
		$this->restoreFactoryState();

		parent::tearDown();
	}

	/**
	 * Tests the affix method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testAffix()
	{
		// Initialise the affix script
		JHtmlBootstrap::affix();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $("#affix").affix({"offset": 10}); });',
			'Verify that the affix script is initialised'
		);
	}

	/**
	 * Tests the alert method.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function testAlert()
	{
		// Initialise the alert script
		JHtmlBootstrap::alert();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $(".alert").alert(); });',
			'Verify that the alert script is initialised'
		);
	}

	/**
	 * Tests the button method.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function testButton()
	{
		// Initialise the alert script
		JHtmlBootstrap::button();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the button method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $(".button").button(); });',
			'Verify that the button script is initialised'
		);
	}

	/**
	 * Tests the carousel method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testCarousel()
	{
		// Initialise the carousel script
		JHtmlBootstrap::carousel();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $(".carousel").carousel({"interval": 5000,"pause": "hover"}); });',
			'Verify that the carousel script is initialised'
		);
	}

	/**
	 * Tests the dropdown method.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function testDropdown()
	{
		// Initialise the dropdown script
		JHtmlBootstrap::dropdown();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the dropdown method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $(".dropdown-toggle").dropdown(); });',
			'Verify that the dropdown script is initialised'
		);
	}

	/**
	 * Tests the framework method.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function testFramework()
	{
		// Initialise the Bootstrap JS framework
		JHtmlBootstrap::framework();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/jquery.min.js',
			$document->_scripts,
			'Verify that Bootstrap initializes jQuery as well'
		);

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that Bootstrap initializes Bootstrap'
		);
	}

	/**
	 * Tests the modal method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testModal()
	{
		// Initialise the modal script
		JHtmlBootstrap::modal();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $("#modal").modal({"backdrop": true,"keyboard": true,"show": false,"remote": ""}); });',
			'Verify that the modal script is initialised'
		);
	}

	/**
	 * Tests the renderModal method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testRenderModal()
	{
		// Get the rendered output.
		$modal = JHtmlBootstrap::renderModal();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		// Check the modal's html structure
		$matcher = array(
			'id'         => 'modal',
			'tag'        => 'div',
			'attributes' => array('class' => 'modal hide fade'),
			'child'      => array(
				'attributes' => array('class' => 'modal-header'),
				'tag' => 'div'
			),
			'children'   => array('count' => 2)
		);

		$this->assertTag(
			$matcher,
			$modal,
			'Verify that the html structure of the modal is correct'
		);
	}

	/**
	 * Tests the popover method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testPopover()
	{
		// Initialise the popover script
		JHtmlBootstrap::popover();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $(".hasPopover").popover({"html": true,"trigger": "hover focus","container": "body"}); });',
			'Verify that the popover script is initialised'
		);
	}

	/**
	 * Tests the scrollspy method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testScrollspy()
	{
		// Initialise the scrollspy script
		JHtmlBootstrap::scrollspy();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $("#navbar").scrollspy({"offset": 10}); });',
			'Verify that the scrollspy script is initialised'
		);
	}

	/**
	 * Tests the tooltip method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testTooltip()
	{
		// Initialise the tooltip script
		JHtmlBootstrap::tooltip();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $(".hasTooltip").tooltip({"html": true,"container": "body"}); });',
			'Verify that the tooltip script is initialised'
		);
	}

	/**
	 * Tests the typeahead method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testTypeahead()
	{
		// Initialise the typeahead script
		JHtmlBootstrap::typeahead();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $(".typeahead").typeahead({"items": 8,"minLength": 1}); });',
			'Verify that the typeahead script is initialised'
		);
	}

	/**
	 * Tests the startAccordion method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testStartAccordion()
	{
		// Initialise the startAccordion script
		$html = JHtmlBootstrap::startAccordion();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){' . "\n\t" . '$(\'#myAccordian\').collapse({"parent": false,"toggle": false})' . "\n" . '});',
			'Verify that the startAccordion script is initialised'
		);

		// Check the modal's html structure
		$matcher = array(
			'id'         => 'myAccordian',
			'tag'        => 'div',
			'attributes' => array('class' => 'accordion')
		);

		$this->assertTag(
			$matcher,
			$html,
			'Verify that the html structure of the accordion is correct'
		);
	}

	/**
	 * Tests the endAccordion method
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function testEndAccordion()
	{
		$this->assertThat(
			JHtml::_('bootstrap.endAccordion'),
			$this->equalTo('</div>')
		);
	}

	/**
	 * Tests the addSlide method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testaddSlide()
	{
		// Must start an accordion first
		JHtmlBootstrap::startAccordion();

		// Initialise the addSlide script
		$html = JHtmlBootstrap::addSlide('myAccordian', 'myText', 'mySlide');

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){' . "\n\t" . '$(\'#myAccordian\').collapse({"parent": false,"toggle": false})' . "\n" . '});',
			'Verify that the addSlide script is initialised'
		);

		// Check the modal's html structure
		$matcher = array(
			'tag'        => 'div',
			'attributes' => array('class' => 'accordion-group'),
			'child'      => array(
				'tag'        => 'div',
				'attributes' => array('class' => 'accordion-heading')
			)
		);

		$this->assertTag(
			$matcher,
			$html,
			'Verify that the html structure of the slide is correct'
		);
	}

	/**
	 * Tests the endSlide method
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function testEndSlide()
	{
		$this->assertEquals('</div></div></div>', JHtmlBootstrap::endSlide());
	}

	/**
	 * Tests the startTabSet method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testStartTabSet()
	{
		// Initialise the startTabSet script
		$html = JHtmlBootstrap::startTabSet();

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$this->assertEquals(
			$document->_script['text/javascript'],
			'jQuery(function($){ $("#myTab a").click(function (e) {e.preventDefault();$(this).tab("show");});});',
			'Verify that the startTabSet script is initialised'
		);

		// Check the tab set's html structure
		$matcher = array(
			'id'  => 'myTabTabs',
			'tag' => 'ul'
		);

		$this->assertTag(
			$matcher,
			$html,
			'Verify that the html structure of the TabSet is correct'
		);
	}

	/**
	 * Tests the endTabSet method
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function testEndTabSet()
	{
		$this->assertEquals("\n</div>", JHtmlBootstrap::endTabSet());
	}

	/**
	 * Tests the addTab method.
	 *
	 * @return  void
	 *
	 * @since   3.6.0
	 */
	public function testAddTab()
	{
		// Must start a tabset first
		JHtmlBootstrap::startTabSet();

		// Add a tab
		$html = JHtmlBootstrap::addTab('myTab', 'myTabItem', 'myTitle');

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/js/bootstrap.min.js',
			$document->_scripts,
			'Verify that the alert method initialises Bootstrap as well'
		);

		$li = "<li class=\\\"\\\"><a href=\\\"#myTabItem\\\" data-toggle=\\\"tab\\\">myTitle<\/a><\/li>";
		$script = 'jQuery(function($){ $("#myTab a").click(function (e) {e.preventDefault();$(this).tab("show");});});';
		$script .= chr(13);
		$script .= 'jQuery(function($){ $("#myTabTabs").append($("' . $li . '")); });';

		$this->assertEquals(
			$document->_script['text/javascript'],
			$script,
			'Verify that the startTabSet script is initialised'
		);

		// Check the tab set's html structure
		$matcher = array(
			'id'         => 'myTabItem',
			'tag'        => 'div',
			'attributes' => array('class' => 'tab-pane')
		);

		$this->assertTag(
			$matcher,
			$html,
			'Verify that the html structure of the Tab is correct'
		);
	}

	/**
	 * Tests the endTab method
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function testEndTab()
	{
		$this->assertEquals("\n</div>", JHtmlBootstrap::endTabSet());
	}

	/**
	 * Tests the endPane method
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function testEndPane()
	{
		$this->assertEquals('</div>', JHtmlBootstrap::endPane());
	}

	/**
	 * Tests the endPanel method
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function testEndPanel()
	{
		$this->assertEquals('</div>', JHtmlBootstrap::endPanel());
	}

	/**
	 * Tests the loadCss method.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function testLoadCss()
	{
		// Initialise the Bootstrap JS framework
		JHtmlBootstrap::loadCss(true, 'rtl');

		// Get the document instance
		$document = JFactory::getDocument();

		$this->assertArrayHasKey(
			'/media/jui/css/bootstrap.min.css',
			$document->_styleSheets,
			'Verify that the base Bootstrap CSS is loaded'
		);

		$this->assertArrayHasKey(
			'/media/jui/css/bootstrap-rtl.css',
			$document->_styleSheets,
			'Verify that the RTL Bootstrap CSS is loaded'
		);
	}
}
