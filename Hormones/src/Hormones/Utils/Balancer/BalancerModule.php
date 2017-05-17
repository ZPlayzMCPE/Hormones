<?php

/*
 *
 * Hormones
 *
 * Copyright (C) 2017 SOFe
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
*/

namespace Hormones\Utils\Balancer;

use Hormones\HormonesPlugin;
use Hormones\Utils\Balancer\Event\PlayerBalancedEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;

class BalancerModule implements Listener{
	private $plugin;
	/** @var string[] */
	private $exempts;

	public function __construct(HormonesPlugin $plugin){
		$this->plugin = $plugin;
		$this->exempts = array_fill_keys(array_map("strtolower", $this->getPlugin()->getConfig()->getNested("balancer.exemptPlayers")), true);
		if($plugin->getConfig()->getNested("balancer.enabled", true)){
			$this->getPlugin()->getServer()->getPluginManager()->registerEvents($this, $plugin);
		}
	}

	/**
	 * @param PlayerLoginEvent $event
	 *
	 * @priority        HIGH
	 * @ignoreCancelled true
	 */
	public function e_onPreLogin(PlayerLoginEvent $event){
		// We can't check permissions here because permission plugins have not checked it yet
		var_dump(count($this->getPlugin()->getServer()->getOnlinePlayers()), $this->getPlugin()->getSoftSlotsLimit());

		if(count($this->getPlugin()->getServer()->getOnlinePlayers()) >= $this->getPlugin()->getSoftSlotsLimit()){ // getOnlinePlayers() doesn't include the current player
			$player = $event->getPlayer();
			if($this->getPlugin()->getLymphResult()->altServer->address === null){
				$event->setCancelled();
				$event->setKickMessage("All {$this->getPlugin()->getOrganName()} servers are full!");
				return;
			}
			$balEv = new PlayerBalancedEvent($this->getPlugin(), $player, $this->getPlugin()->getLymphResult()->altServer);
			if(isset($this->exempts[strtolower($player->getName())])){
				$balEv->setCancelled();
			}
			$this->getPlugin()->getServer()->getPluginManager()->callEvent($balEv);

			if(!$balEv->isCancelled()){
				$this->getPlugin()->getLogger()->info("Transferring {$player->getName()} to alt server: {$balEv->getTargetServer()->displayName}");
				$player->transfer($balEv->getTargetServer()->address, $balEv->getTargetServer()->port,
					"Server full! Transferring you to {$balEv->getTargetServer()->displayName}");
				$event->setCancelled();
			}else{
				$this->getPlugin()->getLogger()->debug("Event cancelled");
			}
		}
	}

	public function getPlugin() : HormonesPlugin{
		return $this->plugin;
	}
}
