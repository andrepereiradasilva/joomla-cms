<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.logout
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Plugin class for logout redirect handling.
 *
 * @since  1.6
 */
class PlgSystemLogout extends JPlugin
{
	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  3.6
	 */
	protected $app;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Cookie hash.
	 *
	 * @var    string
	 * @since  3.6
	 */
	protected $hash = '';

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe -- event dispatcher.
	 * @param   object  $config    An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->hash = JApplicationHelper::getHash('PlgSystemLogout');

		if ($this->app->isSite() && $this->app->input->cookie->getString($this->hash))
		{
			// Destroy the cookie.
			$this->app->input->cookie->set($this->hash, '', 1, $this->app->get('cookie_path', '/'), $this->app->get('cookie_domain', ''));

			// Set the error handler for E_ALL to be the class handleError method.
			JError::setErrorHandling(E_ALL, 'callback', array('PlgSystemLogout', 'handleError'));
		}
	}

	/**
	 * Method to handle any logout logic and report back to the subject.
	 *
	 * @param   array  $user     Holds the user data.
	 * @param   array  $options  Array holding options (client, ...).
	 *
	 * @return  boolean  Always returns true.
	 *
	 * @since   1.6
	 */
	public function onUserLogout($user, $options = array())
	{
		if ($this->app->isSite())
		{
			// Create the cookie.
			$this->app->input->cookie->set($this->hash,	true, time() + 86400, $this->app->get('cookie_path', '/'), $this->app->get('cookie_domain', ''));
		}

		return true;
	}

	/**
	 * Method to handle an error condition.
	 *
	 * @param   Exception  &$error  The Exception object to be handled.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public static function handleError(&$error)
	{
		// Make sure the error is a 403 and we are in the frontend.
		if ($error->getCode() == 403 && $this->app->isSite())
		{
			// Redirect to the home page.
			$this->app->enqueueMessage(JText::_('PLG_SYSTEM_LOGOUT_REDIRECT'));
			$this->app->redirect('index.php');
		}
		else
		{
			// Render the custom error page.
			JError::customErrorPage($error);
		}
	}
}
