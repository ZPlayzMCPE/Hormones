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

namespace Hormones\Hormone;

use Hormones\Event\UnknownHormoneEvent;
use Hormones\HormonesPlugin;

abstract class Hormone{
	public static $knownTypes = [ // I struggled so many times not to rename this to antigen...
		"hormones.HormoneName" => Hormone::class, // NOTE this is a dummy entry. Delete this when there are real hormones.
	];

	private $hormoneId;
	/** @var string [64] a 64-bit byte-array bitmask */
	private $receptors; // I wanted to make this an int, but then I considered it won't work on 32-bit systems
	private $creationTime;

	/**
	 * Internal constructor. Subclasses MUST call this method.
	 *
	 * @param string|null $receptors the bitmask for oragns to handle this hormone
	 */
	protected function __construct(string $receptors = null){
		$this->receptors = $receptors ?? str_repeat("\xFF", 8);
		$this->creationTime = time();
	}

	protected function enableAllOrgans(){
		$this->receptors = str_repeat("\xFF", 8);
	}

	protected function disableReceptors(){
		$this->receptors = str_repeat("\0", 8);
	}

	protected function enableOrgan(int $organId){
		$this->receptors |= HormonesPlugin::setNthBitSmallEndian($organId, 8);
	}

	protected function disableOrgan(int $organId){
		$this->receptors &= ~HormonesPlugin::setNthBitSmallEndian($organId, 8);
	}

	/**
	 * Only to be called from Artery.php
	 *
	 * @param HormonesPlugin $plugin
	 * @param array          $row
	 */
	public static function handleRow(HormonesPlugin $plugin, array $row){
		if(isset(self::$knownTypes[$row["type"]])){
			$class = self::$knownTypes[$row["type"]];
		}else{
			$event = new UnknownHormoneEvent($plugin, $row["type"]);
			$plugin->getServer()->getPluginManager()->callEvent($event);
			$class = $event->getClass();
			if($class === null){
				$plugin->getLogger()->error("Received hormone of unknown type: " . $row["type"]);
				return;
			}
		}
		/** @var Hormone $hormone */
		$hormone = new $class($row["receptors"]);
		$hormone->hormoneId = $row["hormoneId"];
		$hormone->creationTime = $row["creationTime"];
		$hormone->setData(json_decode($row["json"], true));
		$hormone->respond();
	}

	public function release(HormonesPlugin $plugin){
		$plugin->getServer()->getScheduler()->scheduleAsyncTask(new Vein($plugin->getCredentials(), $this));
	}

	public abstract function getType() : string;

	public function getReceptors() : string{
		return $this->receptors;
	}

	public function getCreationTime() : int{
		return $this->creationTime;
	}

	public abstract function getData() : array;

	public abstract function setData(array $data);

	public abstract function respond();

	/**
	 * This should only be called from LymphVessel.php
	 *
	 * @param int $hormoneId
	 */
	public function setHormoneId(int $hormoneId){
		$this->hormoneId = $hormoneId;
	}
}
