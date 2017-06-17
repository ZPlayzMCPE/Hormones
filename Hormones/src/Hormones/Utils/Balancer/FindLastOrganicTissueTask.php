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

declare(strict_types=1);

namespace Hormones\Utils\Balancer;

use Hormones\Commands\FindOrganicTissueTask;
use libasynql\result\MysqlSelectResult;
use pocketmine\Player;

class FindLastOrganicTissueTask extends FindOrganicTissueTask{
	private $fallbackId;
	private $fallbackName;

	public function __construct(BalancerModule $module, Player $player){
		$this->fallbackId = $module->getAlwaysFallbackDestination();
		$this->fallbackName = $module->getAlwaysFallbackName();
		parent::__construct($module->getPlugin(), $player, "", null, function() use ($player){
			$player->kick("All $this->fallbackName servers are full/offline!");
		});
	}

	protected function execute(){
		$db = $this->getMysqli();
		// TODO find last organ ID
		$lastOrganName = "";
		$lastOrganId = -1;
		if($lastOrganId !== -1){
			$this->organId = $lastOrganId;
			$this->organName = $lastOrganName;
			parent::execute();
			$result = $this->getResult();
			if(!($result instanceof MysqlSelectResult and count($result->rows) === 0)){
				return;
			}
			// all tissues in last organ are full
		}
		// else, no last organ, also go to fallback
		$this->organId = $this->fallbackId;
		$this->organName = $this->fallbackName;
		parent::execute();
	}
}
