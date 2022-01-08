<?php

namespace supercrafter333\BetterBan\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use supercrafter333\BetterBan\BetterBan;

/**
 * Class BBBanIpEvent
 * @package supercrafter333\BetterBan\Events
 */
class BBBanIpEvent extends Event implements Cancellable
{
    use CancellableTrait;

    /**
     * BBBanEvent constructor.
     * @param string $IpAddress
     * @param string $source
     * @param string|null $reason
     */
    public function __construct(private string $IpAddress, private string $source, private string|null $reason = null)
    {
        $this->eventName = "BBBanIpEvent";
    }

    /**
     * Get the IpAddress Player-Name of the ban
     *
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->IpAddress;
    }

    /**
     * Get the Source Name of the ban
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get the Reason of the ban
     *
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Set the IpAddress Player-Name of the Ban
     *
     * @param string $IpAddress
     */
    public function setIpAddress(string $IpAddress): void
    {
        $this->IpAddress = $IpAddress;
    }

    /**
     * Set the Source Player-Name of the Ban
     *
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    /**
     * Set the Reason of the Ban
     *
     * @param string|null $reason
     */
    public function setReason(string $reason = null): void
    {
        $this->reason = $reason;
    }

    /**
     * Send the Discord-Webhook Message
     */
    public function sendDiscordWebhookMessage(): void
    {
        $reason = $this->reason === null ?? "";
        BetterBan::getInstance()->sendIpBanMessageToDC($this->IpAddress, $this->source, $reason);
    }
}