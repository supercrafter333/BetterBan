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
use pocketmine\utils\TextFormat;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBKickEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;
use function array_shift;
use function count;
use function implode;
use function trim;

class KickCommand extends BetterBanOwnedCommand {
	/**
	 * @param string $name
	 */
	public function __construct(string $name) {
		parent::__construct(
			$name,
			KnownTranslationFactory::pocketmine_command_kick_description(),
			KnownTranslationFactory::commands_kick_usage()
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_KICK);
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
			$sender->sendForm(BBDefaultForms::kickForm());
			return true;
		}

		if (count($args) === 0) {
			throw new InvalidCommandSyntaxException();
		}

		$name = array_shift($args);
		$reason = trim(implode(" ", $args));

		if (($player = $sender->getServer()->getPlayerExact($name)) instanceof Player) {
			$newReason = $reason == "" ? null : $reason;
			$ev = new BBKickEvent($player, $sender->getName(), $newReason);
			$ev->call();
			if ($ev->isCancelled()) {
				Command::broadcastCommandMessage($sender, "Kick cancelled because the BBKickEvent is cancelled!", true);
				return true;
			}
			if (!$ev->isCancelled()) {
				$ev->kickTarget();
			}
			if ($reason !== "") {
				Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_kick_success_reason($player->getName(), $reason));
			} else {
				Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_kick_success($player->getName()));
			}
		} else {
			$sender->sendMessage(KnownTranslationFactory::commands_generic_player_notFound()->prefix(TextFormat::RED));
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