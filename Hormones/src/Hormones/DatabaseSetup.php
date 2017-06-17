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

namespace Hormones;

use Hormones\Hormone\Defaults\VerifyDatabaseVersionHormone;
use libasynql\MysqlCredentials;
use libasynql\PingMysqlTask;
use libasynql\result\MysqlErrorResult;
use libasynql\result\MysqlResult;
use libasynql\result\MysqlSelectResult;
use libasynql\result\MysqlSuccessResult;
use Logger;

class DatabaseSetup{
	/**
	 * @internal Only to be called from HormonesPlugin.php
	 *
	 * @param MysqlCredentials $cred
	 * @param HormonesPlugin   $plugin
	 * @param int              &$organId
	 * @param \mysqli|null     $mysqli
	 *
	 * @return bool
	 */
	public static function setupDatabase(MysqlCredentials $cred, HormonesPlugin $plugin, &$organId, \mysqli $mysqli) : bool{
		$plugin->getLogger()->debug("Checking database...");
		$mysqli->query("CREATE TABLE IF NOT EXISTS hormones_metadata (name VARCHAR(20) PRIMARY KEY, val VARCHAR(20))");

		$mysqli->query("LOCK TABLES hormones_metadata WRITE, hormones_organs WRITE, hormones_blood WRITE, hormones_tissues WRITE, hormones_mod_banlist WRITE"); // this should lock all startup operations by Hormones

		$result = MysqlResult::executeQuery($mysqli, "SELECT val FROM hormones_metadata WHERE name = ?", [["s", "version"]]);
		if($result instanceof MysqlSelectResult and count($result->rows) > 0){
			$version = (int) $result->rows[0]["val"];
			if($version < HormonesPlugin::DATABASE_VERSION){
				$plugin->getLogger()->notice("Updating the database! Other servers in the network might become incompatible and require updating.");
				$hormone = new VerifyDatabaseVersionHormone;
				$hormone->pluginVersion = $plugin->getDescription()->getVersion();
				$hormone->dbVersion = HormonesPlugin::DATABASE_VERSION;

				// TODO update database
				// NOTE handle compatibility and concurrency issues with loaded servers, probably by firing a StopServerHormone or CheckCompatibilityHormone, or explicitly shutdown specified servers

				$mysqli->query("UPDATE hormones_metadata SET val = ? WHERE name = ?", [["s", HormonesPlugin::DATABASE_VERSION], ["s", "version"]]);
			}elseif($version > HormonesPlugin::DATABASE_VERSION){
				$plugin->getLogger()->critical("Plugin is outdated");
				$plugin->getServer()->getPluginManager()->disablePlugin($plugin);
				return false;
			}else{
				$plugin->getLogger()->debug("Database OK");
			}
		}else{
			$plugin->getLogger()->info("Thanks for using Hormones the first time. Setting up database tables...");
			DatabaseSetup::initialSetup($mysqli, $plugin->getLogger());

			$op = $mysqli->prepare("INSERT INTO hormones_metadata (val, name) VALUES (?, ?)");
			$value = HormonesPlugin::DATABASE_VERSION;
			$name = "version";
			$op->bind_param("ss", $value, $name);
			$op->execute();
		}

		$organName = $plugin->getConfig()->getNested("localize.organ");
		$result = MysqlResult::executeQuery($mysqli, "SELECT organId FROM hormones_organs WHERE name = ?", [["s", $organName]]);
		if($result instanceof MysqlSelectResult and isset($result->rows[0])){
			$organId = (int) $result->rows[0]["organId"];
		}else{
			$result = MysqlResult::executeQuery($mysqli, "INSERT INTO hormones_organs (name) VALUES (?)", [["s", $organName]]);
			if($result instanceof MysqlSuccessResult){
				$organId = (int) $result->insertId;
			}else{
				assert($result instanceof MysqlErrorResult);
				throw $result->getException();
			}
		}

		$mysqli->query("UNLOCK TABLES");

		PingMysqlTask::init($plugin, $cred);

		return true;
	}

	private static function initialSetup(\mysqli $mysqli, Logger $logger){
		$queries = [
			/** @lang MySQL */
			"CREATE TABLE IF NOT EXISTS hormones_metadata (
				name VARCHAR(20) PRIMARY KEY,
				val VARCHAR(20)
			);",
			/** @lang MySQL */
			"CREATE TABLE IF NOT EXISTS hormones_organs (
				organId TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				name VARCHAR(64) UNIQUE
			) AUTO_INCREMENT = 0;",
			/** @lang MySQL */
			"CREATE TRIGGER organs_organId_limit BEFORE INSERT ON hormones_organs FOR EACH ROW
			BEGIN
				IF (NEW.organId < 0 OR NEW.organId > 63) THEN
					SIGNAL SQLSTATE '45000'
						SET MESSAGE_TEXT = 'organ flag';
				END IF;
			END",
			/** @lang MySQL */
			"CREATE TABLE IF NOT EXISTS hormones_blood (
				hormoneId BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				type VARCHAR(64) NOT NULL,
				receptors BIT(64) DEFAULT x'FFFFFFFFFFFFFFFF',
				creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				expiry TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				json TEXT
			);",
			/** @lang MySQL */
			"CREATE TABLE IF NOT EXISTS hormones_tissues (
				tissueId CHAR(32) PRIMARY KEY,
				organId TINYINT UNSIGNED NOT NULL,
				lastOnline TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				usedSlots SMALLINT UNSIGNED,
				maxSlots SMALLINT UNSIGNED,
				ip VARCHAR(68),
				port SMALLINT UNSIGNED,
				hormonesVersion MEDIUMINT,
				displayName VARCHAR(100),
				processId SMALLINT UNSIGNED,
				FOREIGN KEY (organId) REFERENCES hormones_organs(organId) ON UPDATE CASCADE ON DELETE RESTRICT
			);",
			/** @lang MySQL */
			"CREATE TABLE IF NOT EXISTS hormones_mod_banlist (
				name VARCHAR(20) PRIMARY KEY,
				start TIMESTAMP NOT NULL,
				stop TIMESTAMP,
				message VARCHAR(512) DEFAULT '',
				organs BIT(64) DEFAULT x'FFFFFFFFFFFFFFFF',
				doer VARCHAR(20)
			);"
		];
		foreach($queries as $query){
			$result = $mysqli->query($query);
			if($result !== true){
				$logger->error("Failed to execute database setup query: $mysqli->error\n$query");
			}
		}
	}
}
