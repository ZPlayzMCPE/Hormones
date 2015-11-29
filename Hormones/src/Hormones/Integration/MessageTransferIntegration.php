<?php

/*
 * Hormone
 *
 * Copyright (C) 2015 LegendsOfMCPE and contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author LegendsOfMCPE
 */

namespace Hormones\Integration;

use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MessageTransferIntegration implements TransferIntegration{

	public function transferPlayer(Player $player, $ip, $port, $kickMessage = null){
		$player->kick($kickMessage ?? "Please join this server:\n\n" . TextFormat::AQUA . $ip . ":" . $port);
	}
}
