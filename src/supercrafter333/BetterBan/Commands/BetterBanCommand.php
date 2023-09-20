<?php

/*
 *
 *  ____       _   _            ____
 * |  _ \     | | | |          |  _ \
 * | |_) | ___| |_| |_ ___ _ __| |_) | __ _ _ __
 * |  _ < / _ \ __| __/ _ \ '__|  _ < / _` | '_ \
 * | |_) |  __/ |_| ||  __/ |  | |_) | (_| | | | |
 * |____/ \___|\__|\__\___|_|  |____/ \__,_|_| |_|
 *
 * Copyright (c) 2023 by supercrafter333
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at: https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author supercrafter333
 * @link https://github.com/supercrafter333/BetterBan
 *
 */

declare(strict_types=1);

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Forms\BBDefaultForms;

/**
 * Class BetterBanCommand
 * @package supercrafter333\BetterBan\Commands
 */
class BetterBanCommand extends BetterBanOwnedCommand {
	/**
	 * BetterBanCommand constructor.
	 * @param string $name
	 * @param string $description
	 * @param string|null $usageMessage
	 * @param array $aliases
	 */
	public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = []) {
		$this->setPermission("BetterBan.betterban.cmd");
		parent::__construct("betterban", "Open the BetterBan Form!", $usageMessage, $aliases);
	}


	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * 
	 * @return void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$this->testPermission($sender)) {
			return;
		}
		if ($sender instanceof Player) {
			$sender->sendForm(BBDefaultForms::openMenuForm());
			return;
		} else {
			$sender->sendMessage("Only In-Game!");
			return;
		}
	}


	/**
	 * @return Plugin
	 */
	public function getPlugin() : Plugin {
		return BetterBan::getInstance();
	}
}