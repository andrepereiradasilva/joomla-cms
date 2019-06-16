<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Input;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Filter\InputFilter;

/**
 * Joomla! Input Cookie Class
 *
 * @since       1.7.0
 * @deprecated  5.0  Use Joomla\Input\Cookie instead
 */
class Cookie extends Input
{
	/**
	 * Constructor.
	 *
	 * @param   array  $source   Ignored.
	 * @param   array  $options  Array of configuration parameters (Optional)
	 *
	 * @since   1.7.0
	 * @deprecated  5.0  Use Joomla\Input\Cookie instead
	 */
	public function __construct(array $source = null, array $options = array())
	{
		if (isset($options['filter']))
		{
			$this->filter = $options['filter'];
		}
		else
		{
			$this->filter = InputFilter::getInstance();
		}

		// Set the data source.
		$this->data = & $_COOKIE;

		// Set the options for the class.
		$this->options = $options;
	}

	/**
	 * Sets a value
	 *
	 * @param   string   $name      Name of the value to set.
	 * @param   mixed    $value     Value to assign to the input.
	 * @param   array    $options   An associative array which may have any of the keys expires, path, domain,
	 *                              secure, httponly and samesite. The values have the same meaning as described
	 *                              for the parameters with the same name. The value of the samesite element
	 *                              should be either Lax or Strict. If any of the allowed options are not given,
	 *                              their default values are the same as the default values of the explicit
	 *                              parameters. If the samesite element is omitted, no SameSite cookie attribute
	 *                              is set.
	 *
	 * @return  void
	 *
	 * @link    https://www.ietf.org/rfc/rfc2109.txt
	 * @link    https://php.net/manual/en/function.setcookie.php
	 *
	 * @since   1.7.0
	 * @deprecated  5.0  Use Joomla\Input\Cookie instead
	 *
	 * @note    As of __DEPLOY_VERSION__, the (name, value, expire, path, domain, secure, httpOnly) signature is deprecated and will not be supported
	 *          when support for PHP 7.2 and earlier is dropped
	 */
	public function set($name, $value, $options = array())
	{
		// BC layer to convert old method parameters.
		if (is_array($options) === false)
		{
			$argList = func_get_args();

			$options = array(
				'expires'  => isset($argList[2]) === true ? $argList[2] : 0,
				'path'     => isset($argList[3]) === true ? $argList[3] : '',
				'domain'   => isset($argList[4]) === true ? $argList[4] : '',
				'secure'   => isset($argList[5]) === true ? $argList[5] : false,
				'httponly' => isset($argList[6]) === true ? $argList[6] : false,
			);
		}

		// Using the setcookie function before php 7.3, make sure we have default values.
		if (version_compare(PHP_VERSION, '7.3', '<'))
		{
			if (array_key_exists('expires', $options) === false)
			{
				$options['expires'] = 0;
			}

			if (array_key_exists('path', $options) === false)
			{
				$options['path'] = '';
			}

			if (array_key_exists('domain', $options) === false)
			{
				$options['domain'] = '';
			}

			if (array_key_exists('secure', $options) === false)
			{
				$options['secure'] = false;
			}

			if (array_key_exists('httponly', $options) === false)
			{
				$options['httponly'] = false;
			}
		}
	
		if (is_array($value))
		{
			foreach ($value as $key => $val)
			{
				// Set the cookie
				if (version_compare(PHP_VERSION, '7.3', '>='))
				{
					setcookie($name . "[$key]", $val, $options);
				}
				else
				{
					setcookie($name . "[$key]", $val, $options['expires'], $options['path'], $options['domain'], $options['secure'], $options['httponly']);
				}
			}
		}
		else
		{
			// Set the cookie
			if (version_compare(PHP_VERSION, '7.3', '>='))
			{
				setcookie($name, $value, $options);
			}
			else
			{
				setcookie($name, $value, $options['expires'], $options['path'], $options['domain'], $options['secure'], $options['httponly']);
			}
		}

		$this->data[$name] = $value;
	}
}
