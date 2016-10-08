<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.Hit
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Hit plugin.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgContentHit extends JPlugin
{
	/**
	 * Application object
	 *
	 * @var    JApplicationCms
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * Plugin that adds an hit when visiting an item.
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   mixed    &$row     An object with a "text" property or the string to be cloaked.
	 * @param   mixed    &$params  Additional parameters. See {@see PlgContentHit()}.
	 * @param   integer  $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  boolean	True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onContentBeforeDisplay($context, &$row, &$params, $page = 0)
	{
		// Check if the id/context is available for hit.
		if (!$this->availableForHit($context, $row->id))
		{
			return false;
		}

		$options = array(
			'ajaxUrl' => JRoute::_('index.php?option=com_ajax&group=content&plugin=contentHit&format=json'),
			'context' => $context,
			'id'      => $row->id,
			'token'   => JSession::getFormToken(),
		);

		JHtml::_('behavior.core');

		// Include core and polyfill for browsers lower than IE 9.
		JHtml::_('behavior.polyfill', 'event', 'lt IE 9');

		$this->app->getDocument()->addScriptOptions('plg_content_hit', $options);
		JHtml::_('script', 'plg_content_hit/hit.js', false, true);

		return true;
	}

	/**
	 * Receive Ajax Call
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAjaxContentHit()
	{
		// Send json mime type.
		$this->app->mimeType = 'application/json';
		$this->app->setHeader('Content-Type', $this->app->mimeType . '; charset=' . $this->app->charSet);
		$this->app->sendHeaders();

		$process = true;

		// Check if user token is valid.
		if (!JSession::checkToken('post'))
		{
			$this->app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			$process = false;
		}

		// Check if is a ajax request.
		if (strtolower($this->app->input->server->get('HTTP_X_REQUESTED_WITH', '')) !== 'xmlhttprequest')
		{
			// Do nothing.
			$process = false;
		}

		// Hit it!
		if ($process && $model = $this->availableForHit($this->app->input->get('context', '', 'string'), $id))
		{
			try
			{
				$model->hit((int) $id);
			}
			catch (Exception $e)
			{
				// Do nothing.
			}
		}

		echo new JResponseJson();
		$this->app->close();
	}

	/**
	 * Check if the id/context is available for hit.
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   string   $id       The item id.
	 *
	 * @return  boolean|JModelLegacy  False on not available, the model on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function availableForHit($context = '', $id = null)
	{
		if (!$context || !$id)
		{
			return false;
		}

		$parts          = explode('.', $context);
		$componentName  = ucfirst(substr($parts[0], 4));
		$itemName       = ucfirst($parts[1]);
		$modelClassName = $componentName . 'Model' . $itemName;

		// Register the model file.
		JLoader::register($modelClassName, JPATH_ROOT . '/components/' . $parts[0] . '/models/' . $parts[1] . '.php');

		// Check if model can be loaded and hit method executable.
		if (!class_exists($modelClassName) || !is_callable(array($modelClassName, 'hit')))
		{
			return false;
		}

		// Load the model.
		try
		{
			$model = JModelLegacy::getInstance($itemName, $componentName . 'Model', array('ignore_request' => true));
		}
		catch (Exception $e)
		{
			return false;
		}

		// Check if model is loaded and _context property exists.
		if (!$model || ($parts[1] !== 'category' && $model->get('_context') !== $context))
		{
			return false;
		}

		return $model;
	}
}
