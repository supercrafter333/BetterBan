<?php

namespace supercrafter333\BetterBan\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use supercrafter333\BetterBan\BetterBan;

class BBPardonIpEvent extends Event implements Cancellable
{
    
    private $IpAddress;

    private $source;

    public function __construct(string $IpAddress, string $source)
    {
        $this->eventName = "BBPardonIpEvent";
        $this->IpAddress = $IpAddress;
        $this->source = $source;
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