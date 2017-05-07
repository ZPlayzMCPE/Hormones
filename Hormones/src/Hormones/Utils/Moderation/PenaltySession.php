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

namespace Hormones\Utils\Moderation;

use Hormones\HormonesCommand;
use pocketmine\utils\TextFormat;

class PenaltySession{
	const TYPE_MUTE = "mute";
	const TYPE_BAN = "ban";
	private static $PAST_PARTICIPLE = [
		PenaltySession::TYPE_MUTE => "muted",
		PenaltySession::TYPE_BAN => "banned",
	];

	/** @var string */
	public $type;
	/** @var PlayerIdentification */
	public $target;
	/** @var string */
	public $message;
	/** @var string */
	public $source;
	/** @var int */
	public $since;
	/** @var int */
	public $till;

	public function __construct(string $type, PlayerIdentification $target, string $message, string $source, int $since, int $till){
		$this->target = $target;
		$this->message = $message;
		$this->source = $source;
		$this->since = $since;
		$this->till = $till;
	}

	public function getNotifyMessage(bool $me = true) : bool{
		return TextFormat::YELLOW . sprintf(
				"%s have been %s by %s for %s. You have to wait for %s more before you can chat.",
				$me ? "You" : ($this->target->name . " @ " . $this->target->ip),
				PenaltySession::$PAST_PARTICIPLE[$this->type],
				$this->source,
				HormonesCommand::ui_secsToPresent($this->till - $this->since),
				HormonesCommand::ui_secsToPresent($this->till - time()));
	}

	public function hasExpired() : int{
		return time() > $this->till;
	}
}
