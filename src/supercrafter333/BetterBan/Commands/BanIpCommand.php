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
use supercrafter333\BetterBan\Events\BBBanIpEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;
use function array_shift;
use function count;
use function implode;
use function inet_pton;
use function str_replace;

/**
 * Class BanIpCommand
 * @package supercrafter333\BetterBan\Commands
 */
class BanIpCommand extends BetterBanOwnedCommand {
	
	/**
	 * @var BetterBan
	 */
	private BetterBan $pl;


	/**
	 * BanIpCommand constructor.
	 * @param string $name
	 * @param string $description
	 * @param string|null $usageMessage
	 * @param array $aliases
	 */
	public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = []) {
		$this->pl = BetterBan::getInstance();
		parent::__construct($name, KnownTranslationFactory::pocketmine_command_ban_ip_description(), "§4Use:§r /banip <ip-address> [reason: ...] [date interval: ...]", ["ban-ip"]);
		$this->setPermission(DefaultPermissionNames::COMMAND_BAN_IP);
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * 
	 * @throws \Exception
	 * 
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		if (!$this->testPermission($sender)) {
			return true;
		}

		if (empty($args) && $sender instanceof Player) {
			$sender->sendForm(BBDefaultForms::banIpForm());
			return true;
		}

		if (count($args) === 0) {
			throw new InvalidCommandSyntaxException();
		}

		$value = array_shift($args);
		if (count($args) == 2) {
			$reason = $args[0];
			$expires = null;
		} else {
			$reason = null;
			$expires = null;
		}
		if (count($args) >= 3) {
			$expiresRaw = BetterBan::getInstance()->stringToTimestamp(implode(" ", $args));
			$expires = isset($expiresRaw[0]) == false ? null : $expiresRaw[0];
		} else {
			$expires = null;
		}

		if (inet_pton($value) !== false) {
			$ev = new BBBanIpEvent($value, $sender->getName(), $reason);
			$ev->call();
			if ($ev->isCancelled()) {
				Command::broadcastCommandMessage($sender, "Ip-Ban cancelled because the BBBanIpEvent is cancelled!", true);
				return true;
			}
			$this->processIPBan($value, $sender, $reason, $expires);

			Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_banip_success($value), true);
		} else {
			if (($player = $sender->getServer()->getPlayerExact($value)) instanceof Player) {
				$this->processIPBan($player->getNetworkSession()->getIp(), $sender, $reason, $expires);

				Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_banip_success_players((string) $player->getNetworkSession()->getIp(), $player->getName()), true);
			} else {
				$sender->sendMessage(KnownTranslationFactory::commands_banip_invalid());

				return false;
			}
		}

		return true;
	}


	/**
	 * @param string $ip
	 * @param CommandSender $sender
	 * @param string|null $reason
	 * @param \DateTime|null $expires
	 * 
	 * @return void
	 */
	private function processIPBan(string $ip, CommandSender $sender, string $reason = null, \DateTime $expires = null) : void {
		if ($this->pl->useMySQL()) {
			$this->pl->getMySQLIpBans()->addBan($ip, $reason, $expires, $sender->getName());
		} else {
			$sender->getServer()->getIPBans()->addBan($ip, $reason, $expires, $sender->getName());
		}

		foreach ($sender->getServer()->getOnlinePlayers() as $player) {
			if ($player->getNetworkSession()->getIp() === $ip) {
				$cfg = BetterBan::getInstance()->getConfig();
				BetterBan::getInstance()->addBanToBanlog($player->getName());
				if ($reason == null) {
					$player->kick(str_replace(["{line}"], ["\n"], $cfg->get("kick-ip-message")));
				} elseif ($expires == null) {
					$player->kick(str_replace(["{reason}", "{line}"], [$reason, "\n"], $cfg->get("kick-ip-message-with-reason")));
				} else {
					$player->kick(str_replace(["{reason}", "{time}", "{line}"], [$reason, BetterBan::getInstance()->toPrettyFormat($expires, BetterBan::getInstance()->getConfig()->get("use-legacy-format", false)), "\n"], $cfg->get("kick-ip-message-with-time")));
				}
			}
		}
		$sender->getServer()->getNetwork()->blockAddress($ip, -1);
	}



	/**
	 * @return Plugin
	 */
	public function getPlugin() : Plugin {
		return $this->pl;
	}
}