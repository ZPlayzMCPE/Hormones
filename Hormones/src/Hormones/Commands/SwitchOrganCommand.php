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
use libasynql\result\MysqlErrorResult;
use libasynql\result\MysqlResult;
use libasynql\result\MysqlSelectResult;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\Player;

class SwitchOrganCommand extends HormonesCommand{
	private $organId;
	private $organName;

	public static function registerOrganicStuff(HormonesPlugin $plugin){
		$mysqli = $plugin->getCredentials()->newMysqli();
		$result = MysqlResult::executeQuery($mysqli, "SELECT organId, name FROM hormones_organs", []);
		$mysqli->close();
		if(!($result instanceof MysqlSelectResult)){
			assert($result instanceof MysqlErrorResult);
			throw $result->getException();
		}
		$parent = $plugin->getServer()->getPluginManager()->getPermission("hormones.transfer.oragnic");
		foreach($result->rows as $row){
			$organId = (int) $row["organId"];
			$organName = $row["name"];
			$perm = new Permission("hormones.transfer.organic.$organName", "Allows transferring to \"$organName\" servers", Permission::DEFAULT_TRUE);
			$parent->getChildren()[$perm->getName()] = true;
			$plugin->getServer()->getPluginManager()->addPermission($perm);
			$cmd = new SwitchOrganCommand($plugin, $organName, "Transfer to a $organName server", "/$organName");
			$cmd->setPermission($perm->getName());
			$cmd->organId = $organId;
			$cmd->organName = $organName;
			$plugin->getServer()->getCommandMap()->register("organ", $cmd);
		}
	}

	public function execute(CommandSender $sender, $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return false;
		}
		if(!($sender instanceof Player)){
			$sender->sendMessage("Please run this command as a player");
			return false;
		}
		$sender->getServer()->getScheduler()->scheduleAsyncTask(new FindOrganicTissueTask($this->getPlugin()->getCredentials(), $sender, $this->organId, $this->organName));
		return true;
	}
}
