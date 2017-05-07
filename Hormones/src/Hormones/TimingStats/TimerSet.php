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

namespace Hormones\TimingStats;

class TimerSet{
	/** @var Timer */
	public $veinUp, $arteryNet, $arteryCycle;

	public function __construct(){
		$this->veinUp = new Timer;
		$this->arteryNet = new Timer;
		$this->arteryCycle = new Timer;
	}
}
