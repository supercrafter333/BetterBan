<?php

namespace supercrafter333\BetterBan\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use supercrafter333\BetterBan\BetterBan;

class BBEditipbanEvent extends Event implements Cancellable
{

    protected $IpAdress;

    public function __construct(string $IpAdress)
    {
        $this->eventName = "BBEditipbanEvent";
        $this->IpAdress = $IpAdress;
    }

    public function getIpAdress(): string
    {
        return $this->IpAdress;
    }

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