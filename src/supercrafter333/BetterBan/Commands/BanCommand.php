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
use supercrafter333\BetterBan\Events\BBBanEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;
use function array_shift;
use function count;
use function implode;
use function str_replace;

/**
 * Class BanCommand
 * @package supercrafter333\BetterBan\Commands
 */
class BanCommand extends BetterBanOwnedCommand {
	private BetterBan $pl;

	/**
	 * BanCommand constructor.
	 * @param string $name
	 * @param string $description
	 * @param string|null $usageMessage
	 * @param array $aliases
	 */
	public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = []) {
		$this->pl = BetterBan::getInstance();
		parent::__construct($name, KnownTranslationFactory::pocketmine_command_ban_player_description(), "§4Use:§r /ban <name> [reason: ...] [date interval: ...]");
		$this->setPermission(DefaultPermissionNames::COMMAND_BAN_PLAYER);
	}


	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * 
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		$pl = BetterBan::getInstance();
		$cfg = $pl->getConfig();
		if (!$this->testPermission($sender)) {
			return true;
		}

		if (empty($args) && $sender instanceof Player) {
			$sender->sendForm(BBDefaultForms::banForm());
			return true;
		}

		if (count($args) < 1) {
			throw new InvalidCommandSyntaxException();
		}

		if (count($args) == 2 || count($args) == 1) {
			$name = array_shift($args);
			$reason = isset($args[0]) ? (string) $args[0] : "";

			$banEvent = new BBBanEvent($name, $sender->getName());
			$banEvent->call();
			if ($banEvent->isCancelled()) {
				Command::broadcastCommandMessage($sender, "Ban cancelled because the BBBanEvent is cancelled!", true);
				return true;
			}
			$pl->addBanToBanlog($name);
			if ($pl->useMySQL()) {
				$pl->getMySQLNameBans()->addBan($name, $reason, null, $sender->getName());
			} else {
				$sender->getServer()->getNameBans()->addBan($name, $reason, null, $sender->getName());
			}
			if (($player = $sender->getServer()->getPlayerExact($name)) instanceof Player) {
				$player->kick($reason !== "" ? str_replace(["{reason}", "{line}"], [(string) $args[0], "\n"], $cfg->get("kick-message-with-reason")) : $cfg->get("kick-message"));
				Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_ban_success($player !== null ? $player->getName() : $name));
				$sender->sendMessage("Banned!");
				$reason2 = $reason === "" ?? null;
			}
		} elseif (count($args) >= 3) {
			$name = array_shift($args);
			$reason = isset($args[0]) ? (string) $args[0] : "";
			if (!$pl->stringToTimestamp(implode(" ", $args))) {
				$sender->sendMessage($cfg->get("use-DateInterval-format"));
				return true;
			}

			$informations = $pl->stringToTimestamp(implode(" ", $args));
			$bantime = $informations[0];
			//if ($args[1] instanceof DateInterval) {
			$banEvent = new BBBanEvent($sender->getName(), $name, $reason);
			$banEvent->call();
			if ($banEvent->isCancelled()) {
				Command::broadcastCommandMessage($sender, "Ban cancelled because the BBBanEvent is cancelled!", true);
				return true;
			}
			$pl->addBanToBanlog($name);
			if ($pl->useMySQL()) {
				$pl->getMySQLNameBans()->addBan($name, $reason, $bantime, $sender->getName());
			} else {
				$sender->getServer()->getNameBans()->addBan($name, $reason, $bantime, $sender->getName());
			}
			if (($player = $sender->getServer()->getPlayerExact($name)) instanceof Player) {
				$player->kick($reason !== "" ? str_replace(["{reason}", "{time}", "{line}"], [(string) $args[0], $pl->toPrettyFormat($bantime, $pl->getConfig()->get("use-legacy-format", false)), "\n"], $cfg->get("kick-message-with-time")) : $cfg->get("kick-message"));
				Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_ban_success($player !== null ? $player->getName() : $name));
				$sender->sendMessage("[Time] Banned!");
				$reason2 = $reason === "" ?? null;
			}
		} else {
			$sender->sendMessage($this->usageMessage);
		}
		return true;
	}


	/**
	 * @return Plugin
	 */
	public function getPlugin() : Plugin {
		return $this->pl;
	}
}