<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.languagefilter
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

JLoader::register('MenusHelper', JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');

/**
 * Joomla! Language Filter Plugin.
 *
 * @since  1.6
 */
class PlgSystemLanguageFilter extends JPlugin
{
	/**
	 * The routing mode.
	 *
	 * @var    boolean
	 * @since  2.5
	 */
	protected $mode_sef;

	/**
	 * Available languages by sef.
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $sefs;

	/**
	 * Available languages by language codes.
	 *
	 * @var    array
	 * @since  2.5
	 */
	protected $lang_codes;

	/**
	 * The current language code.
	 *
	 * @var    string
	 * @since  3.4.2
	 */
	protected $current_lang;

	/**
	 * The default language code.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $default_lang;

	/**
	 * The logged user language code.
	 *
	 * @var    string
	 * @since  3.3.1
	 */
	private $user_lang_code;

	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  3.3
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		if ($this->app->isSite())
		{
			// Setup language data.
			$this->mode_sef     = $this->app->get('sef', 0);
			$this->sefs         = JLanguageHelper::getAvailableSiteLanguages('sef');
			$this->lang_codes   = JLanguageHelper::getAvailableSiteLanguages('lang_code');
			$this->default_lang = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		}
	}

	/**
	 * After initialise.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onAfterInitialise()
	{
		$this->app->item_associations = $this->params->get('item_associations', 0);

		if ($this->app->isSite())
		{
			$router = $this->app->getRouter();

			// Attach build rules for language SEF.
			$router->attachBuildRule(array($this, 'preprocessBuildRule'), JRouter::PROCESS_BEFORE);
			$router->attachBuildRule(array($this, 'buildRule'), JRouter::PROCESS_DURING);

			if ($this->mode_sef)
			{
				$router->attachBuildRule(array($this, 'postprocessSEFBuildRule'), JRouter::PROCESS_AFTER);
			}
			else
			{
				$router->attachBuildRule(array($this, 'postprocessNonSEFBuildRule'), JRouter::PROCESS_AFTER);
			}

			// Attach parse rules for language SEF.
			$router->attachParseRule(array($this, 'parseRule'), JRouter::PROCESS_DURING);
		}
	}

	/**
	 * After route.
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	public function onAfterRoute()
	{
		// Add custom site name.
		if (isset($this->lang_codes[$this->current_lang]) && $this->lang_codes[$this->current_lang]->sitename)
		{
			$this->app->set('sitename', $this->lang_codes[$this->current_lang]->sitename);
		}
	}

	/**
	 * Add build preprocess rule to router.
	 *
	 * @param   JRouter  &$router  JRouter object.
	 * @param   JUri     &$uri     JUri object.
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	public function preprocessBuildRule(&$router, &$uri)
	{
		$lang = $uri->getVar('lang', $this->current_lang);
		$uri->setVar('lang', $lang);

		if (isset($this->sefs[$lang]))
		{
			$lang = $this->sefs[$lang]->lang_code;
			$uri->setVar('lang', $lang);
		}
	}

	/**
	 * Add build rule to router.
	 *
	 * @param   JRouter  &$router  JRouter object.
	 * @param   JUri     &$uri     JUri object.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function buildRule(&$router, &$uri)
	{
		$lang = $uri->getVar('lang');

		if (isset($this->lang_codes[$lang]))
		{
			$sef = $this->lang_codes[$lang]->sef;
		}
		else
		{
			$sef = $this->lang_codes[$this->current_lang]->sef;
		}

		if ($this->mode_sef
			&& (!$this->params->get('remove_default_prefix', 0)
			|| $lang != $this->default_lang
			|| $lang != $this->current_lang))
		{
			$uri->setPath($uri->getPath() . '/' . $sef . '/');
		}
	}

	/**
	 * postprocess build rule for SEF URLs
	 *
	 * @param   JRouter  &$router  JRouter object.
	 * @param   JUri     &$uri     JUri object.
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	public function postprocessSEFBuildRule(&$router, &$uri)
	{
		$uri->delVar('lang');
	}

	/**
	 * postprocess build rule for non-SEF URLs
	 *
	 * @param   JRouter  &$router  JRouter object.
	 * @param   JUri     &$uri     JUri object.
	 *
	 * @return  void
	 *
	 * @since   3.4
	 */
	public function postprocessNonSEFBuildRule(&$router, &$uri)
	{
		$lang = $uri->getVar('lang');

		if (isset($this->lang_codes[$lang]))
		{
			$uri->setVar('lang', $this->lang_codes[$lang]->sef);
		}
	}

	/**
	 * Add parse rule to router.
	 *
	 * @param   JRouter  &$router  JRouter object.
	 * @param   JUri     &$uri     JUri object.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function parseRule(&$router, &$uri)
	{
		// Did we find the current and existing language yet?
		$found = false;
		$lang_code = false;

		// Are we in SEF mode or not?
		if ($this->mode_sef)
		{
			$path = $uri->getPath();
			$parts = explode('/', $path);

			$sef = $parts[0];

			// Do we have a URL Language Code ?
			if (!isset($this->sefs[$sef]))
			{
				// Check if remove default url language code is set
				if ($this->params->get('remove_default_prefix', 0))
				{
					if ($parts[0])
					{
						// We load a default site language page
						$lang_code = $this->default_lang;
					}
					else
					{
						// We check for an existing language cookie
						$lang_code = $this->getLanguageCookie();
					}
				}
				else
				{
					$lang_code = $this->getLanguageCookie();
				}

				// No language code. Try using browser settings or default site language
				if (!$lang_code && $this->params->get('detect_browser', 0) == 1)
				{
					$lang_code = JLanguageHelper::detectLanguage();
				}

				if (!$lang_code)
				{
					$lang_code = $this->default_lang;
				}

				if ($this->params->get('remove_default_prefix', 0) && $lang_code == $this->default_lang)
				{
					$found = true;
				}
			}
			else
			{
				// We found our language
				$found = true;
				$lang_code = $this->sefs[$sef]->lang_code;

				// If we found our language, but its the default language and we don't want a prefix for that, we are on a wrong URL.
				// Or we try to change the language back to the default language. We need a redirect to the proper URL for the default language.
				if ($this->params->get('remove_default_prefix', 0)
					&& $lang_code == $this->default_lang)
				{
					// Create a cookie.
					$this->setLanguageCookie($lang_code);

					$found = false;
					array_shift($parts);
					$path = implode('/', $parts);
				}

				// We have found our language and the first part of our URL is the language prefix
				if ($found)
				{
					array_shift($parts);
					$uri->setPath(implode('/', $parts));
				}
			}
		}
		// We are not in SEF mode
		else
		{
			$lang_code = $this->getLanguageCookie();

			if ($this->params->get('detect_browser', 1) && !$lang_code)
			{
				$lang_code = JLanguageHelper::detectLanguage();
			}

			if (!isset($this->lang_codes[$lang_code]))
			{
				$lang_code = $this->default_lang;
			}
		}

		$lang = $uri->getVar('lang', $lang_code);

		if (isset($this->sefs[$lang]))
		{
			// We found our language
			$found = true;
			$lang_code = $this->sefs[$lang]->lang_code;
		}

		// We are called via POST. We don't care about the language
		// and simply set the default language as our current language.
		if ($this->app->input->getMethod() == "POST"
			|| count($this->app->input->post) > 0
			|| count($this->app->input->files) > 0)
		{
			$found = true;

			if (!isset($lang_code))
			{
				$lang_code = $this->getLanguageCookie();
			}

			if ($this->params->get('detect_browser', 1) && !$lang_code)
			{
				$lang_code = JLanguageHelper::detectLanguage();
			}

			if (!isset($this->lang_codes[$lang_code]))
			{
				$lang_code = $this->default_lang;
			}
		}

		// We have not found the language and thus need to redirect
		if (!$found)
		{
			// Lets find the default language for this user
			if (!isset($lang_code) || !isset($this->lang_codes[$lang_code]))
			{
				$lang_code = false;

				if ($this->params->get('detect_browser', 1))
				{
					$lang_code = JLanguageHelper::detectLanguage();

					if (!isset($this->lang_codes[$lang_code]))
					{
						$lang_code = false;
					}
				}

				if (!$lang_code)
				{
					$lang_code = $this->default_lang;
				}
			}

			if ($this->mode_sef)
			{
				// Use the current language sef or the default one.
				if (!$this->params->get('remove_default_prefix', 0)
					|| $lang_code != $this->default_lang)
				{
					$path = $this->lang_codes[$lang_code]->sef . '/' . $path;
				}

				$uri->setPath($path);

				if (!$this->app->get('sef_rewrite'))
				{
					$uri->setPath('index.php/' . $uri->getPath());
				}

				$redirectUri = $uri->base() . $uri->toString(array('path', 'query', 'fragment'));
			}
			else
			{
				$uri->setVar('lang', $this->lang_codes[$lang_code]->sef);
				$redirectUri = $uri->base() . 'index.php?' . $uri->getQuery();
			}

			// Set redirect HTTP code to "302 Found".
			$redirectHttpCode = 302;

			// If selected language is the default language redirect code is "301 Moved Permanently".
			if ($lang_code === $this->default_lang)
			{
				$redirectHttpCode = 301;

				// We cannot cache this redirect in browser. 301 is cachable by default so we need to force to not cache it in browsers.
				$this->app->setHeader('Expires', 'Wed, 17 Aug 2005 00:00:00 GMT', true);
				$this->app->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT', true);
				$this->app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);
				$this->app->setHeader('Pragma', 'no-cache');
				$this->app->sendHeaders();
			}

			// Redirect to language.
			$this->app->redirect($redirectUri, $redirectHttpCode);
		}

		// We have found our language and now need to set the cookie and the language value in our system
		$array = array('lang' => $lang_code);
		$this->current_lang = $lang_code;

		// Set the request var.
		$this->app->input->set('language', $lang_code);
		$this->app->set('language', $lang_code);
		$language = JFactory::getLanguage();

		if ($language->getTag() != $lang_code)
		{
			$newLang = JLanguage::getInstance($lang_code);

			foreach ($language->getPaths() as $extension => $files)
			{
				$newLang->load($extension);
			}

			JFactory::$language = $newLang;
			$this->app->loadLanguage($newLang);
		}

		// Create a cookie.
		if ($this->getLanguageCookie() != $lang_code)
		{
			$this->setLanguageCookie($lang_code);
		}

		return $array;
	}

	/**
	 * Before store user method.
	 *
	 * Method is called before user data is stored in the database.
	 *
	 * @param   array    $user   Holds the old user data.
	 * @param   boolean  $isnew  True if a new user is stored.
	 * @param   array    $new    Holds the new user data.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onUserBeforeSave($user, $isnew, $new)
	{
		if ($this->params->get('automatic_change', '1') == '1' && key_exists('params', $user))
		{
			$registry = new Registry;
			$registry->loadString($user['params']);
			$this->user_lang_code = $registry->get('language');

			if (empty($this->user_lang_code))
			{
				$this->user_lang_code = $this->current_lang;
			}
		}
	}

	/**
	 * After store user method.
	 *
	 * Method is called after user data is stored in the database.
	 *
	 * @param   array    $user     Holds the new user data.
	 * @param   boolean  $isnew    True if a new user is stored.
	 * @param   boolean  $success  True if user was succesfully stored in the database.
	 * @param   string   $msg      Message.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onUserAfterSave($user, $isnew, $success, $msg)
	{
		if ($this->params->get('automatic_change', '1') == '1' && key_exists('params', $user) && $success)
		{
			$registry = new Registry;
			$registry->loadString($user['params']);
			$lang_code = $registry->get('language');

			if (empty($lang_code))
			{
				$lang_code = $this->current_lang;
			}

			if ($lang_code == $this->user_lang_code || !isset($this->lang_codes[$lang_code]))
			{
				if ($this->app->isSite())
				{
					$this->app->setUserState('com_users.edit.profile.redirect', null);
				}
			}
			else
			{
				if ($this->app->isSite())
				{
					$this->app->setUserState('com_users.edit.profile.redirect', 'index.php?Itemid='
						. $this->app->getMenu()->getDefault($lang_code)->id . '&lang=' . $this->lang_codes[$lang_code]->sef
					);

					// Create a cookie.
					$this->setLanguageCookie($lang_code);
				}
			}
		}
	}

	/**
	 * Method to handle any login logic and report back to the subject.
	 *
	 * @param   array  $user     Holds the user data.
	 * @param   array  $options  Array holding options (remember, autoregister, group).
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.5
	 */
	public function onUserLogin($user, $options = array())
	{
		$menu = $this->app->getMenu();

		if ($this->app->isSite())
		{
			if ($this->params->get('automatic_change', 1))
			{
				$assoc = JLanguageAssociations::isEnabled();
				$lang_code = $user['language'];

				// If no language is specified for this user, we set it to the site default language
				if (empty($lang_code))
				{
					$lang_code = $this->default_lang;
				}

				jimport('joomla.filesystem.folder');

				// The language has been deleted/disabled or the related content language does not exist/has been unpublished
				// or the related home page does not exist/has been unpublished
				if (!array_key_exists($lang_code, $this->lang_codes)
					|| !array_key_exists($lang_code, JLanguageMultilang::getSiteHomePages())
					|| !JFolder::exists(JPATH_SITE . '/language/' . $lang_code))
				{
					$lang_code = $this->current_lang;
				}

				// Try to get association from the current active menu item
				$active = $menu->getActive();

				$foundAssociation = false;

				if ($active)
				{
					if ($assoc)
					{
						$associations = MenusHelper::getAssociations($active->id);
					}

					// The login menu item contains a redirection.
					// This will override the automatic change to the user preferred language
					if ($active->params['login_redirect_url'])
					{
						$this->app->setUserState('users.login.form.return', JRoute::_($this->app->getUserState('users.login.form.return'), false));
					}
					elseif ($this->app->getUserState('users.login.form.return'))
					{
						// The login module contains a menu item redirection. Try to get association from that menu item.
						$itemid = preg_replace('/\D+/', '', $this->app->getUserState('users.login.form.return'));

						if ($assoc)
						{
							$associations = MenusHelper::getAssociations($itemid);
						}

						if (isset($associations[$lang_code]) && $menu->getItem($associations[$lang_code]))
						{
							$associationItemid = $associations[$lang_code];
							$this->app->setUserState('users.login.form.return', 'index.php?Itemid=' . $associationItemid);
							$foundAssociation = true;
						}
					}
					elseif (isset($associations[$lang_code]) && $menu->getItem($associations[$lang_code]))
					{
						$associationItemid = $associations[$lang_code];
						$this->app->setUserState('users.login.form.return', 'index.php?Itemid=' . $associationItemid);
						$foundAssociation = true;
					}
					elseif ($active->home)
					{
						// We are on a Home page, we redirect to the user site language home page
						$item = $menu->getDefault($lang_code);

						if ($item && $item->language != $active->language && $item->language != '*')
						{
							$this->app->setUserState('users.login.form.return', 'index.php?Itemid=' . $item->id);
							$foundAssociation = true;
						}
					}
				}

				if ($foundAssociation && $lang_code != $this->current_lang)
				{
					// Change language.
					$this->current_lang = $lang_code;

					// Create a cookie.
					$this->setLanguageCookie($lang_code);

					// Change the language code.
					JFactory::getLanguage()->setLanguage($lang_code);
				}
			}
			else
			{
				if ($this->app->getUserState('users.login.form.return'))
				{
					$this->app->setUserState('users.login.form.return', JRoute::_($this->app->getUserState('users.login.form.return'), false));
				}
			}
		}
	}

	/**
	 * Method to add alternative meta tags for associated menu items.
	 *
	 * @return  void
	 *
	 * @since   1.7
	 */
	public function onAfterDispatch()
	{
		$doc = $this->app->getDocument();

		if ($this->app->isSite() && $this->params->get('alternate_meta', 1) && $doc->getType() == 'html')
		{
			$languages             = $this->lang_codes;
			$menu                  = $this->app->getMenu();
			$currentParameters     = $this->app->getRouter()->getVars();
			$currentInternalUrl    = 'index.php?' . http_build_query($currentParameters);
			$active                = $menu->getActive();
			$isHome                = $active && $active->home && JRoute::_($active->link . '&Itemid=' . $active->id) == JRoute::_($currentInternalUrl);
			$currentLanguage       = JFactory::getLanguage();
			$currentLanguageCode   = $currentLanguage->getTag();
			$menuItemAssociations  = array();
			$componentAssociations = array();

			// If in a menu item, check if we are on home item and, if not, get the menu associations.
			if (!$isHome && $active)
			{
				$menuItemAssociations = MenusHelper::getAssociations($active->id);
			}

			// If not in home, load component associations.
			if (!$isHome)
			{
				$option     = strtolower($this->app->input->get('option', '', 'string'));
				$helperFile = JPATH_ROOT . '/components/' . $option . '/helpers/association.php';

				if (file_exists($helperFile))
				{
					$componentClass = ucfirst(str_replace('com_', '', $option)) . 'HelperAssociation';
					JLoader::register($componentClass, JPath::clean($helperFile));

					if (class_exists($componentClass) && is_callable(array($componentClass, 'getAssociations')))
					{
						$componentAssociations = call_user_func(array($componentClass, 'getAssociations'));
					}
				}
			}

			// Fetch the association link for each available site content languages.
			foreach ($languages as $i => $language)
			{
				$language->active = $language->lang_code === $currentLanguageCode;

				switch (true)
				{
					// Language home page, the association is the other language home page.
					case ($isHome):
						$language->link = JRoute::_('index.php?Itemid=' . $language->home_id . '&lang=' . $language->sef);
						break;

					// If current language use the current url.
					case ($language->active):
						$language->link = JRoute::_($currentInternalUrl);
						break;

					// A component item association exists. Use it.
					case (isset($componentAssociations[$i])):
						$language->link = JRoute::_($componentAssociations[$i] . '&lang=' . $language->sef);
						break;

					// A menu item association exists. Use it.
					case (isset($menuItemAssociations[$i]) && ($item = $menu->getItem($menuItemAssociations[$i]))):
						$language->link = JRoute::_($item->link . '&Itemid=' . $item->id . '&lang=' . $language->sef);
						break;

					// If current URI is a component without menu item (no active menu, ex: /en/component/content/).
					case (!isset($active)):
						$urlParameters  = array_replace($currentParameters, array('lang' => $language->sef));
						$language->link = JRoute::_('index.php?' . http_build_query($urlParameters));
						break;

					// No association, no meta tag. Discard the language.
					default:
						unset($languages[$i]);
				}
			}

			// If there are at least 2 associations, add the rel="alternate" links to the <head>
			if (count($languages) > 1)
			{
				$server = JUri::getInstance()->toString(array('scheme', 'host', 'port'));

				// Remove the sef from the default language if "Remove URL Language Code" is on
				if (isset($languages[$this->default_lang]) && $this->params->get('remove_default_prefix', 0))
				{
					$languages[$this->default_lang]->link
									= preg_replace('#^/' . $languages[$this->default_lang]->sef . '/#', '/', $languages[$this->default_lang]->link, 1);
				}

				foreach ($languages as $i => &$language)
				{
					$doc->addHeadLink($server . $language->link, 'alternate', 'rel', array('hreflang' => $i));
				}

				// Add x-default language tag
				if ($this->params->get('xdefault', 1))
				{
					$xdefaultLanguageCode = $this->params->get('xdefault_language', $this->default_lang);
					$xdefaultLanguageCode = $xdefaultLanguageCode === 'default' ? $this->default_lang : $xdefaultLanguageCode;

					if (isset($languages[$xdefaultLanguageCode]))
					{
						// Use a custom tag because addHeadLink is limited to one URI per tag
						$doc->addCustomTag('<link href="' . $server . $languages[$xdefaultLanguageCode]->link . '" rel="alternate" hreflang="x-default" />');
					}
				}
			}
		}
	}

	/**
	 * Set the language cookie
	 *
	 * @param   string  $lang_code  The language code for which we want to set the cookie
	 *
	 * @return  void
	 *
	 * @since   3.4.2
	 */
	private function setLanguageCookie($lang_code)
	{
		// Get the cookie lifetime we want.
		$cookie_expire = 0;

		if ($this->params->get('lang_cookie', 1) == 1)
		{
			$cookie_expire = time() + 365 * 86400;
		}

		// Create a cookie.
		$cookie_domain = $this->app->get('cookie_domain');
		$cookie_path   = $this->app->get('cookie_path', '/');
		$cookie_secure = $this->app->isSSLConnection();
		$this->app->input->cookie->set(JApplicationHelper::getHash('language'), $lang_code, $cookie_expire, $cookie_path, $cookie_domain, $cookie_secure);
	}

	/**
	 * Get the language cookie
	 *
	 * @return  string
	 *
	 * @since   3.4.2
	 */
	private function getLanguageCookie()
	{
		$lang_code = $this->app->input->cookie->getString(JApplicationHelper::getHash('language'));

		// Let's be sure we got a valid language code. Fallback to null.
		if (!array_key_exists($lang_code, $this->lang_codes))
		{
			$lang_code = null;
		}

		return $lang_code;
	}
}
