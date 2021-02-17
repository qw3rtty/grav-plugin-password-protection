<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Data\Blueprints;
use RocketTheme\Toolbox\Event\Event;


/**
 * Class PasswordProtectionPlugin
 * @package Grav\Plugin
 */
class PasswordProtectionPlugin extends Plugin
{
	/**
	 * Hold's the origin page config
	 * @type array
	 */
	private $_config = [];


	/**
	 * Password from post data
	 * @type string
	 */
	private $_password = "";


	/**
	 * Antispam from post data
	 * @type sring
	 */
	private $_antispam = "";


	/**
	 * Get current page header 
	 * @private
	 */
	private function _getPageHeader()
	{
		$page = $this->grav["page"];
		return $page->header();	
	}


	/**
	 * Create an SHA512 hash of given password
	 *
	 * @return		String		Generated hash, empty string by error
	 * @private
	 */
	private function _createHash($password)
	{
		$hash = "";
		if (isset($password) && is_string($password) && !empty($password)) {
			$hash = hash("sha512", $password);
		}

		return $hash;
	}


	/**
	 * Get plugin config
	 */
	private function _getConfig()
	{
		$pluginConfig = $this->grav['config']->get("plugins." . $this->name, null);
		$header = $this->_getPageHeader();
		
		if (isset($header->pp_headline) && !empty($header->pp_headline)) {
			$pluginConfig["headline"] = $header->pp_headline;
		}
		
		if (isset($header->pp_description) && !empty($header->pp_headline)) {
			$pluginConfig["description"] = $header->pp_description;
		}

		return $pluginConfig;
	}


	/**
	 * Determines if the request is a POST request
	 */
	private function _isPostRequest()
	{
		return $_SERVER["REQUEST_METHOD"] === "POST";	
	}

	
	/**
	 * Filter's the form data
	 * @return	array	Filtered form data array
	 */
	private function _filterFormData($form)
	{
		$defaults = [
			'password'  => '',
			'antispam'  => ''
		];

		return array_merge($defaults, $form);
	}


	/**
	 * Validate's the form data
	 * @return	Boolean		true if form data is valid, false else
	 */
	private function _validateFormData()
	{
		$data = $this->_filterFormData($_POST);
		$this->_password = $data["password"];
		$this->_antispam = $data["antispam"];

		return !(empty($this->_password) || !empty($this->_antispam));
	}


	/**
	 * Validates the password
	 * > check if it is the correct one
	 */
	private function _validatePassword()
	{
		$header = $this->_getPageHeader();
		$hash = $this->_createHash($this->_password);

		return $hash === $header->pp_password_hash;	
    }


    /**
     * Create's the password prompt page
     * @return Page
     */
    public function _getPasswordPage()
    {
        $promptPagePath = __DIR__ . "/pages/password-protection.md";
        $prompt = new Page();
        $prompt->init(new \SplFileInfo($promptPagePath));

        return $prompt;
    }


	/**
	 * Get password prompt
	 */
	private function _getPasswordPrompt()
	{
		if ($this->_isPostRequest())
		{
			if ($this->_validateFormData() && $this->_validatePassword()){
				return;
			}		
		}

		$passwordPage = $this->_getPasswordPage();
        $page = $this->grav["page"];
        
        // Only overwrite the necessary fields
        $page->content($passwordPage->content());
        $page->template($passwordPage->template());
	}


	/**
	 * Return a list of subscribed events
	 *
	 * @return array    The list of events of the plugin of the form
	 *                      'name' => ['method_name', priority].
	 */
	public static function getSubscribedEvents()
	{
		return [
			'onAdminSave'	       => ['onAdminSave', 0],
			'onPageInitialized'    => ['onPageInitialized', 0],
			'onPluginsInitialized' => ['onPluginsInitialized', 0],
			'onTwigTemplatePaths'  => ['onTwigTemplatePaths', 0],
			'onTwigSiteVariables'  => ['onTwigSiteVariables', 0],
		];
	}


	/**
	 * Change page object before saving.
	 * > Add "feed.skip: true" if password protection is enabled
	 * > Creates the SHA512 hash for given password
	 *
	 * @param	Event	$event
	 */
	public function onAdminSave(Event $event)
	{
		$page = $event["object"];
		if (!method_exists($page, "header")) {
			return;
		}

		$header = $page->header();
		$header->undef("feed.skip");
		if (isset($header->pp_protect) && $header->pp_protect) {
			$header->set("feed.skip", true);
		}

		if (isset($header->pp_password) && is_string($header->pp_password) 
				&& !empty($header->pp_password)) { 
			$hash = $this->_createHash($header->pp_password);
			$header->set("pp_password_hash", $hash);
			$header->undef("pp_password");
		}
	}


	/**
	 * Add twig lookup path
	 */
	public function onTwigTemplatePaths()
	{
		$this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
	}


	/**
	 * Add variables for twig template
	 */
	public function onTwigSiteVariables()
	{
		$twig = $this->grav['twig'];
		$twig->twig_vars['pp_config'] = $this->_config;
	}


	/**
	 * Initialize page
	 */
	public function onPageInitialized()
	{
		if ($this->isAdmin()) {
			return;
		}

		$this->_config = $this->_getConfig();
		$header = $this->_getPageHeader();
		if (isset($header->pp_protect) && $header->pp_protect) {
			$this->_getPasswordPrompt();	
		}
	}


	/**
	 * Initialize configuration
	 */
	public function onPluginsInitialized()
	{
		// Set admin specific events
		if ($this->isAdmin()) {
			$events = [
				'onBlueprintCreated' => ['onBlueprintCreated', 0],
			];

			// Register events
			$this->enable($events);
		}
	}


	/**
	 * Extend page blueprints with "Password Protection" configuration options.
	 *
	 * @param Event $event
	 */
	public function onBlueprintCreated(Event $event)
 	{
 		$newtype = $event['type'];
		if (strpos($newtype, 'modular/') === 0) {
			return;
 		}

		$blueprint = $event['blueprint'];
		if ($blueprint->get('form/fields/tabs', null, '/')) {
			$blueprints = new Blueprints(__DIR__ . '/blueprints/');
			$extends = $blueprints->get($this->name);
			$blueprint->extend($extends, true);
		}
	}
}
