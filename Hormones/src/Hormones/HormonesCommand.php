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

use pocketmine\command\Command;
use pocketmine\command\PluginIdentifiableCommand;

abstract class HormonesCommand extends Command implements PluginIdentifiableCommand{
	private $plugin;

	public function __construct(HormonesPlugin $plugin, string $name, $description = "", $usageMessage = null, $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);
		$this->plugin = $plugin;
	}

	public function getPlugin() : HormonesPlugin{
		return $this->plugin;
	}
}
