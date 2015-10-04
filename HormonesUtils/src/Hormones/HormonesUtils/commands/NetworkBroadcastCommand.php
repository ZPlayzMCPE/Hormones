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

namespace Hormones\HormonesUtils\commands;

use Hormones\HormonesUtils\HormonesUtils;
use pocketmine\command\CommandSender;

class NetworkBroadcastCommand extends HormonesUtilsCommand{
	public function __construct(HormonesUtils $plugin){
		parent::__construct($plugin, "broadcast", "Broadcast a message to the whole network", "/bc [.local] <message ...>", "hormones.broadcast", "bc", "bdc", "brc", "brct", "brcst", "bdcst");
	}
	protected function run(array $args, CommandSender $sender){
	}
}
