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
	 * Plugin that adds an hit whne visiting an item.
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   mixed    &$row     An object with a "text" property or the string to be cloaked.
	 * @param   mixed    &$params  Additional parameters. See {@see PlgContentEmailcloak()}.
	 * @param   integer  $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  boolean	True on success.
	 */
	public function onContentBeforeDisplay($context, &$row, &$params, $page = 0)
	{
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
	 * Receive AJAX Call
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

		// Check if user token is valid.
		if (!JSession::checkToken('post'))
		{
			$this->app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			echo new JResponseJson();
			$this->app->close();
		}

		// Check if is a ajax request.
		if (strtolower($this->app->input->server->get('HTTP_X_REQUESTED_WITH', '')) === 'xmlhttprequest')
		{
			// Do nothing.
			echo new JResponseJson();
			$this->app->close();
		}

		$context = $this->app->input->get('context', '', 'string');
		$id      = $this->app->input->get('id', null, 'int');

		if (!$context || !$id)
		{
			// Do nothing.
			echo new JResponseJson();
			$this->app->close();
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
			// Do nothing.
			echo new JResponseJson();
			$this->app->close();
		}

		// Load the model.
		$model = JModelLegacy::getInstance($itemName, $componentName . 'Model', array('ignore_request' => true));

		// Check if model is loaded and _context property exists.
		if (!$model || $model->get('_context') !== $context)
		{
			// Do nothing.
			echo new JResponseJson();
			$this->app->close();
		}

		// Set the hit counter
		$hitcount = $this->app->input->set('hitcount', 1);

		// Hit it!
		try
		{
			$model->hit((int) $id);
		}
		catch (Exception $e)
		{
			// Do nothing.
		}

		echo new JResponseJson();
		$this->app->close();
	}
}
