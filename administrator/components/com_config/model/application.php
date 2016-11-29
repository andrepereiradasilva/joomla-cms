<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_config
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Model for the global configuration
 *
 * @since  3.2
 */
class ConfigModelApplication extends ConfigModelForm
{
	/**
	 * Method to get a form object.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_config.application', 'application', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the configuration data.
	 *
	 * This method will load the global configuration data straight from
	 * JConfig. If configuration data has been saved in the session, that
	 * data will be merged into the original data, overwriting it.
	 *
	 * @return	array  An array containg all global config data.
	 *
	 * @since	1.6
	 */
	public function getData()
	{
		// Get the config data.
		$config = new JConfig;
		$data   = ArrayHelper::fromObject($config);

		// Prime the asset_id for the rules.
		$data['asset_id'] = 1;

		// Get the text filter data
		$params          = JComponentHelper::getParams('com_config');
		$data['filters'] = ArrayHelper::fromObject($params->get('filters'));

		// If no filter data found, get from com_content (update of 1.6/1.7 site)
		if (empty($data['filters']))
		{
			$contentParams = JComponentHelper::getParams('com_content');
			$data['filters'] = ArrayHelper::fromObject($contentParams->get('filters'));
		}

		// Check for data in the session.
		$temp = JFactory::getApplication()->getUserState('com_config.config.global.data');

		// Merge in the session data.
		if (!empty($temp))
		{
			$data = array_merge($data, $temp);
		}

		return $data;
	}

	/**
	 * Method to save the configuration data.
	 *
	 * @param   array  $data  An array containing all global config data.
	 *
	 * @return	boolean  True on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function save($data)
	{
		$app = JFactory::getApplication();

		// Check that we aren't setting wrong database configuration
		$options = array(
			'driver'   => $data['dbtype'],
			'host'     => $data['host'],
			'user'     => $data['user'],
			'password' => JFactory::getConfig()->get('password'),
			'database' => $data['db'],
			'prefix'   => $data['dbprefix']
		);

		try
		{
			$dbc = JDatabaseDriver::getInstance($options)->getVersion();
		}
		catch (Exception $e)
		{
			$app->enqueueMessage(JText::_('JLIB_DATABASE_ERROR_DATABASE_CONNECT'), 'error');

			return false;
		}

		// Check if we can set the Force SSL option
		if ((int) $data['force_ssl'] !== 0 && (int) $data['force_ssl'] !== (int) JFactory::getConfig()->get('force_ssl', '0'))
		{
			try
			{
				// Make an HTTPS request to check if the site is available in HTTPS.
				$host    = JUri::getInstance()->getHost();
				$options = new \Joomla\Registry\Registry;
				$options->set('userAgent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0');

				// Do not check for valid server certificate here, leave this to the user, moreover disable using a proxy if any is configured.
				$options->set('transport.curl',
					array(
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_SSL_VERIFYHOST => false,
						CURLOPT_PROXY => null,
						CURLOPT_PROXYUSERPWD => null,
					)
				);
				$response = JHttpFactory::getHttp($options)->get('https://' . $host . JUri::root(true) . '/', array('Host' => $host), 10);

				// If available in HTTPS check also the status code.
				if (!in_array($response->code, array(200, 503, 301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 401), true))
				{
					throw new RuntimeException(JText::_('COM_CONFIG_ERROR_SSL_NOT_AVAILABLE_HTTP_CODE'));
				}
			}
			catch (RuntimeException $e)
			{
				$data['force_ssl'] = 0;

				// Also update the user state
				$app->setUserState('com_config.config.global.data.force_ssl', 0);

				// Inform the user
				$app->enqueueMessage(JText::sprintf('COM_CONFIG_ERROR_SSL_NOT_AVAILABLE', $e->getMessage()), 'warning');
			}
		}

		// Save the rules
		if (isset($data['rules']))
		{
			$rules = new JAccessRules($data['rules']);

			// Check that we aren't removing our Super User permission
			// Need to get groups from database, since they might have changed
			$myGroups      = JAccess::getGroupsByUser(JFactory::getUser()->get('id'));
			$myRules       = $rules->getData();
			$hasSuperAdmin = $myRules['core.admin']->allow($myGroups);

			if (!$hasSuperAdmin)
			{
				$app->enqueueMessage(JText::_('COM_CONFIG_ERROR_REMOVING_SUPER_ADMIN'), 'error');

				return false;
			}

			$asset = JTable::getInstance('asset');

			if ($asset->loadByName('root.1'))
			{
				$asset->rules = (string) $rules;

				if (!$asset->check() || !$asset->store())
				{
					$app->enqueueMessage(JText::_('SOME_ERROR_CODE'), 'error');

					return;
				}
			}
			else
			{
				$app->enqueueMessage(JText::_('COM_CONFIG_ERROR_ROOT_ASSET_NOT_FOUND'), 'error');

				return false;
			}

			unset($data['rules']);
		}

		// Save the text filters
		if (isset($data['filters']))
		{
			$registry = new Registry(array('filters' => $data['filters']));

			$extension = JTable::getInstance('extension');

			// Get extension_id
			$extension_id = $extension->find(array('name' => 'com_config'));

			if ($extension->load((int) $extension_id))
			{
				$extension->params = (string) $registry;

				if (!$extension->check() || !$extension->store())
				{
					$app->enqueueMessage(JText::_('SOME_ERROR_CODE'), 'error');

					return;
				}
			}
			else
			{
				$app->enqueueMessage(JText::_('COM_CONFIG_ERROR_CONFIG_EXTENSION_NOT_FOUND'), 'error');

				return false;
			}

			unset($data['filters']);
		}

		// Get the previous configuration.
		$prev = new JConfig;
		$prev = ArrayHelper::fromObject($prev);

		// Merge the new data in. We do this to preserve values that were not in the form.
		$data = array_merge($prev, $data);

		/*
		 * Perform miscellaneous options based on configuration settings/changes.
		 */

