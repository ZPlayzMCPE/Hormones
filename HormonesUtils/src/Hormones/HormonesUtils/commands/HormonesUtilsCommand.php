<?php

/*
 * Hormone
 *
 * Copyright (C) 2015 PEMapModder and contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace Hormones\HormonesUtils\commands;

use Hormones\HormonesUtils\HormonesUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;

abstract class HormonesUtilsCommand extends Command implements PluginIdentifiableCommand{
	/** @var HormonesUtils */
	private $plugin;
	public function __construct(HormonesUtils $plugin, $name, $description, $usage, $permission, ...$aliases){
		parent::__construct($name, $description, $usage, $aliases);
		$this->setPermission($permission);
		$this->plugin = $plugin;
	}
	public function execute(CommandSender $sender, $label, array $args){
		try{
			if(!$this->testPermissionSilent($sender)){
				$sender->sendMessage($this->getPermissionMessage());
				return false;
			}
			$r = $this->run($args, $sender);
			if($r === false){
				$sender->sendMessage($this->getUsage());
				return false;
			}
			return true;
		}catch(\Exception $e){
			// TODO report exception to LegendsOfMCPE server
			throw $e;
		}
	}
	protected abstract function run(array $args, CommandSender $sender);

	/**
	 * @return HormonesUtils
	 */
	public function getPlugin(){
		return $this->plugin;
	}
	public function getHormones(){
		return $this->plugin->getHormones();
	}
}
