<?php

namespace supercrafter333\BetterBan\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use supercrafter333\BetterBan\BetterBan;

/**
 * Class BBEditbanEvent
 * @package supercrafter333\BetterBan\Events
 */
class BBEditbanEvent extends Event implements Cancellable
{
    use CancellableTrait;

    /**
     * @var string
     */
    protected $target;

    /**
     * BBEditbanEvent constructor.
     * @param string $target
     */
    public function __construct(string $target)
    {
        $this->eventName = "BBEditbanEvent";
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param string $target
     */
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