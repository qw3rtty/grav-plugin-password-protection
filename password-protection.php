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
	 * Get plugin config
	 */
	private function _getConfig()
	{
		$pluginConfig = $this->grav['config']->get("plugins." . $this->name, null);
		$header = $this->_getPageHeader();
		
		if (isset($header->pp_headline)) {
			$pluginConfig["headline"] = $header->pp_headline;
		}
		
		if (isset($header->pp_description)) {
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
		$hash = hash("sha512", $this->_password);
		return $hash === $header->pp_password_hash;	
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

		$prompt = new Page();
		$prompt->init(new \SplFileInfo(__DIR__ . '/pages/password-protection.md'));
		$prompt->header()->title = $this->grav["page"]->header()->title;
			        
		unset($this->grav['page']);
		$this->grav['page'] = $prompt;
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
			'onPageInitialized'    => ['onPageInitialized', 0],
			'onPluginsInitialized' => ['onPluginsInitialized', 0],
			'onTwigTemplatePaths'  => ['onTwigTemplatePaths', 0],
			'onTwigSiteVariables'  => ['onTwigSiteVariables', 0],
		];
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
