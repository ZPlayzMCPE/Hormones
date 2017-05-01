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

namespace Hormones\Utils\SingleSession\Hormones;

use Hormones\Hormone\Hormone;
use Hormones\HormonesPlugin;
use Hormones\Utils\SingleSession\SingleSessionModule;

class PushPlayersHormone extends Hormone{
	public $username;
	public $sourceTissueId;
	public $sourceTissueName;
	public $ip;

	public function getType() : string{
		return "Hormones.SingleSession.PushPlayers";
	}

	public function getData() : array{
		return [
			"username" => $this->username,
			"sourceTissueId" => $this->sourceTissueId,
			"sourceTissueName" => $this->sourceTissueName,
			"ip" => $this->ip
		];
	}

	public function respond(array $args){
		/** @var HormonesPlugin $plugin */
		list($plugin) = $args;
		if($plugin->getTissueId() === $this->sourceTissueId){
			return;
		}

		$player = $plugin->getServer()->getPlayerExact($this->username);
		if($player !== null){
			if($plugin->getSingleSessionModule()->getMode() === SingleSessionModule::MODE_IP_PUSH and $player->getAddress() !== $this->ip){ // but that player should be bumped!
				// TODO verify and outline this logic
				return;
			}
			$player->kick("Pushed by player login at $this->sourceTissueName", false);
		}
	}
}
