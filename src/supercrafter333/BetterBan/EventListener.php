<?php

namespace supercrafter333\BetterBan;

use pocketmine\event\Listener;
use supercrafter333\BetterBan\Events\BBBanEvent;
use supercrafter333\BetterBan\Events\BBEditbanEvent;

class EventListener implements Listener
{

    public function onBBBan(BBBanEvent $event)
    {
        if ($event->isCancelled()) return;
        $event->sendDiscordWebhookMessage();
    }

    public function onBBEditban(BBEditbanEvent $event)
    {
        if ($event->isCancelled()) return;
        $event->sendDiscordWebhookMessage();
    }
}