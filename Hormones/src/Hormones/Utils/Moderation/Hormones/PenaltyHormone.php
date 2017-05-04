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

namespace Hormones\Utils\Moderation\Hormones;

use Hormones\Hormone\Hormone;
use Hormones\HormonesPlugin;
use Hormones\Utils\Moderation\PenaltySession;
use Hormones\Utils\Moderation\PlayerIdentification;

class PenaltyHormone extends Hormone{
	public $type;
	public $name;
	public $ip;
	public $message;
	public $source;

	public function getType() : string{
		return "Hormones.Moderation.Penalty";
	}

	public function getData() : array{
		return [
			"type" => $this->type,
			"name" => $this->name,
			"ip" => $this->ip,
			"message" => $this->message,
			"source" => $this->source
		];
	}

	public function respond(array $args){
		/** @var HormonesPlugin $plugin */
		list($plugin) = $args;
		$plugin->getModerationModule()->addPenaltySession($this->toPenaltySession());
	}

	public function toPenaltySession() : PenaltySession{
		return new PenaltySession($this->type, new PlayerIdentification($this->name, $this->ip), $this->message, $this->source, $this->getCreationTime(), $this->getExpiryTime());
	}
}
