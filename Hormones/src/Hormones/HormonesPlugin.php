<?php

/*
 * Hormones
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

namespace Hormones;

use Hormones\Hormone\Artery;
use Hormones\Hormone\Hormone;
use Hormones\Lymph\LymphResult;
use Hormones\Lymph\LymphVessel;
use mysqli;
use Phar;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine_utils\Utils;
use RuntimeException;
use shoghicp\FastTransfer\FastTransfer;
use WeakRef;

class HormonesPlugin extends PluginBase{
	const HORMONES_DB = "hormones.db";

	/** @var FastTransfer */
	private $transferIntegration;
	/** @var string[]|Hormone[] */
	private $hormoneTypes = [];
	/** @var array */
	private $mysqlDetails;
	/** @var int */
	private $organ;
	/** @var string */
	private $organName;
	/** @var string */
	private $serverID;
	/** @var int */
	private $maxPlayerCnt;
	/** @var LymphResult|null */
	private $lymphResult = null;
	/** @var object[] */
	private $objStore = [];
	/** @var WeakRef[] */
	private $weakObjStore = [];
	/** @var int */
	private $nextObjId = 1;

	/**
	 * @internal
	 */
	public function onEnable(){
		if(!is_file($this->getDataFolder() . "config.yml")){
			$me = Phar::running(false);
			$this->getLogger()->critical("Please run the phar file $me to configure Hormones.");
			$os = Utils::getOS();
			$binary = PHP_BINARY;
			$basename = basename($me);
			if($os === "win"){
				file_put_contents($file = realpath(dirname(Phar::running(false)) . "/HormonesInstaller.cmd"), <<<EOF
@echo off
$binary $basename
EOF
				);
				$this->getLogger()->alert("Please open the file $file to configure Hormones, or simply run this on Windows command terminal: \r\n$binary $basename");
			}else{
				file_put_contents($file = realpath(dirname(Phar::running(false)) . "/HormonesInstaller.sh"), <<<EOF
#!/bin/bash
$binary $basename
EOF
				);
				$this->getLogger()->alert("Please run the file $file to configure Hormones, or simply run this command line:\n$binary $basename");
			}
			throw new RuntimeException("Startup not configured");
		}
		$this->transferIntegration = $this->getServer()->getPluginManager()->getPlugin("FastTransfer");
		if(!($this->transferIntegration instanceof FastTransfer)){
			throw new \UnexpectedValueException("FastTransfer plugin is invalid");
		}
		$this->getLogger()->debug("Loading config...");
		$this->mysqlDetails = $this->getConfig()->get("mysql", [
			"hostname" => "127.0.0.1",
			"username" => "root",
			"password" => "",
			"schema" => "hormones",
		]);
		$this->getLogger()->debug("Testing Heart connection...");
		/** @noinspection PhpUsageOfSilenceOperatorInspection */
		$conn = @self::getMysqli($this->mysqlDetails);
		if($conn->connect_error){
			$this->getLogger()->critical("Could not connect to MySQL database: " . $conn->connect_error);
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->getLogger()->debug("Building Heart...");
		$res = $this->getResource("dbInit.sql");
		$conn->query(stream_get_contents($res));
		fclose($res);
		if($conn->error){
			$this->getLogger()->critical("Failed to prepare Heart: " . $conn->error);
			$this->getServer()->getPluginManager()->disablePlugin($this);
		}
		$organ = $this->getConfig()->getNested("localize.organ");
		if(is_string($organ)){
			$organName = $organ;
			unset($organ);
			$this->getLogger()->notice("Converting organ name '$organName' into organ ID...");
			$result = $conn->query("SELECT flag FROM organs WHERE name='{$conn->escape_string($organName)}'");
			$row = $result->fetch_assoc();
			$result->close();
			if(is_array($row)){
				$organ = (int) $row["flag"];
			}else{
				$this->getLogger()->notice("Registering new organ type: '$organName'");
				$conn->query("INSERT INTO organs (name) VALUES ('{$conn->escape_string($organName)}')");
				$organ = $conn->insert_id;
			}
		}elseif(is_int($organ)){
			$result = $conn->query("SELECT name FROM organs WHERE flag=$organ");
			$row = $result->fetch_assoc();
			if(is_array($row)){
				$organName = $row["name"];
			}else{
				$this->getLogger()->critical("Fatal: Unregistered organ ID $organ");
				$this->getServer()->getPluginManager()->disablePlugin($this);
				return;
			}
		}else{
			$this->getLogger()->critical("Fatal: Illegal organ type " . gettype($organ));
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->getLogger()->info("Starting tissue " . ($this->serverID = $this->getServer()->getServerUniqueId()) . " of organ '$organName' (#$organ)...");
		$this->organ = $organ;
		$this->organName = $organName;
		$this->maxPlayerCnt = (int) $this->getConfig()->getNested("localize.maxPlayers", 20);
		$playerCnt = count($this->getServer()->getOnlinePlayers());
		$conn->query("INSERT INTO tissues (id, organ, laston, usedslots, maxslots) VALUES ('{$conn->escape_string($this->serverID)}', 1 << $this->organ, unix_timestamp(), $playerCnt, $this->maxPlayerCnt)");

		$this->getServer()->getScheduler()->scheduleRepeatingTask(new LymphVessel($this), 1);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Artery($this), 1);

		$this->getLogger()->info("Startup completed.");
	}
	/**
	 * @internal
	 */
	public function onDisable(){
		if(isset($this->mysqlDetails)){
			/** @noinspection PhpUsageOfSilenceOperatorInspection */
			$db = @self::getMysqli($this->mysqlDetails);
			$db->query("UPDATE tissues SET laston=0 WHERE id='{$this->getServer()->getServerUniqueId()}'");
			$db->close();
		}
	}

	/**
	 * @param array $mysqlDetails
	 * @return mysqli
	 */
	public static function getMysqli(array $mysqlDetails) : mysqli{
		return new mysqli(
			isset($mysqlDetails["hostname"]) ? $mysqlDetails["hostname"] : "127.0.0.1",
			isset($mysqlDetails["hostname"]) ? $mysqlDetails["username"] : "root",
			isset($mysqlDetails["hostname"]) ? $mysqlDetails["password"] : "",
			isset($mysqlDetails["hostname"]) ? $mysqlDetails["schema"] : "hormones",
			isset($mysqlDetails["hostname"]) ? $mysqlDetails["port"] : 3306
		);
	}
	/**
	 * @return array
	 */
	public function getMysqlDetails() : array{
		return $this->mysqlDetails;
	}
	/**
	 * @return int
	 */
	public function getOrgan() : int{
		return $this->organ;
	}
	/**
	 * @return string
	 */
	public function getOrganName() : string{
		return $this->organName;
	}
	/**
	 * @return int
	 */
	public function getMaxPlayerCount() : int{
		return $this->maxPlayerCnt;
	}
	/**
	 * @return string
	 */
	public function getServerID() : string{
		return $this->serverID;
	}
	/**
	 * @return LymphResult|null
	 */
	public function getLastLymphResult(){
		if($this->lymphResult !== null){
			return $this->lymphResult;
		}
		return new LymphResult;
	}
	/**
	 * @param LymphResult $result
	 * @internal
	 */
	public function refreshLymphResult(LymphResult $result){
		$this->lymphResult = $result;
	}
	/**
	 * @param string $class
	 * @throws \ClassNotFoundException|\ClassCastException|\InvalidStateException|\OverflowException
	 */
	public function registerHormoneType(string $class){
		try{
			$refClass = new \ReflectionClass($class);
		}catch(\ReflectionException $e){
			throw new \ClassNotFoundException("Unknown class '$class'");
		}
		if(!$refClass->isSubclassOf(Hormone::class)){
			throw new \ClassCastException("$class must extend " . Hormone::class);
		}
		$constructor = $refClass->getConstructor();
		if(!$constructor->isPublic()){
			throw new \InvalidStateException("$class::__construct must be public");
		}
		$shortName = $refClass->getShortName();
		if(strlen($shortName) > 63){
			throw new \OverflowException("Class name $shortName is too long; hormone type names must not exceed 63 characters.");
		}
		$this->hormoneTypes[$shortName] = $class;
	}
	/**
	 * @param string $type
	 * @param int $receptors
	 * @param int $creationTime
	 * @param mixed $data
	 * @param string[] $tags
	 * @param null $id
	 * @return Hormone
	 */
	public function getHormone(string $type, int $receptors, int $creationTime, $data, $tags = [], $id = null) : Hormone{
		if(isset($this->hormoneTypes[$type])){
			$class = $this->hormoneTypes[$type];
			/** @var Hormone $hormone */
			$hormone = new $class($this, $receptors, $creationTime, $data, $tags, $id);
			return $hormone;
		}
		throw new RuntimeException("Unknown hormone type: $type");
	}
	/**
	 * @param Server $server
	 * @return HormonesPlugin|null
	 */
	public static function getInstance(Server $server) : HormonesPlugin{
		return $server->getPluginManager()->getPlugin("Hormones");
	}

	public function transferPlayer(Player $player, string $ip, int $port, string $msg){
		$this->transferIntegration->transferPlayer($player, $ip, $port, $msg);
	}
	/**
	 * WARNING: Do NOT use the $weak option until PocketMine is shipped with a stable version of WeakRef with PHP 7!
	 * @param object $object $object
	 * @param bool $weak default false
	 * @return int
	 */
	public function storeObject(object $object, bool $weak = false) : int{
		$ret = $this->nextObjId++;
		if(!$weak){
			$this->objStore[$ret] = $object;
		}else{
			$this->weakObjStore[$ret] = new WeakRef($weak);
		}
		return $ret;
	}
	/**
	 * @param int $objId
	 * @return object
	 * @throws RuntimeException
	 */
	public function fetchObject(int $objId) : object{
		if(isset($this->objStore[$objId])){
			$weakRef = $this->objStore[$objId];
			unset($this->objStore[$objId]);
			return $weakRef;
		}
		if(isset($this->weakObjStore[$objId])){
			$weakRef = $this->weakObjStore[$objId];
			unset($this->weakObjStore[$objId]);
			return $weakRef->valid() ? $weakRef->get() : null;
		}
		if($objId >= $this->nextObjId){
			throw new RuntimeException("Unknown object ID $objId");
		}
		throw new RuntimeException("Object is already released from Hormones Global Object Storage");
	}
}
