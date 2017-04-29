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
use libasynql\result\MysqlSuccessResult;
use pocketmine\Server;

class Vein extends QueryMysqlTask{
	private $hormone;

	public function __construct(MysqlCredentials $credentials, Hormone $hormone){
		parent::__construct($credentials, $hormone);
		$this->hormone = serialize([
			"type" => $hormone->getType(),
			"receptors" => bin2hex($hormone->getReceptors()),
			"creationTime" => $hormone->getCreationTime(),
			"data" => $hormone->getData()
		]);
	}

	protected function execute(){
		$hormone = unserialize($this->hormone);

		$this->setResult(MysqlResult::executeQuery($this->getMysqli(), "INSERT INTO hormones_blood 
				(type, receptors, creation, json) VALUES (?, ?, FROM_UNIXTIME(?), ?)", [
			["s" => $hormone["type"]],
			["s" => $hormone["receptors"]],
			["i" => $hormone["creationTime"]],
			["s" => $hormone["data"]]
		]));
	}

	public function onCompletion(Server $server){
		$result = $this->getResult();
		if($result instanceof MysqlErrorResult){
			$plugin = HormonesPlugin::getInstance($server);
			$plugin->getLogger()->logException($result->getException());
		}elseif($result instanceof MysqlSuccessResult){
			/** @var Hormone $hormone */
			$hormone = $this->fetchLocal($server);
			$hormone->setHormoneId($result->insertId);
		}
	}
}
