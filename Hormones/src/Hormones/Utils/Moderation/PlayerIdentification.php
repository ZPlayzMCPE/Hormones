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

use pocketmine\Player;

class PlayerIdentification{
	public $name;
	public $ip;

	public function __construct(string $name, string $ip){
		$this->name = $name;
		$this->ip = $ip;
	}

	// TODO does clientId still exist?

	public function matchesPlayer(Player $player) : bool{
		return $player->getLowerCaseName() === $this->name or $player->getAddress() === $this->ip;
	}
}
