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
use libasynql\DirectQueryMysqlTask;
use pocketmine\scheduler\PluginTask;

/**
 * Deletes old hormones
 */
class Kidney extends PluginTask{
	private $expiry;

	public static function init(HormonesPlugin $plugin){
		if($plugin->getConfig()->getNested("kidney.enabled", true)){
			$kidney = new Kidney($plugin);
			$kidney->expiry = $plugin->getConfig()->getNested("kidney.expiry", 600);
			$plugin->getServer()->getScheduler()->scheduleRepeatingTask($kidney, $plugin->getConfig()->getNested("kidney.interval", 600) * 20);
		}
	}

	public function onRun($currentTick){
		/** @var HormonesPlugin $plugin */
		$plugin = $this->getOwner();
		$plugin->getServer()->getScheduler()->scheduleAsyncTask(new DirectQueryMysqlTask($plugin->getCredentials(),
			"DELETE FROM hormones_blood WHERE UNIX_TIMESTAMP(expiry) < UNIX_TIMESTAMP() - ?", [["i", $this->expiry]]));
	}
}
