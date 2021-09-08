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
     * @var string
     */
    protected $IpAdress;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string|null
     */
    protected $reason;

    /**
     * BBBanEvent constructor.
     * @param string $IpAdress
     * @param string $source
     * @param string|null $reason
     */
    public function __construct(string $IpAdress, string $source, string $reason = null)
    {
        $this->eventName = "BBBanIpEvent";
        $this->IpAdress = $IpAdress;
        $this->source = $source;
        $this->reason = $reason;
    }

    /**
     * Get the IpAdress Player-Name of the ban
     *
     * @return string
     */
    public function getIpAdress(): string
    {
        return $this->IpAdress;
    }

    /**
     * Get the Source Name of the ban
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->IpAdress;
    }

    /**
     * Get the Reason of the ban
     *
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->IpAdress;
    }

    /**
     * Set the IpAdress Player-Name of the Ban
     *
     * @param string $IpAdress
     */
    public function setIpAdress(string $IpAdress): void
    {
        $this->IpAdress = $IpAdress;
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
        BetterBan::getInstance()->sendIpBanMessageToDC($this->IpAdress, $this->source, $reason);
    }
}