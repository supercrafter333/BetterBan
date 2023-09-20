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
 * Class BBBanEvent
 * @package supercrafter333\BetterBan\Events
 */
class BBBanEvent extends Event implements Cancellable {
	use CancellableTrait;

	/**
	 * BBBanEvent constructor.
	 * @param string|null $reason
	 */
	public function __construct(private string $target, private string $source, private string|null $reason = null) {
		$this->eventName = "BBBanEvent";
	}

	/**
	 * Get the Target Player-Name of the ban
	 * @return string
	 */
	public function getTarget() : string {
		return $this->target;
	}

	/**
	 * Get the Source Name of the ban
	 * @return string
	 */
	public function getSource() : string {
		return $this->target;
	}

	/**
	 * Get the Reason of the ban
	 * @return string|null
	 */
	public function getReason() : ?string {
		return $this->target;
	}

	/**
	 * Set the Target Player-Name of the Ban
	 * @param string $target
	 * 
	 * @return void
	 */
	public function setTarget(string $target) : void {
		$this->target = $target;
	}

	/**
	 * Set the Source Player-Name of the Ban
	 * @param string $source
	 * 
	 * @return void
	 */
	public function setSource(string $source) : void {
		$this->source = $source;
	}

	/**
	 * Set the Reason of the Ban
	 * @param string|null $reason
	 * 
	 * @return void
	 */
	public function setReason(string $reason = null) : void {
		$this->reason = $reason;
	}

	/**
	 * Send the Discord-Webhook Message
	 * @return void
	 */
	public function sendDiscordWebhookMessage() : void {
		$reason = $this->reason === null ?? "";
		BetterBan::getInstance()->sendBanMessageToDC($this->target, $this->source, $reason);
	}
}