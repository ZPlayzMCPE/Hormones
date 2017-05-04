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

	public static function ui_inputToSecs(string $input) : int{
		$input = strtolower($input);
		$duplets = [];
		$duplet = ["", ""];
		$lastInt = true;
		for($i = 0; $i < strlen($input); $i++){
			$ord = ord($input{$i});
			if(ord("0") <= $ord and $ord <= chr("9")){
				$isInt = true;
			}elseif(ord("a") <= $ord and $ord <= chr("z")){
				$isInt = false;
			}else{
				continue;
			}
			if(!$lastInt and $isInt){
				$duplets[] = $duplet;
				$duplet = ["", ""];
			}
			$duplet[$isInt ? 0 : 1] .= $input{$i};
			$lastInt = $isInt;
		}
		if($duplet !== ["", ""]){
			throw new \InvalidArgumentException("Incomplete time input");
		}

		$units = [
			"millennium" => 86400 * 365242, // don't ask me why. some judges have a strange sense of favour of imprisoning people for 300 years rather than life imprisonment.
			// Figures from https://pumas.nasa.gov/files/04_21_97_1.pdf
			"century" => 86400 * 36524,
			"decade" => 86400 * 3652,
			"y" => 86400 * 365,
			"yr" => 86400 * 365,
			"year" => 86400 * 365,
			"season" => 86400 * 91, // if anyone would ever use this
			"month" => 86400 * 30,
			"fortnight" => 1209600, // in order not to waste this line of code I'll use this in testing
			"w" => 604800,
			"wk" => 604800,
			"week" => 604800,
			"d" => 86400,
			"day" => 86400,
			"h" => 3600,
			"hr" => 3600,
			"hour" => 3600,
			"m" => 60,
			"min" => 60,
			"minute" => 60,
			"s" => 1,
			"sec" => 1,
			"second" => 1
		];

		$secs = 0;
		foreach($duplets as list($coef, $unit)){
			if($unit !== "s" and substr($unit, 0, -1) === "s"){
				$unit = substr($unit, 0, -1);
			}
			if(!isset($units[$unit])){
				throw new \InvalidArgumentException("Unknown unit $unit");
			}
			$secs += $coef * $units[$unit];
		}
		return $secs;
	}

	public static function ui_secsToPresent(int $secs) : string{
		$units = [
			"day" => 86400,
			"hour" => 3600,
			"minute" => 60
		];

		$parts = [];

		foreach($units as $unit => $ratio){
			if($secs >= 2 * $ratio){ // not $secs > $ratio
				$parts[] = ((int) ($secs / $ratio)) . " " . $unit . "s";
				$secs %= $ratio;
			}elseif($secs >= $ratio){
				$parts[] = "1 " . $unit;
				$secs -= $ratio;
			}
		}

		if(count($parts) === 0 or $secs > 0){
			$parts[] = $secs > 1 ? "$secs seconds" : "$secs second";
		}

		return implode(", ", $parts);
	}
}
