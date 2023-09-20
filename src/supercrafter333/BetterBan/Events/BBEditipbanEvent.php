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

namespace supercrafter333\BetterBan\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use supercrafter333\BetterBan\BetterBan;

/**
 * Class BBEditipbanEvent
 * @package supercrafter333\BetterBan\Events
 */
class BBEditipbanEvent extends Event implements Cancellable {
	use CancellableTrait;

	/**
	 * BBEditipbanEvent constructor.
	 * @param string $ipAddress
	 */
	public function __construct(private string $IpAddress) {
		$this->eventName = "BBEditipbanEvent";
	}


	/**
	 * @return string
	 */
	public function getIpAddress() : string {
		return $this->IpAddress;
	}


	/**
	 * @param string $IpAddress
	 * 
	 * @return void
	 */
	public function setIpAddress(string $IpAddress) : void {
		$this->IpAddress = $IpAddress;
	}

	/**
	 * Send the Discord-Webhook Message
	 * @return void
	 */
	public function sendDiscordWebhookMessage() : void {
		BetterBan::getInstance()->sendIpBanUpdatedMessageToDC($this->IpAddress);
	}
}