<?php

namespace supercrafter333\BetterBan\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use supercrafter333\BetterBan\BetterBan;

/**
 * Class BBPardonIpEvent
 * @package supercrafter333\BetterBan\Events
 */
class BBPardonIpEvent extends Event implements Cancellable
{
    use CancellableTrait;

    /**
     * BBPardonIpEvent constructor.
     * @param string $IpAddress
     * @param string $source
     */
    public function __construct(private string $IpAddress, private string $source)
    {
        $this->eventName = "BBPardonIpEvent";
    }

    /**
     * Get the IpAddress
     *
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->IpAddress;
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
     * Set the IpAddress
     *
     * @param string $IpAddress
     */
    public function setIpAddress(string $IpAddress): void
    {
        $this->IpAddress = $IpAddress;
    }

    /**
     * Send the Discord-Webhook Message
     */
    public function sendDiscordWebhookMessage(): void
    {
        BetterBan::getInstance()->sendPardonIpMessageToDC($this->IpAddress, $this->source);
    }
}