		// Escape the offline message if present.
		if (isset($data['offline_message']))
		{
			$data['offline_message'] = JFilterOutput::ampReplace($data['offline_message']);
		}

		// Purge the database session table if we are changing to the database handler.
		if ($prev['session_handler'] != 'database' && $data['session_handler'] == 'database')
		{
			$table = JTable::getInstance('session');
			$table->purge(-1);
		}

		// Set the shared session configuration
		if (isset($data['shared_session']))
		{
			$currentShared = isset($prev['shared_session']) ? $prev['shared_session'] : '0';

			// Has the user enabled shared sessions?
			if ($data['shared_session'] == 1 && $currentShared == 0)
			{
				// Generate a random shared session name
				$data['session_name'] = JUserHelper::genRandomPassword(16);
			}

			// Has the user disabled shared sessions?
			if ($data['shared_session'] == 0 && $currentShared == 1)
			{
				// Remove the session name value
				unset($data['session_name']);
			}
		}

		if (empty($data['cache_handler']))
		{
			$data['caching'] = 0;
		}

		$path = JPATH_SITE . '/cache';

		// Give a warning if the cache-folder can not be opened
		if ($data['caching'] > 0 && $data['cache_handler'] == 'file' && @opendir($path) == false)
		{
			JLog::add(JText::sprintf('COM_CONFIG_ERROR_CACHE_PATH_NOTWRITABLE', $path), JLog::WARNING, 'jerror');
			$data['caching'] = 0;
		}

		// Clean the cache if disabled but previously enabled.
		if (!$data['caching'] && $prev['caching'])
		{
			$cache = JFactory::getCache();
			$cache->clean();
		}

		// Create the new configuration object.
		$config = new Registry($data);

		// Overwrite the old FTP credentials with the new ones.
		$temp = JFactory::getConfig();
		$temp->set('ftp_enable', $data['ftp_enable']);
		$temp->set('ftp_host', $data['ftp_host']);
		$temp->set('ftp_port', $data['ftp_port']);
		$temp->set('ftp_user', $data['ftp_user']);
		$temp->set('ftp_pass', $data['ftp_pass']);
		$temp->set('ftp_root', $data['ftp_root']);

		// Clear cache of com_config component.
		$this->cleanCache('_system', 0);
		$this->cleanCache('_system', 1);

