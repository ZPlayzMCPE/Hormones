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

use Hormones\HormonesQueryAsyncTask;

class HormoneCleaner extends HormonesQueryAsyncTask{
	private $id;
	public function __construct(Hormone $hormone){
		parent::__construct($hormone->getMain()->getMysqlDetails());
		$this->id = $hormone->getId();
	}
	public function onRun(){
		$this->getDb()->query("DELETE FROM blood WHERE id=$this->id");
	}
}
