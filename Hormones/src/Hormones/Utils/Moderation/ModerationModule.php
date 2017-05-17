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

namespace Hormones\Utils\Moderation;

use Hormones\HormonesPlugin;
use Hormones\Utils\Moderation\Commands\PenaltyCommand;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerPreLoginEvent;

class ModerationModule implements Listener{
	/** @var HormonesPlugin */
	private $plugin;

	/** @var PenaltySession[][] */
	private $penaltySessions = [PenaltySession::TYPE_BAN => [], PenaltySession::TYPE_MUTE => []];

	public function __construct(HormonesPlugin $plugin){
		$this->plugin = $plugin;
		$this->plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
		$this->plugin->getServer()->getCommandMap()->registerAll("hormones", [
			new PenaltyCommand($plugin, PenaltySession::TYPE_BAN, "nban", "Ban"),
			new PenaltyCommand($plugin, PenaltySession::TYPE_MUTE, "nmute", "Mute"),
		]);
	}

	/**
	 * @param PlayerChatEvent $event
	 *
	 * @priority        LOW
	 * @ignoreCancelled true
	 */
	public function e_onChat(PlayerChatEvent $event){
		foreach($this->penaltySessions[PenaltySession::TYPE_MUTE] as $k => $muteSession){
			if($muteSession->hasExpired()){
				unset($this->penaltySessions[PenaltySession::TYPE_MUTE][$k]);
			}
			if($muteSession->target->matchesPlayer($event->getPlayer())){
				$event->setCancelled();
				$event->getPlayer()->sendMessage($muteSession->getNotifyMessage());
			}
		}
	}

	/**
	 * @param PlayerPreLoginEvent $event
	 *
	 * @priority        HIGH
	 * @ignoreCancelled true
	 */
	public function e_onPreLogin(PlayerPreLoginEvent $event){
		foreach($this->penaltySessions[PenaltySession::TYPE_BAN] as $k => $banSession){
			if($banSession->hasExpired()){
				unset($this->penaltySessions[PenaltySession::TYPE_BAN][$k]);
			}
			if($banSession->target->matchesPlayer($event->getPlayer())){
				$event->setCancelled();
				$event->setKickMessage($banSession->getNotifyMessage());
			}
		}
	}

	public function addPenaltySession(PenaltySession $session){
		$this->penaltySessions[$session->type][spl_object_hash($session)] = $session;
	}
}