		// Write the configuration file.
		return $this->writeConfigFile($config);
	}

	/**
	 * Method to unset the root_user value from configuration data.
	 *
	 * This method will load the global configuration data straight from
	 * JConfig and remove the root_user value for security, then save the configuration.
	 *
	 * @return	boolean  True on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function removeroot()
	{
		// Get the previous configuration.
		$prev = new JConfig;
		$prev = ArrayHelper::fromObject($prev);

		// Create the new configuration object, and unset the root_user property
		unset($prev['root_user']);
		$config = new Registry($prev);

		// Write the configuration file.
		return $this->writeConfigFile($config);
	}

	/**
	 * Method to write the configuration to a file.
	 *
	 * @param   Registry  $config  A Registry object containing all global config data.
	 *
	 * @return	boolean  True on success, false on failure.
	 *
	 * @since	2.5.4
	 * @throws  RuntimeException
	 */
	private function writeConfigFile(Registry $config)
	{
		jimport('joomla.filesystem.path');
		jimport('joomla.filesystem.file');

		// Set the configuration file path.
		$file = JPATH_CONFIGURATION . '/configuration.php';

		// Get the new FTP credentials.
		$ftp = JClientHelper::getCredentials('ftp', true);

		$app = JFactory::getApplication();

		// Attempt to make the file writeable if using FTP.
		if (!$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0644'))
		{
			$app->enqueueMessage(JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTWRITABLE'), 'notice');
		}

		// Attempt to write the configuration file as a PHP class named JConfig.
		$configuration = $config->toString('PHP', array('class' => 'JConfig', 'closingtag' => false));

		if (!JFile::write($file, $configuration))
		{
			throw new RuntimeException(JText::_('COM_CONFIG_ERROR_WRITE_FAILED'));
		}

		// Attempt to make the file unwriteable if using FTP.
		if (!$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0444'))
		{
			$app->enqueueMessage(JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTUNWRITABLE'), 'notice');
		}

		return true;
	}

	/**
	 * Method to store the permission values in the asset table.
	 *
	 * This method will get an array with permission key value pairs and transform it
	 * into json and update the asset table in the database.
	 *
	 * @param   string  $permission  Need an array with Permissions (component, rule, value and title)
	 *
	 * @return  array  A list of result data.
	 *
	 * @since   3.5
	 */
	public function storePermissions($data = null)
	{
		$app  = JFactory::getApplication();
		$user = JFactory::getUser();

		// Get data from input.
		if (is_null($data))
		{
			$data = array(
				'component' => $app->input->get('comp'),
				'action'    => $app->input->get('action'),
				'rule'      => $app->input->get('rule'),
				'value'     => $app->input->get('value'),
				'title'     => $app->input->get('title', '', 'RAW'),
			);
		}

		$assetName  = !$data['component'] ? 'root.1' : $data['component'];
		$action     = $data['action'];
		$groupId    = (int) $data['rule'];
		$permission = $data['value'];

		// We are creating a new item so we don't have an item id so don't allow.
		if (substr($data['component'], -6) === '.false')
		{
			$app->enqueueMessage(JText::_('JLIB_RULES_SAVE_BEFORE_CHANGE_PERMISSIONS'), 'error');

			return false;
		}

		// Check if the user is authorized to do this.
		if (!$user->authorise('core.admin', $assetName))
		{
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');

			return false;
		}

		// Check if changed group has Super User permissions.
		$isSuperUserGroupBefore = JAccess::checkGroup($groupId, 'core.admin');

		// Check if current user belongs to changed group.
		$currentUserBelongsToGroup = in_array($groupId, $user->groups) ? true : false;

		// Get current user groups tree.
		$currentUserGroupsTree = JAccess::getGroupsByUser($user->id, true);

		// Check if current user belongs to changed group.
		$currentUserSuperUser = $user->authorise('core.admin');

		// If user is not Super User cannot change the permissions of a group it belongs to.
		if (!$currentUserSuperUser && $currentUserBelongsToGroup)
		{
			$app->enqueueMessage(JText::_('JLIB_USER_ERROR_CANNOT_CHANGE_OWN_GROUPS'), 'error');

			return false;
		}

		// If user is not Super User cannot change the permissions of a group it belongs to.
		if (!$currentUserSuperUser && in_array($groupId, $currentUserGroupsTree))
		{
			$app->enqueueMessage(JText::_('JLIB_USER_ERROR_CANNOT_CHANGE_OWN_PARENT_GROUPS'), 'error');

			return false;
		}

		// If user is not Super User cannot change the permissions of a Super User Group.
		if (!$currentUserSuperUser && $isSuperUserGroupBefore && !$currentUserBelongsToGroup)
		{
			$app->enqueueMessage(JText::_('JLIB_USER_ERROR_CANNOT_CHANGE_SUPER_USER'), 'error');

			return false;
		}

		// If user is not Super User cannot change the Super User permissions in any group it belongs to.
		if ($isSuperUserGroupBefore && $currentUserBelongsToGroup && $action === 'core.admin')
		{
			$app->enqueueMessage(JText::_('JLIB_USER_ERROR_CANNOT_DEMOTE_SELF'), 'error');

			return false;
		}

		$asset = JTable::getInstance('Asset');
		$asset->load(array('name' => $assetName));

		// There is no asset in the database, inform the user to save before trying to change permissions.
		if (!$asset->id)
		{
			$app->enqueueMessage(JText::_('JLIB_RULES_SAVE_BEFORE_CHANGE_PERMISSIONS'), 'error');

			return false;
		}

		// Asset found, let's update it.

		// Get the current asset rules.
		$currentRules = new JAccessRules($asset->rules);

		// Replace the action in the rules.
		$currentRules->setAction($action, array($groupId => $permission));

		// set the new rules.
		$asset->rules = (string) $currentRules;

		// Create/Update the asset rules.
		if (!$asset->check() || !$asset->store())
		{
			$app->enqueueMessage($asset->getError(), 'error');

			return false;
		}

		/**
		 * When we reach this point the asset is saved (new or updated).
		 * We now need to send the new asset calculated permissions.
		 */
		try
		{
			// Get the group parent id of the current group.
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('parent_id'))
				->from($this->db->quoteName('#__usergroups'))
				->where($this->db->quoteName('id') . ' = ' . $groupId);

			$groupParentId = (int) $this->db->setQuery($query)->loadResult();

			// Count the number of child groups of the current group.
			$query->clear()
				->select('COUNT(' . $this->db->quoteName('id') . ')')
				->from($this->db->quoteName('#__usergroups'))
				->where($this->db->quoteName('parent_id') . ' = ' . $groupId);

			$totalChildGroups = (int) $this->db->setQuery($query)->loadResult();
		}
		catch (Exception $e)
		{
			$app->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		// Clear access statistics.
		JAccess::clearStatics();

		// After current group permission is changed we need to check again if the group has Super User permissions.
		$isSuperUserGroupAfter = JAccess::checkGroup($groupId, 'core.admin');

		// If removed or added super user from group, we need to refresh the page to recalculate all settings.
		if ($isSuperUserGroupBefore != $isSuperUserGroupAfter)
		{
			$app->enqueueMessage(JText::_('JLIB_RULES_NOTICE_RECALCULATE_GROUP_PERMISSIONS'), 'notice');
		}

		// If this group has child groups, we need to refresh the page to recalculate the child settings.
		if ($totalChildGroups > 0)
		{
			$app->enqueueMessage(JText::_('JLIB_RULES_NOTICE_RECALCULATE_GROUP_CHILDS_PERMISSIONS'), 'notice');
		}

		// Get the calculated permission data.
		$calculated = JAccess::getCalculatedPermission($asset->id, $asset->parent_id, $groupId, $groupParentId, $action);
		$locked     = $calculated['locked'] ? '<span class="icon-lock icon-white"></span>' : '';
		$allowed    = $calculated['allowed'] ? 'label-success' : 'label-important';

		return array(
			'class'  => 'label ' . $allowed,
			'text'   => $locked . $calculated['text'],
			'result' => true,
		);
	}

	/**
	 * Method to send a test mail which is called via an AJAX request
	 *
	 * @return boolean
	 *
	 * @since   3.5
	 * @throws Exception
	 */
	public function sendTestMail()
	{
		// Set the new values to test with the current settings
		$app = JFactory::getApplication();
		$input = $app->input;

		$app->set('smtpauth', $input->get('smtpauth'));
		$app->set('smtpuser', $input->get('smtpuser', '', 'STRING'));
		$app->set('smtppass', $input->get('smtppass', '', 'RAW'));
		$app->set('smtphost', $input->get('smtphost'));
		$app->set('smtpsecure', $input->get('smtpsecure'));
		$app->set('smtpport', $input->get('smtpport'));
		$app->set('mailfrom', $input->get('mailfrom', '', 'STRING'));
		$app->set('fromname', $input->get('fromname', '', 'STRING'));
		$app->set('mailer', $input->get('mailer'));
		$app->set('mailonline', $input->get('mailonline'));

		$mail = JFactory::getMailer();

		// Prepare email and send try to send it
		$mailSubject = JText::sprintf('COM_CONFIG_SENDMAIL_SUBJECT', $app->get('sitename'));
		$mailBody    = JText::sprintf('COM_CONFIG_SENDMAIL_BODY', JText::_('COM_CONFIG_SENDMAIL_METHOD_' . strtoupper($mail->Mailer)));

		if ($mail->sendMail($app->get('mailfrom'), $app->get('fromname'), $app->get('mailfrom'), $mailSubject, $mailBody) === true)
		{
			$methodName = JText::_('COM_CONFIG_SENDMAIL_METHOD_' . strtoupper($mail->Mailer));

			// If JMail send the mail using PHP Mail as fallback.
			if ($mail->Mailer != $app->get('mailer'))
			{
				$app->enqueueMessage(JText::sprintf('COM_CONFIG_SENDMAIL_SUCCESS_FALLBACK', $app->get('mailfrom'), $methodName), 'warning');
			}
			else
			{
				$app->enqueueMessage(JText::sprintf('COM_CONFIG_SENDMAIL_SUCCESS', $app->get('mailfrom'), $methodName), 'message');
			}

			return true;
		}

		$app->enqueueMessage(JText::_('COM_CONFIG_SENDMAIL_ERROR'), 'error');

		return false;
	}
}
