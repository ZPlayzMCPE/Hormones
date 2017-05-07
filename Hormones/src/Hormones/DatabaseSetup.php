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

use libasynql\MysqlCredentials;
use libasynql\PingMysqlTask;
use libasynql\result\MysqlErrorResult;
use libasynql\result\MysqlResult;
use libasynql\result\MysqlSelectResult;
use libasynql\result\MysqlSuccessResult;

class DatabaseSetup{
	/**
	 * @internal Only to be called from HormonesPlugin.php
	 * @param MysqlCredentials $cred
	 * @param HormonesPlugin   $plugin
	 * @param int              &$organId
	 * @return bool
	 */
	public static function setupDatabase(MysqlCredentials $cred, HormonesPlugin $plugin, &$organId) : bool{
		$mysqli = $cred->newMysqli();
		$mysqli->query("CREATE TABLE IF NOT EXISTS hormones_metadata (name VARCHAR(20) PRIMARY KEY, val VARCHAR(20))");

		$mysqli->query("LOCK TABLES hormones_metadata WRITE"); // this should lock all startup operations by Hormones

		$result = MysqlResult::executeQuery($mysqli, "SELECT val FROM hormones_metadata WHERE name = ?", [["s", "version"]]);
		if($result instanceof MysqlSelectResult and count($result->rows) === 0){
			$version = (int) $result->rows[0]["version"];
			if($version < HormonesPlugin::DATABASE_VERSION){
				// TODO update database
				// NOTE handle compatibility and concurrency issues with loaded servers, probably by firing a StopServerHormone or CheckCompatibilityHormone, or explicitly shutdown specified servers

				$mysqli->query("UPDATE hormones_metadata SET val = ? WHERE name = ?", [["s", HormonesPlugin::DATABASE_VERSION], ["s", "version"]]);
			}elseif($version > HormonesPlugin::DATABASE_VERSION){
				$plugin->getLogger()->critical("Plugin is outdated");
				$plugin->getServer()->getPluginManager()->disablePlugin($plugin);
				return false;
			}
		}else{
			// TODO copy code from database-brief.sql

			$mysqli->query("INSERT INTO hormones_metadata (val, name) VALUES (?, ?)", [["s", HormonesPlugin::DATABASE_VERSION], ["s", "version"]]);
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
}
