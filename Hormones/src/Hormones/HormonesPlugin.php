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

use Hormones\Commands\HormonesStatusCommand;
use Hormones\Commands\StopNetworkCommand;
use Hormones\Commands\SwitchOrganCommand;
use Hormones\Hormone\Artery;
use Hormones\Hormone\Kidney;
use Hormones\Lymph\LymphResult;
use Hormones\Lymph\LymphVessel;
use Hormones\TimingStats\TimerSet;
use Hormones\Utils\Balancer\BalancerModule;
use Hormones\Utils\Moderation\ModerationModule;
use Hormones\Utils\SingleSession\SingleSessionModule;
use Hormones\Utils\TransferOnly\TransferOnlyModule;
use libasynql\MysqlCredentials;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Utils;
use spoondetector\SpoonDetector;

class HormonesPlugin extends PluginBase{
	const DATABASE_VERSION = 1;

	/** @var MysqlCredentials */
	private $credentials;
	/** @var int */
	private $organId;
	/** @var string */
	private $organName;
	/** @var string */
	private $serverId;
	/** @var string */
	private $visibleAddress;
	/** @var string */
	private $displayName;

	private $lymphResult;

	/** @var BalancerModule */
	private $balancerModule;
	/** @var int|null */
	private $softSlotsLimit;
	/** @var ModerationModule */
	private $moderationModule;
	/** @var SingleSessionModule */
	private $singleSessionModule;
	/** @var TransferOnlyModule */
	private $transferOnlyModule;

	/** @var callable[] */
	private $arteryDiastoleHandlers = [];

	/** @var TimerSet */
	private $timers;

	public static function getInstance(Server $server) : HormonesPlugin{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $server->getPluginManager()->getPlugin("Hormones");
	}

	public function onEnable(){
		SpoonDetector::printSpoon($this, 'spoon.txt');

		$this->saveDefaultConfig();

		if($this->getConfig()->get("Dear User") === 'Please delete this line after you have finished setting up the config file.'){
			$this->getLogger()->alert("Thank you for using Hormones. Please set up Hormones by editing the config file at " . realpath($this->getDataFolder() . "config.yml"));
			$this->getLogger()->alert("If you have already done so, please delete the \"Dear User\" line (usually line 2) in the config file.");
			$this->getLogger()->alert("Hormones will not be enabled until you have done so");

			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$this->credentials = $cred = MysqlCredentials::fromArray($this->getConfig()->get("mysql"));
		if(!DatabaseSetup::setupDatabase($cred, $this, $organId)){
			return;
		}
		$this->organName = $this->getConfig()->getNested("localize.organ");
		$this->organId = $organId;
		$this->serverId = $this->calcServerId();
		$this->visibleAddress = $this->getConfig()->getNested("localize.address", "auto");
		if($this->visibleAddress === "auto"){
			$this->visibleAddress = Utils::getIP();
		}
		$this->displayName = $this->getConfig()->getNested("localize.name", "auto");
		if($this->displayName === "auto"){
			$this->displayName = $this->visibleAddress . ":" . $this->getServer()->getPort();
		}

		$this->getServer()->getScheduler()->scheduleAsyncTask(new LymphVessel($cred, $this));
		$this->getServer()->getScheduler()->scheduleAsyncTask(new Artery($cred, -1, $organId));
		Kidney::init($this);
		if($this->getConfig()->getNested("organicTransfer.enabled", true)){
			SwitchOrganCommand::registerOrganicStuff($this);
		}
		$this->getServer()->getCommandMap()->register("hormones", new StopNetworkCommand($this));
		$this->getServer()->getCommandMap()->register("hormones", new HormonesStatusCommand($this));

		$this->timers = new TimerSet;

		$this->balancerModule = new BalancerModule($this);
		$this->moderationModule = new ModerationModule($this);
		$this->singleSessionModule = new SingleSessionModule($this);
		$this->transferOnlyModule = new TransferOnlyModule($this);
	}

	private function calcServerId(){
		return md5($this->getServer()->getDataPath() . $this->getServer()->getIp() . $this->getServer()->getPort() .
			$this->getConfig()->getNested("localize.name", "auto"));
	}

	public function getTissueId(){
		return $this->serverId;
	}

	public function getOrganName() : string{
		return $this->organName;
	}

	public function getOrganId() : int{
		return $this->organId;
	}

	public function getVisibleAddress() : string{
		return $this->visibleAddress;
	}

	public function getDisplayName() : string{
		return $this->displayName;
	}

	public function getCredentials() : MysqlCredentials{
		return $this->credentials;
	}

	public function getSoftSlotsLimit() : int{
		return $this->softSlotsLimit ?? ($this->softSlotsLimit =
				$this->getConfig()->getNested("playerSoftLimit", $this->getServer()->getMaxPlayers() - 2));
	}

	public function getLymphResult() : LymphResult{
		return $this->lymphResult;
	}

	public function setLymphResult(LymphResult $lymphResult){
		$this->lymphResult = $lymphResult;
	}

	public function addDiastoleListener(callable $callable){
		$this->arteryDiastoleHandlers[] = $callable;
	}

	public function onArteryDiastole(){
		$handlers = $this->arteryDiastoleHandlers;
		$this->arteryDiastoleHandlers = [];
		foreach($handlers as $handler){
			$handler();
		}
	}

	public function getTimers() : TimerSet{
		return $this->timers;
	}


	public function getBalancerModule() : BalancerModule{
		return $this->balancerModule;
	}

	public function getModerationModule() : ModerationModule{
		return $this->moderationModule;
	}

	public function getSingleSessionModule() : SingleSessionModule{
		return $this->singleSessionModule;
	}

	public function getTransferOnlyModule() : TransferOnlyModule{
		return $this->transferOnlyModule;
	}


	public static function setNthBitSmallEndian(int $n, int $bytes){
		$offset = $n >> 3;
		$byteArray = str_repeat("\0", $bytes);
		$byteArray{$offset} = chr(1 << ($n & 7));
		return $byteArray;
	}
}
