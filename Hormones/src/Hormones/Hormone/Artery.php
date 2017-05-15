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

namespace Hormones\Hormone;

use Hormones\HormonesPlugin;
use libasynql\MysqlCredentials;
use libasynql\QueryMysqlTask;
use libasynql\result\MysqlErrorResult;
use libasynql\result\MysqlResult;
use libasynql\result\MysqlSelectResult;
use pocketmine\Server;

class Artery extends QueryMysqlTask{
	private $hormonesAfter;
	private $organId;
	private $objectCreated;

	private $normal;

	public function __construct(MysqlCredentials $credentials, int $hormonesAfter, int $organId, bool $normal = true){
		parent::__construct($credentials);
		$this->hormonesAfter = $hormonesAfter;
		$this->organId = $organId;
		$this->objectCreated = microtime(true);
		$this->normal = $normal;
	}

	protected function execute(){
		if(!$this->normal){
			return;
		}

		$bitmask = str_repeat("\0", 8);
		if($this->hormonesAfter !== -1){
			$after = "hormoneId > ?";
			$args = [["i", $this->hormonesAfter], ["s", $bitmask]];
		}else{
			$after = "UNIX_TIMESTAMP(expiry) > UNIX_TIMESTAMP()";
			$args = [["s", $bitmask]];
		}

		$this->setResult(MysqlResult::executeQuery($this->getMysqli(), "SELECT
				hormoneId, type, receptors, UNIX_TIMESTAMP(creation) creationTime, UNIX_TIMESTAMP(expiry) expiryTime, json
				FROM hormones_blood WHERE $after AND (receptors & ?) > 0",
			$args));
	}

	public function onCompletion(Server $server){
		$plugin = HormonesPlugin::getInstance($server);
		if(!$plugin->isEnabled()){
			return;
		}
		$lastHormoneId = $this->hormonesAfter;
		if($this->normal){
			$result = $this->getResult();
			if(!$plugin->isEnabled()){
				return;
			}
			if($result instanceof MysqlErrorResult){
				$plugin->getLogger()->logException($result->getException());
				return;
			}elseif($result instanceof MysqlSelectResult){
				$result->fixTypes([
					"hormoneId" => MysqlSelectResult::TYPE_INT,
					"type" => MysqlSelectResult::TYPE_STRING,
					"receptors" => MysqlSelectResult::TYPE_STRING,
					"creationTime" => MysqlSelectResult::TYPE_INT,
					"expiryTime" => MysqlSelectResult::TYPE_INT,
					"json" => MysqlSelectResult::TYPE_STRING
				]);
				foreach($result->rows as $row){
					Hormone::handleRow($plugin, $row);
					$lastHormoneId = $row["hormoneId"];
				}
				$plugin->onArteryDiastole();
				$plugin->getTimers()->arteryNet->addDatum($result->getTiming());
			}
		}
		$plugin->getTimers()->arteryCycle->addDatum(microtime(true) - $this->objectCreated);
		$server->getScheduler()->scheduleAsyncTask(new Artery($this->getCredentials(), $lastHormoneId, $this->organId, $this->normal));
	}
}
