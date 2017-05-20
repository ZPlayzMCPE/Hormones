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

namespace Hormones\Utils\Moderation\Commands;

use Hormones\HormonesCommand;
use Hormones\HormonesPlugin;
use Hormones\Utils\Moderation\Hormones\BroadcastMessageHormone;
use pocketmine\command\CommandSender;

class BroadcastCommand extends HormonesCommand{
	/** @var bool */
	private $global;

	public function __construct(HormonesPlugin $plugin, bool $global){
		$this->global = $global;
		$alias = $global ? "nsay" : "osay";
		parent::__construct($plugin, $name = $global ? "network-say" : "organ-say",
			"Broadcast a message to all players in the " . ($global ? "network" : "organ {$plugin->getOrganName()}"),
			"/$alias [perm:<permissions>] <message ...>", [$alias]);

		$this->setPermission($global ? "hormones.moderation.moderator.global.broadcast" : "hormones.moderation.moderator.sectional.broadcast");
	}

	public function execute(CommandSender $sender, $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return false;
		}

		$permissions = isset($args[0]) && substr($args[0], 0, 5) === "perm:" ? substr(array_shift($args), 5) : "";

		if(!isset($args[0])){
			$sender->sendMessage($this->getUsage());
			return false;
		}

		$hormone = new BroadcastMessageHormone($this->global ? str_repeat("\xFF", 8) : HormonesPlugin::setNthBit($this->getPlugin()->getOrganId(), 8));
		$hormone->message = implode(" ", $args);
		$hormone->permissions = $permissions;
		$hormone->release($this->getPlugin());

		return true;
	}
}
