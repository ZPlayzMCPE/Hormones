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

namespace Hormones\Utils\Moderation;

use Hormones\Commands\HormonesCommand;
use pocketmine\utils\TextFormat;

class Penalty{
	const TYPE_MUTE = "mute";
	const TYPE_BAN = "ban";
	private static $PAST_PARTICIPLE = [
		Penalty::TYPE_MUTE => "muted",
		Penalty::TYPE_BAN => "banned",
	];
	private static $DISALLOWED = [
		Penalty::TYPE_MUTE => "chat",
		Penalty::TYPE_BAN => "join",
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
		$this->type = $type;
		$this->target = $target;
		$this->message = $message;
		$this->source = $source;
		$this->since = $since;
		$this->till = $till;
	}

	public function getNotifyMessage(bool $me = true) : string{
		return TextFormat::YELLOW . sprintf(
				'%s have been %s by %s for %s: "%s". %s have to wait for %s more before %s can %s.',
				$me ? "You" : ($this->target->name . " @ " . $this->target->ip),
				Penalty::$PAST_PARTICIPLE[$this->type],
				$this->source,
				HormonesCommand::ui_secsToPresent($this->till - $this->since),
				$this->message,
				$me ? "You" : "He/She",
				HormonesCommand::ui_secsToPresent($this->till - time()),
				$me ? "you" : "he/she",
				Penalty::$DISALLOWED[$this->type]);
	}

	public function hasExpired() : bool{
		return $this->till - time() < 0;
	}

	public function __toString() : string{
		return json_encode($this);
	}
}
