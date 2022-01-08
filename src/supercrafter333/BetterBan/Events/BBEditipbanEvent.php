<?php

namespace supercrafter333\BetterBan\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use supercrafter333\BetterBan\BetterBan;

/**
 * Class BBEditipbanEvent
 * @package supercrafter333\BetterBan\Events
 */
class BBEditipbanEvent extends Event implements Cancellable
{
    use CancellableTrait;

    /**
     * BBEditipbanEvent constructor.
     * @param string $IpAddress
     */
    public function __construct(private string $IpAddress)
    {
        $this->eventName = "BBEditipbanEvent";
    }

    /**
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->IpAddress;
    }

    /**
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
        BetterBan::getInstance()->sendIpBanUpdatedMessageToDC($this->IpAddress);
    }
}