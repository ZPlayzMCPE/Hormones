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

namespace Hormones\HormonesUtils;

use Hormones\HormonesPlugin;
use pocketmine\plugin\PluginBase;

class HormonesUtils extends PluginBase{
	/** @var HormonesPlugin */
	private $hormones;
	public function onEnable(){
		$this->hormones = $this->getServer()->getPluginManager()->getPlugin("Hormones");
	}
	/**
	 * @return HormonesPlugin
	 */
	public function getHormones() : HormonesPlugin{
		return $this->hormones;
	}
}
