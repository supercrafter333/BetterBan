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
use pocketmine\player\Player;
use supercrafter333\BetterBan\BetterBan;
use function str_replace;

/**
 * Class BBKickEvent
 * @package supercrafter333\BetterBan\Events
 */
class BBKickEvent extends Event implements Cancellable {
	use CancellableTrait;

	/**
	 * BBKickEvent constructor.
	 * @param Player $target
	 * @param string $source
	 * @param string|null $reason
	 */
	public function __construct(private Player $target, private string $source, private string|null $reason = null) {
		$this->eventName = "BBKickEvent";
	}


	/**
	 * @return Player
	 */
	public function getTarget() : Player {
		return $this->target;
	}


	/**
	 * @return string
	 */
	public function getTargetName() : string {
		return $this->target->getName();
	}


	/**
	 * @return string
	 */
	public function getSource() : string {
		return $this->source;
	}

	/**
	 * @return string
	 */
	public function getReason() : ?string {
		return $this->reason;
	}


	/**
	 * @param Player $target
	 * 
	 * @return void
	 */
	public function setTarget(Player $target) : void {
		$this->target = $target;
	}


	/**
	 * @param string $source
	 * 
	 * @return void
	 */
	public function setSource(string $source) : void {
		$this->source = $source;
	}


	/**
	 * @param string $reason
	 * 
	 * @return void
	 */
	public function setReason(string $reason) : void {
		$this->reason = $reason;
	}

	/**
	 * Kick the Target Player
	 * @return void
	 */
	public function kickTarget() : void {
		if (!$this->isCancelled()) {
			$cfg = BetterBan::getInstance()->getConfig();
			if ($this->reason !== null) {
				$this->target->kick(str_replace(["{reason}", "{source}", "{line}"], [$this->reason, $this->source, "\n"], $cfg->get("kicked-with-reason-message")));
				return;
			} else {
				$this->target->kick(str_replace(["{source}", "{line}"], [$this->source, "\n"], $cfg->get("kicked-message")));
				return;
			}
		} else {
			BetterBan::getInstance()->getLogger()->warning("Can't kick " . $this->getTargetName() . " because the kick event is cancelled!");
			return;
		}
	}

	/**
	 * Send the Discord-Webhook Message
	 * @return void
	 */
	public function sendDiscordWebhookMessage() : void {
		BetterBan::getInstance()->sendKickMessageToDC($this->getTargetName(), $this->source);
	}
}