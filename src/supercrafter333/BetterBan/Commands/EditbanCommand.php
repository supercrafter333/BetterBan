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
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBEditbanEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;
use function count;
use function str_replace;

/**
 * Class EditbanCommand
 * @package supercrafter333\BetterBan\Commands
 */
class EditbanCommand extends BetterBanOwnedCommand {
	
	/**
	 * @var BetterBan
	 */
	private BetterBan $pl;

	/**
	 * EditbanCommand constructor.
	 * @param string $name
	 * @param string $description
	 * @param string|null $usageMessage
	 * @param array $aliases
	 */
	public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = []) {
		$this->pl = BetterBan::getInstance();
		$this->setPermission("BetterBan.editban.cmd");
		parent::__construct("editban", "Add or reduce the time of a ban", "§4Use:§r /editban <player> <addbantime|reducebantime> <time>", $aliases);
	}

	/**
	 * @param CommandSender $s
	 * @param string $commandLabel
	 * @param array $args
	 * 
	 * @throws \Exception
	 * 
	 * @return void
	 */
	public function execute(CommandSender $s, string $commandLabel, array $args) : void {
		if (!$this->testPermission($s)) {
			return;
		}
		$plugin = $this->pl;
		$cfg = $plugin->getConfig();
		if (empty($args) && $s instanceof Player) {
			$s->sendForm(BBDefaultForms::editbanForm());
			return;
		}
		if (count($args) < 3) {
			$s->sendMessage($this->usageMessage);
			return;
		}
		$server = $plugin->getServer();
		if (!$server->getNameBans()->isBanned($args[0])) {
			$s->sendMessage(str_replace(["{name}"], [(string) $args[0]], $cfg->get("error-not-banned")));
			return;
		}
		$playerName = (string) $args[0];
		if ($args[1] !== "addbantime" && $args[1] !== "reducebantime") {
			$s->sendMessage($this->usageMessage);
			return;
		}

		$ban = $plugin->useMySQL() ? $plugin->getMySQLNameBans()->getEntry($args[0]) : $server->getNameBans()->getEntry($args[0]);
		$oldDate = $ban->getExpires();
		if ($oldDate === null) {
			$s->sendMessage(str_replace(["{name}"], [(string) $args[0]], $cfg->get("error-no-tempban-found")));
			return;
		}
		$nameBans = $plugin->useMySQL() ? $plugin->getMySQLNameBans() : $server->getNameBans();
		$option = $args[1];
		$ebEvent = new BBEditbanEvent($playerName);
		$ebEvent->call();
		if ($ebEvent->isCancelled()) {
			Command::broadcastCommandMessage($s, "Ban editing cancelled because the BBEditbanEvent is cancelled!", true);
			return;
		}
		if ($option === "addbantime") {
			$information = $plugin->stringToTimestampAdd($args[2], $oldDate);
			$date = $information[0];
			$newDate = $date;
			$ban->setExpires($newDate);
			if ($plugin->useMySQL()) {
				$nameBans->add($ban);
			} else {
				$nameBans->save(true);
			}
			if (!$s->getServer()->isOp($s->getName())) {
				$s->sendMessage("§7§o[Added time to ban: " . $playerName . " +" . $args[2] . "]");
			}
			$server->getLogger()->info("§7§o[Added time to ban: " . $playerName . " +" . $args[2] . "]");
			foreach ($plugin->getServer()->getOps() as $ops) {
				$op = $plugin->getServer()->getPlayerExact($ops);
				if ($op instanceof Player) {
					$op->sendMessage("§7§o[Added time to ban: " . $playerName . " +" . $args[2] . "]");
				}
			}
			return;
		}
		if ($option === "reducebantime") {
			$information = $plugin->stringToTimestampReduce($args[2], $oldDate);
			$date = $information[0];
			$newDate = $date;
			//$clipboard = ["time" => $ban->getExpires(), "reason" => $ban->getReason(), "source" => $ban->getSource(), "name" => $ban->getName(), "created" => $ban->getCreated()];
			$ban->setExpires($newDate);
			if ($plugin->useMySQL()) {
				$nameBans->add($ban);
			} else {
				$nameBans->save(true);
			}
			$server->getNameBans()->save(true);
			if (!$s->getServer()->isOp($s->getName())) {
				$s->sendMessage("§7§o[Reduced time for ban: " . $playerName . " -" . $args[2] . "]");
			}
			$server->getLogger()->info("§7§o[Reduced time for ban: " . $playerName . " -" . $args[2] . "]");
			foreach ($plugin->getServer()->getOps() as $ops) {
				$op = $plugin->getServer()->getPlayerExact($ops);
				if ($op instanceof Player) {
					$op->sendMessage("§7§o[Reduced time for ban: " . $playerName . " -" . $args[2] . "]");
				}
			}
			return;
		}
	}


	/**
	 * @return Plugin
	 */
	public function getPlugin() : Plugin {
		return $this->pl;
	}
}