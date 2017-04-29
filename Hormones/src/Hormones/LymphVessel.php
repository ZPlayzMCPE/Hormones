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

namespace Hormones;

use libasynql\MysqlCredentials;
use libasynql\QueryMysqlTask;
use libasynql\result\MysqlResult;
use pocketmine\Server;

class LymphVessel extends QueryMysqlTask{
	private $data;

	public function __construct(MysqlCredentials $credentials, HormonesPlugin $plugin){
		parent::__construct($credentials);
		$this->data = serialize([
			["s", $plugin->getServerId()],
			["i", $plugin->getOrganId()],
			["i", count($plugin->getServer()->getOnlinePlayers())],
			["i", $plugin->getServer()->getMaxPlayers()],
			["s", $plugin->getVisibleAddress()],
			["i", $plugin->getServer()->getPort()],
			["i", HormonesPlugin::DATABASE_VERSION],
			["s", $plugin->getDisplayName()],
			["i", getmypid()]
		]);
	}

	protected function execute(){
		$mysqli = $this->getMysqli();
		MysqlResult::executeQuery($mysqli, "INSERT INTO hormones_tissues
			(tissueId, organId, usedSlots, maxSlots, ip, port, hormonesVersion, displayName, processId) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
			unserialize($this->data));
	}

	public function onCompletion(Server $server){
		$plugin = HormonesPlugin::getInstance($server);
		if($plugin->isEnabled()){
			// an ironic fact: if I forgot to add the $plugin->isEnabled() check, the server may fail to shutdown and the phagocyte can't kill it because it reports to maintain onlinle
			$server->getScheduler()->scheduleAsyncTask(new LymphVessel($this->getCredentials(), $plugin));
		}
	}
}
