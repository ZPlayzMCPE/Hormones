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

namespace Hormones\Event;

use Hormones\HormonesPlugin;

/**
 * This event is fired when a hormone of unknown type is downloaded from the blood table.
 */
class UnknownHormoneEvent extends HormonesEvent{
	public static $handlerList = null;

	private $type;
	private $class = null;

	public function __construct(HormonesPlugin $plugin, string $type){
		parent::__construct($plugin);
		$this->type = $type;
	}

	public function getType() : string{
		return $this->type;
	}

	public function getClass(){
		return $this->class;
	}

	public function setClass(string $class){
		$this->class = $class;
	}
}
