<?php

/*
 * Hormone
 *
 * Copyright (C) 2015 PEMapModder and contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace Hormones\HormonesUtils\hormones;

use Hormones\Hormone\Hormone;
use pocketmine\Server;

class BroadcastHormone extends Hormone{
	public function execute(){
		$data = $this->getData();
		if(isset($data["msg"])){
			$this->getMain()->getServer()->broadcast($data["msg"], isset($data["permission"]) ? $data["permission"] : Server::BROADCAST_CHANNEL_USERS);
		}
	}
}
