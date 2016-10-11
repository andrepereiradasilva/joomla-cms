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
	 * On after app dispatch.
	 *
	 * @return  boolean	True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAfterDispatch()
	{
		// We only add hits in the frontend.
		if (!$this->app->isSite())
		{
			return;
		}

		$id      = $this->app->input->get('id', null, 'int');
		$context = $this->app->input->get('option', '', 'string') . '.' . $this->app->input->get('view', '', 'string');

		// Check if the id/context is available for hit.
		if ($this->availableForHit($context, $id))
		{
			$this->addHitScripts($context, $id);
		}
	}


	function onContentPrepareForm($form, $data)
	{
		// Check we have a form.
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		$formFilePath = '';
		$formName     = $form->getName();
echo $formName;

		// Add hits option in backend filter forms.
		$filterFormNames = array(
			// Content
			'com_content.articles.filter',
			'com_content.featured.filter',
			'com_categories.categories.article.filter',
			// Contacts
			'com_contact.contacts.filter',
			'com_categories.categories.contact.filter',
			// News Feeds
			'com_newsfeeds.newsfeed.filter',
			'com_categories.categories.newsfeed.filter',
			// Banners
			'com_categories.categories.banners.filter',
			// Tags
			'com_tags.tags.filter',
			// Search
			//'com_search.searches.filter',
			// Redirect
			//'com_redirect.links.filter',
		);

		
		// Add to backend searchtools filters.
		if (in_array($formName, $filterFormNames))
		{
			if ($fullOrdering = $form->getField('fullordering', 'list', null))
			{
				$fullOrdering->addListOption('JGLOBAL_HITS_ASC', array('value' => 'a.hits ASC'));
				$fullOrdering->addListOption('JGLOBAL_HITS_DESC', array('value' => 'a.hits DESC'));
			}
		}
		// Config pages
		elseif ($formName === 'com_config.component')
		{
			$component = JFactory::getApplication()->input->get('component', '', 'string');

			// Content Component
			if ($component === 'com_content')
			{
				if ($filterField = $form->getField('filter_field', 'list_default_parameters', null))
				{
					$filterField->addListOption('JGLOBAL_HITS', array('value' => 'hits'));
				}

				if ($orderBySec = $form->getField('orderby_sec', 'shared', null))
				{
					$orderBySec->addListOption('JGLOBAL_MOST_HITS', array('value' => 'hits'));
					$orderBySec->addListOption('JGLOBAL_LEAST_HITS', array('value' => 'rhits'));
				}
			}

			$formName .= '.' . $component;
		}
		// Modules
		elseif ($formName === 'com_modules.module')
		{
			// Articles - Category
			if ($data->module === 'mod_articles_category')
			{
				if ($articleOrdering = $form->getField('article_ordering', 'params', null))
				{
					$articleOrdering->addListOption('MOD_ARTICLES_CATEGORY_OPTION_HITS_VALUE', array('value' => 'a.hits'));
				}

				$formName .= '.' . $data->module;
			}
			// Articles - News
			elseif ($data->module === 'mod_articles_news')
			{
				if ($ordering = $form->getField('ordering', 'params.basic', null))
				{
					$ordering->addListOption('JGLOBAL_HITS', array('value' => 'a.hits'));
				}

				// Add to direction showon value
				if ($showOn = $form->getFieldAttribute('direction', 'showon', null, 'params.basic'))
				{
					$form->setFieldAttribute('direction', 'showon', $showOn . ',a.hits', 'params.basic');
				}

				$formName .= '.' . $data->module;
			}
		}

		// Add extra fields if form exists.
		JForm::addFormPath(__DIR__ . '/forms');
		$form->loadFile($formName, false);

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

		$id = $this->app->input->get('id', null, 'int');

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
	 * Add the ajax hit scripts.
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   string   $id       The item id.
	 *
	 * @return  void.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function addHitScripts($context = '', $id = null)
	{
		$options = array(
			'ajaxUrl' => JRoute::_('index.php?option=com_ajax&group=content&plugin=contentHit&format=json'),
			'context' => $context,
			'id'      => $id,
			'token'   => JSession::getFormToken(),
		);

		JHtml::_('behavior.core');

		// Include core and polyfill for browsers lower than IE 9.
		JHtml::_('behavior.polyfill', 'event', 'lt IE 9');

		$this->app->getDocument()->addScriptOptions('plg_content_hit', $options);
		JHtml::_('script', 'plg_content_hit/hit.js', false, true);
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
