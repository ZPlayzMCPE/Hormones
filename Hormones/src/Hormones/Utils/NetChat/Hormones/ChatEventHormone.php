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

namespace Hormones\Utils\NetChat\Hormones;

use Hormones\Hormone\Hormone;
use Hormones\HormonesPlugin;

class ChatEventHormone extends Hormone{
	public $priority;
	public $message;
	public $translatable = false;
	public $source;
	public $channel;

	public function getType() : string{
		return "Hormones.Utils.NetChat.ChatEvent";
	}

	public function getData() : array{
		return [
			"priority" => $this->priority,
			"message" => $this->message,
			"translatable" => $this->translatable,
			"source" => $this->source,
			"channel" => $this->channel
		];
	}

	public function respond(array $args){
		/** @var HormonesPlugin $plugin */
		list($plugin) = $args;
		$module = $plugin->getNetChatModule();
		// TODO handle
	}
}
