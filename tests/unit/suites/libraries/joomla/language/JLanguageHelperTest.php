<?php
/**
 * @package    Joomla.UnitTest
 *
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

/**
 * Test class for JLanguageHelper.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Language
 * @since       11.1
 */
class JLanguageHelperTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testCreateLanguageList()
	{
		$option = array(
			'text'     => 'English (United Kingdom)',
			'value'    => 'en-GB',
			'selected' => 'selected="selected"'
		);

		$listCompareEqual = array(
			0 => $option,
		);

		$list = JLanguageHelper::createLanguageList('en-GB', __DIR__ . '/data', false);
		$this->assertEquals(
			$listCompareEqual,
			$list
		);
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testDetectLanguage()
	{
		$lang = JLanguageHelper::detectLanguage();

		// Since we're running in a CLI context we can only check the defualt value
		$this->assertNull(
			$lang
		);
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testExists()
	{
		$this->assertFalse(
			JLanguageHelper::exists(null)
		);

		$basePath = __DIR__ . '/data';

		$this->assertTrue(
			JLanguageHelper::exists('en-GB', $basePath)
		);

		$this->assertFalse(
			JLanguageHelper::exists('es-ES', $basePath)
		);
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testGetMetadata()
	{
		// Language doesn't exist, retun NULL
		$this->assertNull(
			JLanguageHelper::getMetadata('es-ES')
		);

		$localeString = 'en_GB.utf8, en_GB.UTF-8, en_GB, eng_GB, en, english, english-uk, uk, gbr, britain, england, great britain, ' .
			'uk, united kingdom, united-kingdom';

		// In this case, returns array with default language
		// - same operation of get method with metadata property
		$options = array(
			'name' => 'English (en-GB)',
			'tag' => 'en-GB',
			'rtl' => '0',
			'locale' => $localeString,
			'firstDay' => '0',
			'weekEnd' => '0,6'
		);

		// Language exists, returns array with values
		$this->assertEquals(
			$options,
			JLanguageHelper::getMetadata('en-GB')
		);
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testGetKnownLanguages()
	{
		// This method returns a list of known languages
		$basePath = __DIR__ . '/data';
		$option1  = array(
			'name'     => 'English (United Kingdom)',
			'tag'      => 'en-GB',
			'rtl'      => '0',
			'locale'   => 'en_GB.utf8, en_GB.UTF-8, en_GB, eng_GB, en, english, english-uk, uk, gbr, britain, england, great britain,' .
			' uk, united kingdom, united-kingdom',
			'firstDay' => '0',
			'weekEnd'  => '0,6',
		);

		$listCompareEqual1 = array(
			'en-GB' => $option1,
		);

		$this->assertEquals(
			$listCompareEqual1,
			JLanguageHelper::getKnownLanguages($basePath),
			'Line: ' . __LINE__
		);
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testGetLanguagePath()
	{
		$basePath = 'test';

		// $language = null, returns language directory
		$this->assertEquals(
			'test/language',
			JLanguageHelper::getLanguagePath($basePath, null),
			'Line: ' . __LINE__
		);

		// $language = value (en-GB, for example), returns en-GB language directory
		$this->assertEquals(
			'test/language/en-GB',
			JLanguageHelper::getLanguagePath($basePath, 'en-GB'),
			'Line: ' . __LINE__
		);

		// With no argument JPATH_BASE should be returned
		$this->assertEquals(
			JPATH_BASE . '/language',
			JLanguageHelper::getLanguagePath(),
			'Line: ' . __LINE__
		);
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testParseLanguageFiles()
	{
		$dir    = __DIR__ . '/data/language';
		$option = array(
			'name'     => 'English (United Kingdom)',
			'tag'      => 'en-GB',
			'rtl'      => '0',
			'locale'   => 'en_GB.utf8, en_GB.UTF-8, en_GB, eng_GB, en, english, english-uk, uk, gbr, britain, england,' .
				' great britain, uk, united kingdom, united-kingdom',
			'firstDay' => '0',
			'weekEnd'  => '0,6',
		);

		$expected = array(
			'en-GB' => $option
		);

		$result = JLanguageHelper::parseLanguageFiles($dir);

		$this->assertEquals(
			$expected,
			$result,
			'Line: ' . __LINE__
		);
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testParseXMLLanguageFile()
	{
		$path   = __DIR__ . '/data/language/en-GB/en-GB.xml';
		$option = array(
			'name'     => 'English (United Kingdom)',
			'tag'      => 'en-GB',
			'rtl'      => '0',
			'locale'   => 'en_GB.utf8, en_GB.UTF-8, en_GB, eng_GB, en, english, english-uk, uk, gbr, britain, england, great britain,' .
				' uk, united kingdom, united-kingdom',
			'firstDay' => '0',
			'weekEnd'  => '0,6',
		);

		$this->assertEquals(
			$option,
			JLanguageHelper::parseXMLLanguageFile($path),
			'Line: ' . __LINE__
		);
	}

	/**
	 * Test...
	 *
	 * @expectedException  RuntimeException
	 *
	 * @return void
	 */
	public function testParseXMLLanguageFileException()
	{
		$path = __DIR__ . '/data/language/es-ES/es-ES.xml';

		JLanguageHelper::parseXMLLanguageFile($path);
	}
}
