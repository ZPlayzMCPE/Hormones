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

namespace Hormones\Utils\Moderation\Hormones;

use Hormones\Hormone\Hormone;
use Hormones\HormonesPlugin;

class KickPlayerHormone extends Hormone{
	public $playerName;
	public $ip;
	public $message;

	public function getType() : string{
		return "Hormones.Moderation.KickPlayer";
	}

	public function getData() : array{
		return [
			"playerName" => $this->playerName,
			"ip" => $this->ip,
			"message" => $this->message
		];
	}

	public function respond(array $args){
		/** @var HormonesPlugin $plugin */
		list($plugin) = $args;
		$player = $plugin->getServer()->getPlayerExact($this->playerName);
		if($player !== null){
			$player->kick($this->message, false);
		}
		foreach($plugin->getServer()->getOnlinePlayers() as $player){
			if($player->getAddress() === $this->ip){
				$player->kick($this->message, false);
			}
		}
	}
}
