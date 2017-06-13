<?php
/**
 * @package	    Joomla.UnitTest
 * @subpackage  Media
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license	    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Test class for JHelperMedia.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Media
 * @since       3.2
 */
class JHelperMediaTest extends TestCaseDatabase
{
	/**
	 * Object under test
	 *
	 * @var    JHelperMedia
	 * @since  3.2
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->saveFactoryState();

		JFactory::$application = $this->getMockCmsApp();
		JFactory::$session     = $this->getMockSession();

		$this->object = new JHelperMedia;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function tearDown()
	{
		$this->restoreFactoryState();
		unset($this->object);
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

		$dataSet->addTable('jos_extensions', JPATH_TEST_DATABASE . '/jos_extensions.csv');

		return $dataSet;
	}

	/**
	 * isImage data
	 *
	 * @return  array
	 *
	 * @since   3.2
	 */
	public function isImageProvider()
	{
		return [
			['Image file' => 'mypicture.jpg', 1],
			['Invalid type' => 'mypicture.php', 0],
			['No extension' => 'mypicture', 0],
			['Empty string' => '', 0]
		];
	}

	/**
	 * Tests the isImage method
	 *
	 * @param   string  $fileName  The filename
	 * @param   string  $expected  Expected result
	 *
	 * @return  void
	 *
	 * @dataProvider  isImageProvider
	 * @since         3.2
	 * @covers        JHelperMedia::isImage
	 */
	public function testIsImage($fileName, $expected)
	{
		$isImage = $this->object->isImage($fileName);
		$this->assertEquals($isImage, $expected);
	}

	/**
	 * Tests the getTypeIcon method
	 *
	 * @return  void
	 *
	 * @since   3.2
	 * @covers  JHelperMedia::getTypeIcon
	 */
	public function testGetTypeIcon()
	{
		$name = JHelperMedia::getTypeIcon('myfile.pdf');
		$this->assertEquals($name, 'pdf');
	}

	/**
	 * Tests the countFiles method
	 *
	 * @return  void
	 *
	 * @since   3.2
	 * @covers  JHelperMedia::countFiles
	 */
	public function testCountFiles()
	{
		// If changes are made to the tests/unit/schema directory this test requires updates
		$countFiles = $this->object->countFiles(JPATH_TESTS . '/schema');
		$this->assertSame([4, 0], $countFiles);
	}

	/**
	 * canUpload data
	 *
	 * @return  array
	 *
	 * @since   3.2
	 */
	public function canUploadProvider()
	{
		return [
			['Valid image file' => ['name' => 'mypicture.jpg', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 8], true],
			['File too big' => ['name' => 'mypicture.jpg', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 10485770], false],
			['Not an image' => ['name' => 'mypicture.php', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 8], false],
			['Ends with .' => ['name' => 'mypicture.png.', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 8], false],
			['Name contains bad characters' => ['name' => 'my<body>picture.jpg', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 8], false],
			['Name contains bad extension' => ['name' => 'myscript.php.jpg', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 8], false],
			['Name contains a space' => ['name' => 'my script.php.jpg', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 8], false],
			['Empty name' => ['name' => '', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 8], false],
			['Unknown format' => ['name' => 'myfile.xyz', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 8], false],
			['File above php limit' => ['name' => 'mypicture.jpg', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 20485770], false],
			['File above max configured but below php limit' => ['name' => 'mypicture.jpg', 'type' => 'image/jpeg', 'tmp_name' => JPATH_TESTS . '/suites/libraries/joomla/image/stubs/koala.jpg', 'error' => 0, 'size' => 10685770], false],
		];
	}

	/**
	 * Tests the canUpload method
	 *
	 * @param   array    $file      File information
	 * @param   boolean  $expected  Expected result
	 *
	 * @return  void
	 *
	 * @dataProvider  canUploadProvider
	 * @since         3.2
	 * @covers        JHelperMedia::canUpload
	 */
	public function testCanUpload($file, $expected)
	{
	    $canUpload = $this->object->canUpload($file);
		$this->assertEquals($canUpload, $expected);
	}

	/**
	 * imageResize data
	 *
	 * @return  array
	 *
	 * @since   3.2
	 */
	public function imageResizeProvider()
	{
		return [
			['Bigger Height' => 300, 200, 150, [150, 100]],
			['Bigger Width' => 200, 300, 150, [100, 150]],
			['Square' => 300, 300, 150, [150, 150]],
			['0 Height' => 300, 0, 150, [150, 0]],
			['0 Width' => 0, 300, 150, [0, 150]],
			['0 Target' => 300, 200, 0, [0, 0]],
		];
	}

	/**
	 * Tests the imageResize method
	 *
	 * @param   string  $fileName  The filename
	 * @param   string  $expected  Expected result
	 *
	 * @return  void
	 *
	 * @dataProvider  imageResizeProvider
	 * @since         3.2
	 * @covers        JHelperMedia::imageResize
	 */
	public function testImageResize($width, $height, $target, $expected)
	{
		$newSize = JHelperMedia::imageResize($width, $height, $target);
		$this->assertEquals($newSize, $expected);
	}
}
