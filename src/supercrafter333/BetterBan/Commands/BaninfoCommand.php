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
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;
use function implode;
use function str_replace;

/**
 * Class BaninfoCommand
 * @package supercrafter333\BetterBan\Commands
 * @method testPermission(CommandSender $s)
 */
class BaninfoCommand extends BetterBanOwnedCommand {
	/** @var BetterBan */
	private $pl;

	/**
	 * BaninfoCommand constructor.
	 * @param string $name
	 * @param string $description
	 * @param string|null $usageMessage
	 * @param array $aliases
	 */
	public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = []) {
		$this->pl = BetterBan::getInstance();
		$this->setPermission('BetterBan.baninfo.cmd');
		parent::__construct("baninfo", "See the ban-informations of a banned player", "§4Use:§r /baninfo <player>", ["baninformation"]);
	}


	/**
	 * @param CommandSender $s
	 * @param string $commandLabel
	 * @param array $args
	 * 
	 * @return void
	 */
	public function execute(CommandSender $s, string $commandLabel, array $args) : void {
		if (!$this->testPermission($s)) {
			return;
		}
		if (empty($args)) {
			$s->sendMessage($this->usageMessage);
			return;
		}
		$pl = $this->pl;
		$name = implode(" ", $args);
		$server = $pl->getServer();
		$nameBans = $pl->useMySQL() ? $pl->getMySQLNameBans() : $server->getNameBans();
		if ($nameBans->getEntry($name) === null) {
			//$s->sendMessage(str_replace(["{name}"], [$name], $pl->getConfig()->get("error-not-banned")));
			$s->sendMessage(str_replace(["{name}", "{log}", "{line}"], [$name, (string) $pl->getBanLogOf($name), "\n"], $pl->getConfig()->get("baninfo-not-banned")));
			return;
		}
		$ban = $nameBans->getEntry($name);
		$source = $ban->getSource() === "(Unknown)" ? "§8---" : $ban->getSource();
		$date = $ban->hasExpired() ? $pl->toPrettyFormat($ban->getExpires(), $pl->getConfig()->get("use-legacy-format", false)) : "§8---";
		$reason = $ban->getReason();
		$log = $pl->getBanLogOf($name);
		$s->sendMessage(str_replace(["{name}", "{source}", "{date}", "{reason}", "{log}", "{line}"], [$name, $source, $date, $reason, (string) $log, "\n"], $pl->getConfig()->get("baninfo-message-list")));
		return;
	}


	/**
	 * @return Plugin
	 */
	public function getPlugin() : Plugin {
		return $this->pl;
	}
}