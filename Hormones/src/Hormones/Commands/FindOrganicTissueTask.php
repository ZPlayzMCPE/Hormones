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

use libasynql\MysqlCredentials;
use libasynql\QueryMysqlTask;
use libasynql\result\MysqlErrorResult;
use libasynql\result\MysqlResult;
use libasynql\result\MysqlSelectResult;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class FindOrganicTissueTask extends QueryMysqlTask{
	/** @var int */
	private $organId;
	/** @var string */
	private $organName;

	public function __construct(MysqlCredentials $credentials, Player $player, int $organId, string $organName){
		parent::__construct($credentials, $player);
		$this->organId = $organId;
		$this->organName = $organName;
	}

	protected function execute(){
		$this->setResult(MysqlResult::executeQuery($this->getMysqli(), "SELECT ip, port, displayName FROM hormones_tissues
				WHERE organId = ? AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(lastOnline) < 5 AND maxSlots > usedSlots
				ORDER BY (maxSlots - usedSlots) DESC, maxSlots ASC LIMIT 1",
			[["i" => $this->organId]]));
	}

	public function onCompletion(Server $server){
		/** @var Player $player */
		$player = $this->fetchLocal($server);
		if(!$player->isOnline()){
			return;
		}

		$result = $this->getResult();
		if($result instanceof MysqlSelectResult){
			if(count($result->rows) === 1){
				$player->transfer($result->rows[0]["ip"], $result->rows[0]["port"],
					"Transferring you to the least full $this->organName server: " . $result->rows[0]["displayName"]);
			}else{
				$player->sendMessage(TextFormat::YELLOW . "All $this->organName servers are full/offline!");
			}
		}else{
			assert($result instanceof MysqlErrorResult);
			throw $result->getException();
		}
	}
}
