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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBPardonIpEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;
use function count;
use function inet_pton;

/**
 * Class PardonIpCommand
 * @package supercrafter333\BetterBan\Commands
 */
class PardonIpCommand extends BetterBanOwnedCommand {
	/**
	 * PardonIpCommand constructor.
	 * @param string $name
	 */
	public function __construct(string $name) {
		parent::__construct(
			$name,
			KnownTranslationFactory::pocketmine_command_unban_ip_description(),
			KnownTranslationFactory::commands_unbanip_usage(),
			["unban-ip"]
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_UNBAN_IP);
	}


	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * 
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		if (!$this->testPermission($sender)) {
			return true;
		}
		if (empty($args) && $sender instanceof Player) {
			$sender->sendForm(BBDefaultForms::pardonIpForm());
			return true;
		}
		if (count($args) !== 1) {
			throw new InvalidCommandSyntaxException();
		}
		$ev = new BBPardonIpEvent($args[0], $sender->getName());
		$ev->call();
		if ($ev->isCancelled()) {
			Command::broadcastCommandMessage($sender, "Unban cancelled because the BBPardonIpEvent is cancelled!", true);
			return true;
		}

		if (inet_pton($args[0]) !== false) {
			$pl = BetterBan::getInstance();
			if ($pl->useMySQL()) {
				$pl->getMySQLIpBans()->remove($args[0]);
			} else {
				$pl->getServer()->getIpBans()->remove($args[0]);
			}
			$sender->getServer()->getNetwork()->unblockAddress($args[0]);
			Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_unbanip_success($args[0]));
		} else {
			$sender->sendMessage(KnownTranslationFactory::commands_unbanip_invalid());
		}
		return true;
	}


	/**
	 * @return Plugin
	 */
	public function getPlugin() : Plugin {
		return BetterBan::getInstance();
	}
}