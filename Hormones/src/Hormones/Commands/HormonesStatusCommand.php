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

namespace Hormones\Commands;

use Hormones\HormonesCommand;
use Hormones\HormonesPlugin;
use pocketmine\command\CommandSender;

class HormonesStatusCommand extends HormonesCommand{
	public function __construct(HormonesPlugin $plugin){
		parent::__construct($plugin, "hormones", "See Hormones version and status", "/hormones");
	}

	public function execute(CommandSender $sender, $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return false;
		}

		$sender->sendMessage("Using {$this->getPlugin()->getName()} v{$this->getPlugin()->getDescription()->getVersion()} by " . implode(", ", $this->getPlugin()->getDescription()->getAuthors()));
		$timers = $this->getPlugin()->getTimers();
		$sender->sendMessage("Vein up time: " . $timers->veinUp->evalAverage());
		$sender->sendMessage("Artery net time: " . $timers->arteryNet->evalAverage());
		$sender->sendMessage("Artery cycle time: " . $timers->arteryCycle->evalAverage());
		$sender->sendMessage("Lymph net time: " . $timers->lymphNet->evalAverage());
		$sender->sendMessage("Lymph cycle time: " . $timers->lymphCycle->evalAverage());
		return true;
	}
}
