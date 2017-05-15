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

	private $objectCreated;

	private $normal;

	public function __construct(MysqlCredentials $credentials, HormonesPlugin $plugin, bool $normal = true){
		parent::__construct($credentials);
		$this->serverId = $plugin->getTissueId();
		$this->organId = $plugin->getOrganId();
		$this->usedSlots = count($plugin->getServer()->getOnlinePlayers());
		$this->maxSlots = $plugin->getSoftSlotsLimit();
		$this->ip = $plugin->getVisibleAddress();
		$this->port = $plugin->getServer()->getPort();
		$this->hormonesVersion = HormonesPlugin::DATABASE_VERSION;
		$this->displayName = $plugin->getDisplayName();
		$this->processId = getmypid(); // make sure this is called from the main thread

		$this->objectCreated = microtime(true);

		$this->normal = $normal;
	}

	protected function execute(){
		if(!$this->normal){
			$lr = new LymphResult();
			$lr->netTime = 0;
			$lr->altServer = new AltServerObject();
			$this->setResult($lr);
			return;
		}
		$mysqli = $this->getMysqli();
		$stmt = $mysqli->prepare(/** @lang MySQL */
			"INSERT INTO hormones_tissues
			(tissueId, organId, usedSlots, maxSlots, ip, port, hormonesVersion, displayName, processId) VALUES 
			(?       , ?      , ?        , ?       , ? , ?   , ?              , ?          , ?) ON DUPLICATE KEY UPDATE
			organId = ?, usedSlots = ?, maxSlots = ?, ip = ?, port = ?, hormonesVersion = ?, displayName = ?, processId = ?,
			lastOnline = CURRENT_TIMESTAMP");
		$stmt->bind_param(str_replace(" ", "",
			"s         i        i          i         s   i     i                s            i" .
			"          i        i          i         s   i     i                s            i"),
			$this->serverId,
			$this->organId, $this->usedSlots, $this->maxSlots, $this->ip, $this->port, $this->hormonesVersion, $this->displayName, $this->processId,
			$this->organId, $this->usedSlots, $this->maxSlots, $this->ip, $this->port, $this->hormonesVersion, $this->displayName, $this->processId);
		$stmt->execute();
		$stmt->close();

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
			$lr = new LymphResult();
			$lr->netTime = $result->getTiming();
			$lr->altServer = $altServer = new AltServerObject();
			if(!isset($result->rows[0])){
				$lr->tissueCount = 0;
				$lr->onlineSlots = 0;
				$lr->totalSlots = 0;
			}else{
				$row = $result->rows[0];
				$lr->tissueCount = $row["tissues"];
				$lr->onlineSlots = $row["online"];
				$lr->totalSlots = $row["total"];
				$altServer->address = $row["ip"];
				$altServer->port = $row["port"];
				$altServer->displayName = $row["displayName"];
			}

			$this->setResult($lr);
		}elseif($result instanceof MysqlErrorResult){
			$this->setResult($result->getException());
		}
	}

	public function onCompletion(Server $server){
		$plugin = HormonesPlugin::getInstance($server);
		if(!$plugin->isEnabled()){
			return;
		}
		if($this->normal){
			$result = $this->getResult();
			if($result instanceof MysqlException){
				$plugin->getLogger()->logException($result);
			}elseif($result instanceof LymphResult){
				$plugin->setLymphResult($result);
				$plugin->getTimers()->lymphNet->addDatum($result->netTime);
			}
		}

		$plugin->getTimers()->lymphCycle->addDatum(microtime(true) - $this->objectCreated);
		$server->getScheduler()->scheduleAsyncTask(new LymphVessel($this->getCredentials(), $plugin, $this->normal));
	}
}
