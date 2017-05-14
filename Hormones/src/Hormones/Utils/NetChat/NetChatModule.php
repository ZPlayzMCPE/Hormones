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

namespace Hormones\Utils\NetChat;

use Hormones\HormonesPlugin;
use libasynql\DirectQueryMysqlTask;
use libasynql\result\MysqlErrorResult;
use libasynql\result\MysqlResult;
use libasynql\result\MysqlSelectResult;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

class NetChatModule implements Listener{
	private $plugin;
	private $enabled;
	/** @var NetChatSession[] */
	private $sessions = [];
	/** @var NetChatChannel[] */
	private $loadedChannels = []; // TODO clean loaded channels periodically

	public function __construct(HormonesPlugin $plugin){
		$this->plugin = $plugin;
		$this->enabled = $plugin->getConfig()->getNested("netChat.enabled", false);
		if(!$this->enabled){
			return;
		}

		$this->plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * @param PlayerLoginEvent $event
	 * @priority        MONITOR
	 * @ignoreCancelled true
	 */
	public function e_onLogin(PlayerLoginEvent $event){
		$session = new NetChatSession($this, $event->getPlayer());
		$this->sessions[$event->getPlayer()->getId()] = $session;
	}

	public function e_onQuit(PlayerQuitEvent $event){
		if(isset($this->sessions[$event->getPlayer()->getId()])){
			$this->sessions[$event->getPlayer()->getId()]->finalize();
			unset($this->sessions[$event->getPlayer()->getId()]);
		}
	}

	public function getLoadedChannel(string $name){
		return $this->loadedChannels[mb_strtolower($name)] ?? null;
	}

	public function lazyGetChannel(string $name, callable $return, callable $notFound){
		$lowName = mb_strtolower($name);
		if(isset($lowName)){
			$return($this->loadedChannels[$lowName]);
		}else{
			$task = new DirectQueryMysqlTask($this->plugin->getCredentials(),
				"SELECT name, visible, invite, passphrase, permission, defaultPerm FROM hormones_netchat_channels WHERE name = ?", [
					["s", $name]
				], function(MysqlResult $result) use ($lowName, $return, $notFound){
					if(!($result instanceof MysqlSelectResult)){
						assert($result instanceof MysqlErrorResult);
						throw $result->getException();
					}
					if(count($result->rows) === 0){
						$notFound();
						return;
					}
					$result->fixTypes([
						"name" => MysqlSelectResult::TYPE_STRING,
						"visible" => MysqlSelectResult::TYPE_BOOL,
						"invite" => MysqlSelectResult::TYPE_BOOL,
						"defaultPerm" => MysqlSelectResult::TYPE_INT,
						// passphrase and permission nullable
					]);
					$row = $result->rows[0];
					$channel = new NetChatChannel($row["name"], $row["visible"], $row["invite"],
						$row["passphrase"] ?? null, $row["permission"] ?? null, $row["defaultPerm"]);
					$return($channel);
				});
			$this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
		}
	}

	public function isEnabled() : bool{
		return $this->enabled;
	}

	public function getPlugin() : HormonesPlugin{
		return $this->plugin;
	}
}
