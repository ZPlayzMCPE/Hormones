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

namespace Hormones\Event;

use Hormones\Hormone\Hormone;
use Hormones\HormonesPlugin;
use pocketmine\event\Cancellable;

class HormoneReceiveEvent extends HormonesEvent implements Cancellable{
	public static $handlerList = null;

	private $hormone;

	public function __construct(HormonesPlugin $plugin, Hormone $hormone){
		parent::__construct($plugin);
		$this->hormone = $hormone;
	}

	public function getHormone() : Hormone{
		return $this->hormone;
	}
}
