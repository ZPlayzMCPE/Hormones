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

namespace Hormones\Event;

use Hormones\Hormone\Hormone;
use Hormones\HormonesPlugin;

class HormonesHormoneEvent extends HormonesEvent{
	/**
	 * @var Hormone
	 */
	private $hormone;
	public function __construct(HormonesPlugin $hormones, Hormone $hormone){
		parent::__construct($hormones);
		$this->hormone = $hormone;
	}
	/**
	 * @return Hormone
	 */
	public function getHormone() : Hormone{
		return $this->hormone;
	}
}
