<?php

namespace supercrafter333\BetterBan\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use supercrafter333\BetterBan\BetterBan;

/**
 * Class BBPardonEvent
 * @package supercrafter333\BetterBan\Events
 */
class BBPardonEvent extends Event implements Cancellable
{
    use CancellableTrait;

    /**
     * @var string
     */
    private $target;

    /**
     * @var string
     */
    private $source;

    /**
     * BBPardonEvent constructor.
     * @param string $target
     * @param string $source
     */
    public function __construct(string $target, string $source)
    {
        $this->eventName = "BBPardonEvent";
        $this->target = $target;
        $this->source = $source;
    }

    /**
     * Get the target
     *
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     *
     * Get the source
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Set the source
     *
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    /**
     * Set the target
     *
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
        BetterBan::getInstance()->sendPardonMessageToDC($this->target, $this->source);
    }
}