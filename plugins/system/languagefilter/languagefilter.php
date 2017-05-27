<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.languagefilter
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

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
	 * After initialise.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onAfterInitialise()
	{
		if ($this->app->isClient('site') === false)
		{
			return;
		}

		$this->app->setLanguageFilter(true);
		$this->app->setDetectBrowser((bool) $this->params->get('detect_browser', 1));

		// Setup language data.
		$this->mode_sef     = (int) $this->app->get('sef', 0);
		$this->sefs         = JLanguageHelper::getContentLanguages(true, true, 'sef');
		$this->lang_codes   = JLanguageHelper::getContentLanguages(true, true, 'lang_code');
		$this->default_lang = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		$this->current_lang = $this->app->getLanguage()->getTag();

		// Remove language with other access levels.
		foreach ($this->sefs as $sef => $language)
		{
			if ($language->access && !in_array($language->access, JFactory::getUser()->getAuthorisedViewLevels()))
			{
				unset($this->lang_codes[$language->lang_code], $this->sefs[$language->sef]);
			}
		}

		// Attach the language routing events.
		$router = $this->app->getRouter();

		// Attach build rules for language uri.
		$router->attachBuildRule(array($this, 'preprocessBuildRule'), JRouter::PROCESS_BEFORE);
		$router->attachBuildRule(array($this, 'buildRule'), JRouter::PROCESS_DURING);
		$router->attachBuildRule(array($this, ($this->mode_sef === 1 ? 'postprocessSEFBuildRule' : 'postprocessNonSEFBuildRule')), JRouter::PROCESS_AFTER);

		// Attach parse rules for language uri.
		$router->attachParseRule(array($this, 'parseRule'), JRouter::PROCESS_DURING);
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
		if (isset($this->lang_codes[$this->current_lang]) === true && (string) $this->lang_codes[$this->current_lang]->sitename !== '')
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
		// Get the query string param language code from the uri.
		$languageCode = JLanguageHelper::getLanguageCodeFromEnvironment($uri->getVar('lang', $this->current_lang), false, false);

		$uri->setVar('lang', $this->lang_codes[$languageCode]->sef);
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
		if ($this->mode_sef === 0)
		{
			return;
		}

		// Get the query string param language code from the uri.
		$languageCode = JLanguageHelper::getLanguageCodeFromEnvironment($uri->getVar('lang', $this->current_lang), false, false);

		// Add the sef language slug if needed.
		if ((int) $this->params->get('remove_default_prefix', 0) === 0 || $languageCode !== $this->default_lang || $languageCode !== $this->current_lang)
		{
			$uri->setPath($uri->getPath() . '/' . $this->lang_codes[$languageCode]->sef . '/');
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
		$languageCode = JLanguageHelper::getLanguageCodeFromEnvironment($uri->getVar('lang', ''), false, false);

		if ($languageCode !== '')
		{
			$uri->setVar('lang', $this->lang_codes[$languageCode]->sef);
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
		$detectBrowserLanguage = (int) $this->params->get('detect_browser', 1);
		$removePrefix          = (int) $this->params->get('remove_default_prefix', 0);
		$languageCodeCookie    = (string) $this->getLanguageCookie();

		// Start by getting the language code form the query string 'lang' parameter.
		$languageCode = JLanguageHelper::getLanguageCodeFromEnvironment($uri->getVar('lang', ''), false, false);

		// Language code not found in param, if sef mode is disabled we get the language from environment redirect to the url but with the 'lang' param.
		if ($languageCode === '' && $this->mode_sef === 0)
		{
			$languageCode = JLanguageHelper::getLanguageCodeFromEnvironment($languageCodeCookie, (bool) $detectBrowserLanguage, true);

			$uri->setVar('lang', $this->lang_codes[$languageCode]->sef);
			$this->languageRedirect($uri->base() . 'index.php?' . $uri->getQuery(), $languageCode);
		}
		// Language code not found in param, if sef mode is enabled ...
		elseif ($languageCode === '' && $this->mode_sef === 1)
		{
			$path             = $uri->getPath();
			list($sefSlug)    = explode('/', $path);
			$sefSlug          = isset($this->sefs[$sefSlug]) === true ? $this->sefs[$sefSlug]->sef : '';
			$languageCode     = $sefSlug !== '' ? $this->sefs[$sefSlug]->lang_code : '';
			$languageRedirect = null;

			// If exists, remove sef language slug prefix (ex: /en/x to /x) from uri path for router internal processing.
			$path = preg_replace('#^' . $sefSlug . '(/|$)#', '', $path);

			// If exists, remove index.php from uri path.
			$path = preg_replace('#index\.php$#', '', $path);

			// Check if we need to redirect.
			// Unable to find the language code from the sef language slug.
			if ($languageCode === '' && $sefSlug === '')
			{
				$languageCodeEnvironment = JLanguageHelper::getLanguageCodeFromEnvironment($languageCodeCookie, (bool) $detectBrowserLanguage, false);

				// A cookie/browser language exists and is not the default language, a redirect to that language sef slug is needed. (ex: /x to /pt/x).
				if ($languageCodeEnvironment !== '' && $languageCodeEnvironment !== $this->default_lang)
				{
					$languageCode     = $languageCodeEnvironment;
					$languageRedirect = 'index.php?lang=' . $this->lang_codes[$languageCode]->sef;
				}
				// If preserve sef prefix mode, a redirect to default language sef slug is needed (ex: /x to /en/x).
				elseif ($removePrefix === 0)
				{
					$languageCode     = $this->default_lang;
					$languageRedirect = 'index.php?lang=' . $this->lang_codes[$languageCode]->sef;
				}
			}
			// In remove sef prefix mode and is the default language, redirect to default language without sef language slug (ex: /en/x to /x).
			elseif ($removePrefix === 1 && $languageCode === $this->default_lang)
			{
				$languageRedirect = '';
			}

			// A redirect is needed, do it.
			if ($languageRedirect !== null)
			{
				// Add a cookie before the redirect so the app can load it after the redirect.
				$this->setLanguageCookie($languageCode);

				// Add the new redirect path.
				$uri->setPath(ltrim(JRoute::_($languageRedirect), '/') . $path);

				// Redirect.
				$this->languageRedirect($uri->base() . $uri->toString(array('path', 'query', 'fragment')), $languageCode);
			}

			// No redirect needed, set the internal routing path, without the sef language slug prefix.
			$uri->setPath($path);
		}

		// Fallback to the language from environment, if needed.
		if ($languageCode === '')
		{
			$languageCode = JLanguageHelper::getLanguageCodeFromEnvironment($languageCodeCookie, (bool) $detectBrowserLanguage, true);
		}

		// Override the cookie value, if needed.
		if ($languageCodeCookie !== $languageCode)
		{
			$this->setLanguageCookie($languageCode);
		}

		// Set the request variables.
		$this->app->input->set('language', $languageCode);
		$this->app->set('language', $languageCode);

		// If language code not current language, update the app.
		if ($this->current_lang !== $languageCode)
		{
			// Set as current language in the language filter plugin.
			$this->current_lang = $languageCode;

			// Change the app language.
			JLanguageHelper::changeAppLanguage($languageCode);
		}

		return array('lang' => $this->lang_codes[$languageCode]->sef);
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
		if ($this->app->isClient('site') === false || (int) $this->params->get('automatic_change', 1) === 0 || array_key_exists('params', $user) === false)
		{
			return;
		}

		$userParams           = new Registry($user['params']);
		$this->user_lang_code = (string) $userParams->get('language', $this->current_lang);
	}

	/**
	 * After store user method.
	 *
	 * Method is called after user data is stored in the database.
	 *
	 * @param   array    $user     Holds the new user data.
	 * @param   boolean  $isNew    True if a new user is stored.
	 * @param   boolean  $success  True if user was succesfully stored in the database.
	 * @param   string   $msg      Message.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onUserAfterSave($user, $isNew, $success, $msg)
	{
		if ($this->app->isClient('site') === false || (int) $this->params->get('automatic_change', 1) === 0 || array_key_exists('params', $user) === false)
		{
			return;
		}

		$userParams   = new Registry($user['params']);
		$languageCode = $userParams->get('language', $this->current_lang);

		if ($languageCode === $this->user_lang_code || isset($this->lang_codes[$languageCode]) === false)
		{
			return;
		}

		$profileRedirect = 'index.php?Itemid=' . $this->app->getMenu()->getDefault($languageCode)->id . '&lang=' . $this->lang_codes[$languageCode]->sef;

		$this->app->setUserState('com_users.edit.profile.redirect', $profileRedirect);

		// Create a cookie.
		$this->setLanguageCookie($languageCode);
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
		if ($this->app->isClient('site') === false || (int) $this->params->get('automatic_change', 1) === 0)
		{
			return;
		}

		// Get all available content languages (published or not published).
		$languagesCodes = JLanguageHelper::getContentLanguages(false, true, 'lang_code');

		// Get the user language code.
		$languageCode = (string) $user['language'];

		// If no language is specified for this user, get the language from the environment, fallback to site default language if needed.
		if (isset($languagesCodes[$languageCode]) === false || array_key_exists($languageCode, JLanguageMultilang::getSiteHomePages()) === false)
		{
			$languageCode = JLanguageHelper::getLanguageCodeFromEnvironment((string) $this->getLanguageCookie(), (bool) $this->params->get('detect_browser', 1), true);
		}

		// Try to get association from the current active menu item.
		$menu           = $this->app->getMenu();
		$activeMenuItem = $menu->getActive();

		// If there is no active menu item or a login_redirect_url exists for that menu item, do nothing.
		if ($activeMenuItem === null || (string) $activeMenuItem->params['login_redirect_url'] !== '')
		{
			return;
		}

		/**
		 * Looking for associations.
		 * If the login menu item form contains an internal URL redirection,
		 * This will override the automatic change to the user preferred site language.
		 * In that case we use the redirect as defined in the menu item.
		 *  Otherwise we redirect, when available, to the user preferred site language.
		 */
		$associations     = array();
		$associatedItemId = 0;

		// Get the associations for the menu item id.
		if (JLanguageAssociations::isEnabled() === true)
		{
			// Retrieve the Itemid from the login form return url.
			$uri       = new JUri($userLoginReturnUrl);
			$uriItemId = (int) $uri->getVar('Itemid', 0);

			// Retrieve the associations for the menu item id (retrived from the login form return url, fallback to active menu item id).
			$menuItemId   = $uriItemId !== 0 ? $uriItemId : $activeMenuItem->id;
			$associations = MenusHelper::getAssociations($menuItemId);

			// Check if an association exists for the language code.
			if (isset($associations[$languageCode]) === true && $menu->getItem($associations[$languageCode]))
			{
				$associatedItemId = $associations[$languageCode];
			}
		}

		// Even if there is no associations, if this is the homepage, the association is the language Home page.
		if ($associatedItemId === 0 && $activeMenuItem->home)
		{
			$item                 = $menu->getDefault($languageCode);
			$invalidLanguageCodes = array($activeMenuItem->language, '*');

			if (isset($item) === true && in_array($item->language, $invalidLanguageCodes, true) === false)
			{
				$associationItemid = $item->id;
			}
		}

		// Found an associated menu item id, redirect to it after login.
		if ($associatedItemId !== 0)
		{
			$this->app->setUserState('users.login.form.return', JRoute::_('index.php?Itemid=' . $associatedItemId . '&lang=' . $languageCode));
		}

		return;
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
		if ($this->app->isClient('site') === false || (int) $this->params->get('alternate_meta', 1) === 0)
		{
			return;
		}

		$doc = JFactory::getDocument();

		if ($doc->getType() !== 'html')
		{
			return;
		}

		$menu                 = $this->app->getMenu();
		$activeMenuItem       = $menu->getActive();
		$isHomePage           = false;
		$router               = $this->app->getRouter();
		$currentInternalUrlId = (int) $router->getVar('Itemid', 0);
		$associations         = array(); 
		$currentInternalUrl   = 'index.php?' . http_build_query($router->getVars());

		if ($activeMenuItem && $currentInternalUrlId !== 0 && (int) $activeMenuItem->id === $currentInternalUrlId)
		{
			// Load menu associations
			$associations = MenusHelper::getAssociations($activeMenuItem->id);

			// Check if we are on the home page
			$isHomePage = $activeMenuItem->home;
		}

		// Load component associations.
		$className = StringHelper::ucfirst(StringHelper::str_ireplace('com_', '', $this->app->input->get('option', '', 'cmd'))) . 'HelperAssociation';

		JLoader::register($className, JPath::clean(JPATH_COMPONENT_SITE . '/helpers/association.php'));

		if (class_exists($className) && is_callable(array($className, 'getAssociations')))
		{
			$componentAssociations = call_user_func(array($className, 'getAssociations'));
		}

		// Get all language home pages.
		$homePages = JLanguageMultilang::getSiteHomePages();

		// Get all valid languages.
		$languages = array_intersect_key($this->lang_codes, $homePages);

		// For each language...
		foreach ($languages as $languageCode => &$language)
		{
			// Home page.
			if ($isHomePage === true)
			{
				$language->link = JRoute::_('index.php?Itemid=' . $homePages[$languageCode]->id . '&lang=' . $language->sef);
			}
			// Current language link.
			elseif ($languageCode === $this->current_lang)
			{
				$language->link = JRoute::_($currentInternalUrl);
			}
			// Component associations.
			elseif (isset($componentAssociations[$languageCode]) === true)
			{
				$language->link = JRoute::_($componentAssociations[$languageCode] . '&lang=' . $language->sef);
			}
			// Menu items associations.
			elseif (isset($associations[$languageCode]) === true && ($item = $menu->getItem($associations[$languageCode])))
			{
				$language->link = JRoute::_('index.php?Itemid=' . $item->id . '&lang=' . $language->sef);
			}
			// Not a valid language.
			else
			{
				unset($languages[$languageCode]);
			}
		}

		// If there are at least 2 available languages, add the rel="alternate" links to the <head>
		if (count($languages) > 1)
		{
			$baseUri = rtrim(JUri::getInstance()->base(), '/');

			// Remove the sef from the default language if "Remove URL Language Code" is on
			if (isset($languages[$this->default_lang]) === true && (int) $this->params->get('remove_default_prefix', 0) === 1)
			{
				$languages[$this->default_lang]->link = preg_replace('#^/' . $languages[$this->default_lang]->sef . '/#', '/', $languages[$this->default_lang]->link);
			}

			// Add altrernate language link tags.
			foreach ($languages as $languageCode => $language)
			{
				$doc->addHeadLink($baseUri . $language->link, 'alternate', 'rel', array('hreflang' => $languageCode));
			}

			// Add x-default language link tag.
			if ((int) $this->params->get('xdefault', 1) === 1)
			{
				$xDefaultLanguageCode = (string) $this->params->get('xdefault_language', $this->default_lang);
				$xDefaultLanguageCode = $xDefaultLanguageCode === 'default' ? $this->default_lang : $xDefaultLanguageCode;

				// Use a custom tag because addHeadLink is limited to one URI per tag.
				if (isset($languages[$xDefaultLanguageCode]) === true)
				{
					$doc->addCustomTag('<link href="' . $baseUri . $languages[$xDefaultLanguageCode]->link . '" rel="alternate" hreflang="x-default" />');
				}
			}
		}
	}

	/**
	 * Set the language cookie
	 *
	 * @param   string  $languageCode  The language code for which we want to set the cookie
	 *
	 * @return  void
	 *
	 * @since   3.4.2
	 */
	private function setLanguageCookie($languageCode)
	{
		// Set the user language in the session (that is already saved in a cookie).
		JFactory::getSession()->set('plg_system_languagefilter', $languageCode);

		// If is set to use language cookie for a year in plugin params, save the user language in a new cookie (one year lifetime).
		if ((int) $this->params->get('lang_cookie', 0) === 1)
		{
			$this->app->input->cookie->set(
				JApplicationHelper::getHash('language'),
				$languageCode,
				time() + 365 * 86400,
				$this->app->get('cookie_path', '/'),
				$this->app->get('cookie_domain', ''),
				$this->app->isHttpsForced(),
				true
			);
		}
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
		$languageCode = '';

		// Is is set to use a year language cookie in plugin params, get the user language from the cookie.
		if ((int) $this->params->get('lang_cookie', 0) === 1)
		{
			$languageCode = $this->app->input->cookie->get(JApplicationHelper::getHash('language'));
		}

		// If no language code get the user language from the session.
		if ($languageCode === '')
		{
			$languageCode = JFactory::getSession()->get('plg_system_languagefilter');
		}

		// Let's be sure we got a valid language code. Fallback to null.
		if (array_key_exists($languageCode, $this->lang_codes) === false)
		{
			return '';
		}

		return $languageCode;
	}

	/**
	 * Make a language redirect.
	 *
	 * @param   string  $redirectUri    The uri to redirect to.
	 * @param   string  $languageCode   The language code.
	 *
	 * @return  null
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function languageRedirect($redirectUri, $languageCode)
	{
		// Set redirect HTTP code to "302 Found".
		$redirectHttpCode = 302;

		// If selected language is the default language redirect code is "301 Moved Permanently".
		if ($languageCode === $this->default_lang)
		{
			$redirectHttpCode = 301;

			// We cannot cache this redirect in browser. 301 is cachable by default so we need to force to not cache it in browsers.
			$this->app->setHeader('Expires', 'Wed, 17 Aug 2005 00:00:00 GMT', true);
			$this->app->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT', true);
			$this->app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);
			$this->app->setHeader('Pragma', 'no-cache');
			$this->app->sendHeaders();
		}

		// Redirect.
		$this->app->redirect($redirectUri, $redirectHttpCode);
	}
}
