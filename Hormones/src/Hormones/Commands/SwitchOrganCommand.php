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
	private $organData;

	public static function registerOrganicStuff(HormonesPlugin $plugin){
		$mysqli = $plugin->getCredentials()->newMysqli();

		$mode = $plugin->getConfig()->getNested("organicTransfer.mode", "group");
		if($mode === "off"){
			return;
		}elseif($mode === "group"){
			$modeGroup = true;
		}elseif($mode === "direct"){
			$modeGroup = false;
		}else{
			$plugin->getLogger()->error("Unknown organicTransfer.mode '$mode', using default value 'group'");
			$modeGroup = true;
		}

		$result = MysqlResult::executeQuery($mysqli, "SELECT organId, name FROM hormones_organs", []);
		$mysqli->close();
		if(!($result instanceof MysqlSelectResult)){
			assert($result instanceof MysqlErrorResult);
			throw $result->getException();
		}
		$result->fixTypes([
			"organId" => MysqlSelectResult::TYPE_INT,
			"name" => MysqlSelectResult::TYPE_STRING,
		]);

		$parentPerm = $plugin->getServer()->getPluginManager()->getPermission("hormones.player.transfer.organic");
		if($modeGroup){
			$cmd = new SwitchOrganCommand($plugin, "organic-transfer", "Transfer to a server in another organ", "/ot <organ name>", ["ot"]);
			$cmd->setPermission($parentPerm->getName());
			$cmd->organData = [];
			foreach($result->rows as $row){
				$organId = $row["organId"];
				if($organId === $plugin->getOrganId()){
					continue;
				}
				$cmd->organData[strtolower($row["name"])] = $organId;
			}
			$plugin->getServer()->getCommandMap()->register("hormones", $cmd);
		}else{
			foreach($result->rows as $row){
				$organId = $row["organId"];
				if($organId === $plugin->getOrganId()){
					continue;
				}
				$organName = $row["name"];
				$perm = new Permission("hormones.player.transfer.organic.$organName", "Allows transferring to \"$organName\" servers", Permission::DEFAULT_TRUE);
				$parentPerm->getChildren()[$perm->getName()] = true;
				$plugin->getServer()->getPluginManager()->addPermission($perm);
				$cmd = new SwitchOrganCommand($plugin, $organName, "Transfer to a $organName server", "/$organName");
				$cmd->setPermission($perm->getName());
				$cmd->organId = $organId;
				$cmd->organName = $organName;
				$plugin->getServer()->getCommandMap()->register("organ", $cmd);
			}
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

		if(isset($this->organId, $this->organName)){
			$organId = $this->organId;
			$organName = $this->organName;
		}else{
			// this is the /ot command
			if(!isset($args[0])){
				$sender->sendMessage("Usage: /ot <organ name>");
				$sender->sendMessage("Available organ names: " . implode(", ", array_keys($this->organData)));
				return false;
			}
			$organName = strtolower($args[0]);
			if(!isset($organName)){
				$sender->sendMessage("Unknown organ name: $args[0]");
				$sender->sendMessage("Available organ names: " . implode(", ", array_keys($this->organData)));
				return false;
			}
			$organId = $this->organData[$organName];
		}

		$sender->getServer()->getScheduler()->scheduleAsyncTask(new FindOrganicTissueTask($this->getPlugin()->getCredentials(), $sender, $organId, $organName));
		return true;
	}
}
