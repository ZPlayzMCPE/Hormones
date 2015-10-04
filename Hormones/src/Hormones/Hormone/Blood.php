<?php

/*
 * Hormone
 *
 * Copyright (C) 2015 LegendsOfMCPE and contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author LegendsOfMCPE
 */

namespace Hormones\Hormone;

use Hormones\Event\HormoneReceiveEvent;
use Hormones\HormonesPlugin;
use Hormones\HormonesQueryAsyncTask;
use pocketmine\Server;

class Blood extends HormonesQueryAsyncTask{
	public $queryFinished = false;
	private $shiftedOrgan;
	public function __construct(HormonesPlugin $main){
		parent::__construct($main->getMysqlDetails());
		$this->shiftedOrgan = 1 << $main->getOrgan();
	}
	public function onRun(){
		$db = $this->getDb();
		$mResult = $db->query("SELECT id,type,receptors,creation,json,tags FROM blood WHERE (receptors & $this->shiftedOrgan) = $this->shiftedOrgan");
		$output = [];
		while(is_array($row = $mResult->fetch_assoc())){
			$output[] = $row;
		}
		$mResult->close();
		$this->queryFinished = true;
		$this->setResult($output);
	}
	public function onCompletion(Server $server){
		$main = HormonesPlugin::getInstance($server);
		if($main === null){
			return;
		}
		foreach($this->getResult() as $row){
			try{
				$hormone = $main->getHormone($row["type"], (int) $row["receptors"], (int) $row["creation"], json_decode($row["json"], true), array_filter(explode(",", $row["tags"])), (int) $row["id"]);
				$main->getServer()->getPluginManager()->callEvent($ev = new HormoneReceiveEvent($main, $hormone));
				if(!$ev->isCancelled()){
					$hormone->execute();
				}
			}catch(\RuntimeException $e){
				$main->getLogger()->error($e->getMessage());
			}
		}
	}
}
