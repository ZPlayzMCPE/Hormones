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
use pocketmine\utils\TextFormat;

class HormonesStatusCommand extends HormonesCommand{
	public function __construct(HormonesPlugin $plugin){
		parent::__construct($plugin, "hormones", "See Hormones version and status", "/hormones");
		$this->setPermission("hormones.admin.status");
	}

	public function execute(CommandSender $sender, $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return false;
		}

		$sender->sendMessage("Using {$this->getPlugin()->getName()} v{$this->getPlugin()->getDescription()->getVersion()} by " . implode(", ", $this->getPlugin()->getDescription()->getAuthors()));
		$timers = $this->getPlugin()->getTimers();
		$lymphResult = $this->getPlugin()->getLymphResult();
		$sender->sendMessage("Organic slots: " . TextFormat::AQUA . "{$lymphResult->onlineSlots} / {$lymphResult->totalSlots} in {$lymphResult->tissueCount} tissues");
		$sender->sendMessage("Recommended alt server: " . TextFormat::AQUA . "{$lymphResult->altServer->displayName} ({$lymphResult->altServer->address}:{$lymphResult->altServer->port})");
		$sender->sendMessage("Vein up time (s): " . TextFormat::AQUA . $timers->veinUp->evalAverage());
		$sender->sendMessage("Artery net time (s): " . TextFormat::AQUA . $timers->arteryNet->evalAverage());
		$sender->sendMessage("Artery cycle time (s): " . TextFormat::AQUA . $timers->arteryCycle->evalAverage());
		$sender->sendMessage("Lymph net time (s): " . TextFormat::AQUA . $timers->lymphNet->evalAverage());
		$sender->sendMessage("Lymph cycle time (s): " . TextFormat::AQUA . $timers->lymphCycle->evalAverage());
		$sender->sendMessage("Last arterial hormone ID: " . TextFormat::AQUA . $this->getPlugin()->getLastArterialHormoneId());
		return true;
	}
}
