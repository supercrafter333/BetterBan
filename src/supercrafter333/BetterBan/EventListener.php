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

namespace supercrafter333\BetterBan;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use supercrafter333\BetterBan\Events\BBBanEvent;
use supercrafter333\BetterBan\Events\BBBanIpEvent;
use supercrafter333\BetterBan\Events\BBEditbanEvent;
use supercrafter333\BetterBan\Events\BBEditipbanEvent;
use supercrafter333\BetterBan\Events\BBKickEvent;
use supercrafter333\BetterBan\Events\BBPardonEvent;
use supercrafter333\BetterBan\Events\BBPardonIpEvent;
use function str_replace;


/**
 * BetterBan EventListener
 */
class EventListener implements Listener {
	/**
	 * @param BBBanEvent $event
	 * 
	 * @return void
	 */
	public function onBBBan(BBBanEvent $event) : void {
		if ($event->isCancelled()) {
			return;
		}
		$event->sendDiscordWebhookMessage();
	}


	/**
	 * @param BBEditbanEvent $event
	 * 
	 * @return void
	 */
	public function onBBEditban(BBEditbanEvent $event) : void {
		if ($event->isCancelled()) {
			return;
		}
		$event->sendDiscordWebhookMessage();
	}


	/**
	 * @param BBPardonEvent $event
	 * 
	 * @return void
	 */
	public function onBBPardon(BBPardonEvent $event) : void {
		if ($event->isCancelled()) {
			return;
		}
		$event->sendDiscordWebhookMessage();
	}


	/**
	 * @param BBBanIpEvent $event
	 * 
	 * @return void
	 */
	public function onBBIpBan(BBBanIpEvent $event) : void {
		if ($event->isCancelled()) {
			return;
		}
		$event->sendDiscordWebhookMessage();
	}


	/**
	 * @param BBEditipbanEvent $event
	 * 
	 * @return void
	 */
	public function onBBEditIpBan(BBEditipbanEvent $event) : void {
		if ($event->isCancelled()) {
			return;
		}
		$event->sendDiscordWebhookMessage();
	}


	/**
	 * @param BBPardonIpEvent $event
	 * 
	 * @return void
	 */
	public function onBBPardonIp(BBPardonIpEvent $event) : void {
		if ($event->isCancelled()) {
			return;
		}
		$event->sendDiscordWebhookMessage();
	}


	/**
	 * @param BBKickEvent $event
	 * 
	 * @return void
	 */
	public function onBBKick(BBKickEvent $event) : void {
		if ($event->isCancelled()) {
			return;
		}
		$event->sendDiscordWebhookMessage();
	}


	/**
	 * @param PlayerPreLoginEvent $event
	 * 
	 * @return void
	 */
	public function onPreLogin(PlayerPreLoginEvent $event) : void {
		$playerInfo = $event->getPlayerInfo();
		if (BetterBan::isBanned($playerInfo->getUsername())) {
			$pl = BetterBan::getInstance();
			$entry = $pl->useMySQL() ? $pl->getMySQLNameBans()->getEntry($playerInfo->getUsername()) : $pl->getServer()->getNameBans()->getEntry($playerInfo->getUsername());
			$reason = str_replace(["{source}", "{expires}", "{reason}", "{line}"], [$entry->getSource(), $entry->getExpires() !== null ? BetterBan::getInstance()->toPrettyFormat($entry->getExpires(), BetterBan::getInstance()->getConfig("use-legacy-format", false)) : "Never", $entry->getReason(), "\n"], BetterBan::getInstance()->getConfig()->get("you-are-banned-logout"));
			$event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_BANNED, $reason);
		}
	}


	/**
	 * @param PlayerLoginEvent $event
	 * 
	 * @return void
	 */
	public function onLogin(PlayerLoginEvent $event) : void {
		$player = $event->getPlayer();
		if (BetterBan::isBanned($player->getName())) {
			$pl = BetterBan::getInstance();
			$entry = $pl->useMySQL() ? $pl->getMySQLNameBans()->getEntry($player->getName()) : $pl->getServer()->getNameBans()->getEntry($player->getName());
			$reason = str_replace(["{source}", "{expires}", "{reason}", "{line}"], [$entry->getSource(), $entry->getExpires() !== null ? BetterBan::getInstance()->toPrettyFormat($entry->getExpires(), BetterBan::getInstance()->getConfig("use-legacy-format", false)) : "Never", $entry->getReason(), "\n"], BetterBan::getInstance()->getConfig()->get("you-are-banned-logout"));
			$player->kick($reason);
			$event->cancel();
		}
	}
}