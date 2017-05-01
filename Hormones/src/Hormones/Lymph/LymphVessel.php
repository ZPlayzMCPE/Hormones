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

namespace Hormones\Lymph;

use Hormones\HormonesPlugin;
use libasynql\exception\MysqlException;
use libasynql\MysqlCredentials;
use libasynql\QueryMysqlTask;
use libasynql\result\MysqlErrorResult;
use libasynql\result\MysqlResult;
use libasynql\result\MysqlSelectResult;
use pocketmine\Server;

class LymphVessel extends QueryMysqlTask{
	private $serverId;
	private $organId;
	private $usedSlots;
	private $maxSlots;
	private $ip;
	private $port;
	private $hormonesVersion;
	private $displayName;
	private $processId;

	public function __construct(MysqlCredentials $credentials, HormonesPlugin $plugin){
		parent::__construct($credentials);
		$this->serverId = $plugin->getServerId();
		$this->organId = $plugin->getOrganId();
		$this->usedSlots = count($plugin->getServer()->getOnlinePlayers());
		$this->maxSlots = $plugin->getSoftSlotsLimit();
		$this->ip = $plugin->getVisibleAddress();
		$this->port = $plugin->getServer()->getPort();
		$this->hormonesVersion = HormonesPlugin::DATABASE_VERSION;
		$this->displayName = $plugin->getDisplayName();
		$this->processId = getmypid();
	}

	protected function execute(){
		$mysqli = $this->getMysqli();
		$stmt = $mysqli->prepare("INSERT INTO hormones_tissues
			(tissueId, organId, usedSlots, maxSlots, ip, port, hormonesVersion, displayName, processId) VALUES 
			(?       , ?      , ?        , ?       , ? , ?   , ?              , ?          , ?)");
		$stmt->bind_param(str_replace(" ", "",
			"s         i        i          i         s   i     i                s            i"),
			$this->serverId, $this->organId, $this->usedSlots, $this->maxSlots, $this->ip, $this->port, $this->hormonesVersion, $this->displayName, $this->processId);

		$result = MysqlResult::executeQuery($mysqli,
			"SELECT t.tissues, t.online, t.total, t2.ip, t2.port, t2.displayName FROM
				(SELECT COUNT(*) tissues, IFNULL(SUM(usedSlots), 0) online, IFNULL(SUM(maxSlots), 0) total, MAX(maxSlots - usedSlots) maxAvail
						FROM hormones_tissues WHERE organId = ? AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(lastOnline) < 10) t
				LEFT JOIN hormones_tissues t2 ON t.maxAvail = t2.maxSlots - t2.usedSlots
				WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(t2.lastOnline) < 10
				ORDER BY maxSlots LIMIT 1",
			[["i", $this->organId]]);
		// maxSlots - usedSlots = available slots
		// ORDER BY maxSlots => minimum percentage load

		if($result instanceof MysqlSelectResult){
			$result->fixTypes([
				"tissues" => MysqlSelectResult::TYPE_INT,
				"online" => MysqlSelectResult::TYPE_INT,
				"total" => MysqlSelectResult::TYPE_INT,
				"ip" => MysqlSelectResult::TYPE_STRING,
				"port" => MysqlSelectResult::TYPE_INT,
				"displayName" => MysqlSelectResult::TYPE_STRING,
			]);
			$row = $result->rows[0];
			$lr = new LymphResult();
			$lr->tissueCount = $row["tissues"];
			$lr->onlineSlots = $row["online"];
			$lr->totalSlots = $row["total"];
			$lr->altServer = $altServer = new AltServerObject();
			$altServer->address=$row["ip"];
			$altServer->port=$row["port"];
			$altServer->displayName=$row["displayName"];

			$this->setResult($lr);
		}elseif($result instanceof MysqlErrorResult){
			$this->setResult($result->getException());
		}
	}

	public function onCompletion(Server $server){
		$plugin = HormonesPlugin::getInstance($server);
		if($plugin->isEnabled()){
			// an ironic fact: if I forgot to add the $plugin->isEnabled() check, the server may fail to shutdown and the phagocyte can't kill it because it reports to maintain onlinle

			$result = $this->getResult();
			if($result instanceof MysqlException){
				$plugin->getLogger()->logException($result);
			}elseif($result instanceof LymphResult){
				$plugin->setLymphResult($result);
			}

			$server->getScheduler()->scheduleAsyncTask(new LymphVessel($this->getCredentials(), $plugin));
		}
	}
}
