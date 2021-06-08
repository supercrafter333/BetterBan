<?php

namespace supercrafter333\BetterBan\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use supercrafter333\BetterBan\BetterBan;

class BBEditbanEvent extends Event implements Cancellable
{

    protected $target;

    public function __construct(string $target)
    {
        $this->eventName = "BBEditbanEvent";
        $this->target = $target;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): void
    {
        $this->target = $target;
    }

    /**
     * Send the Discord-Webhook Message
     */
    public function sendDiscordWebhookMessage(): void
    {
        BetterBan::getInstance()->sendBanUpdatedMessageToDC($this->target);
    }
}