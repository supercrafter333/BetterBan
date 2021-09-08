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
     * @var string
     */
    protected $IpAdress;

    /**
     * BBEditipbanEvent constructor.
     * @param string $IpAdress
     */
    public function __construct(string $IpAdress)
    {
        $this->eventName = "BBEditipbanEvent";
        $this->IpAdress = $IpAdress;
    }

    /**
     * @return string
     */
    public function getIpAdress(): string
    {
        return $this->IpAdress;
    }

    /**
     * @param string $IpAdress
     */
    public function setIpAdress(string $IpAdress): void
    {
        $this->IpAdress = $IpAdress;
    }

    /**
     * Send the Discord-Webhook Message
     */
    public function sendDiscordWebhookMessage(): void
    {
        BetterBan::getInstance()->sendIpBanUpdatedMessageToDC($this->IpAdress);
    }
}