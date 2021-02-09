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
	 * Get current page header 
	 * @private
	 */
	private function _getPageHeader()
	{
		$page = $this->grav["page"];
		return $page->header();	
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
		];
	}

	
	/**
	 * Initialize page
	 */
	public function onPageInitialized()
	{
		if ($this->isAdmin()) {
			return;
		}

		$header = $this->_getPageHeader();
		if ($header->pp_protect) {
			echo "PP ENABLED: " . $header->pp_protect;
		}
	}


	/**
	 * Initialize configuration
	 */
	public function onPluginsInitialized()
	{
		// Set admin specific events
		if ($this->isAdmin()) {
			$this->active = false;
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
