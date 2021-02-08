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
	 * Return a list of subscribed events
	 *
	 * @return array    The list of events of the plugin of the form
	 *                      'name' => ['method_name', priority].
	 */
	public static function getSubscribedEvents()
	{
		return [
			'onPluginsInitialized' => ['onPluginsInitialized', 0],
			//'onPageInitialized'    => ['onPageInitialized', 0],
		];
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
