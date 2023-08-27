<?php

namespace supercrafter333\BetterBan;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use supercrafter333\BetterBan\Events\BBBanEvent;
use supercrafter333\BetterBan\Events\BBBanIpEvent;
use supercrafter333\BetterBan\Events\BBEditbanEvent;
use supercrafter333\BetterBan\Events\BBEditipbanEvent;
use supercrafter333\BetterBan\Events\BBKickEvent;
use supercrafter333\BetterBan\Events\BBPardonEvent;
use supercrafter333\BetterBan\Events\BBPardonIpEvent;

/**
 *
 */
class EventListener implements Listener
{

    /**
     * @param BBBanEvent $event
     */
    public function onBBBan(BBBanEvent $event): void
    {
        if ($event->isCancelled()) return;
        $event->sendDiscordWebhookMessage();
    }

    /**
     * @param BBEditbanEvent $event
     */
    public function onBBEditban(BBEditbanEvent $event): void
    {
        if ($event->isCancelled()) return;
        $event->sendDiscordWebhookMessage();
    }

    /**
     * @param BBPardonEvent $event
     */
    public function onBBPardon(BBPardonEvent $event): void
    {
        if ($event->isCancelled()) return;
        $event->sendDiscordWebhookMessage();
    }

    /**
     * @param BBBanIpEvent $event
     */
    public function onBBIpBan(BBBanIpEvent $event): void
    {
        if ($event->isCancelled()) return;
        $event->sendDiscordWebhookMessage();
    }

    /**
     * @param BBEditipbanEvent $event
     */
    public function onBBEditIpBan(BBEditipbanEvent $event): void
    {
        if ($event->isCancelled()) return;
        $event->sendDiscordWebhookMessage();
    }

    /**
     * @param BBPardonIpEvent $event
     */
    public function onBBPardonIp(BBPardonIpEvent $event): void
    {
        if ($event->isCancelled()) return;
        $event->sendDiscordWebhookMessage();
    }

    /**
     * @param BBKickEvent $event
     */
    public function onBBKick(BBKickEvent $event): void
    {
        if ($event->isCancelled()) return;
        $event->sendDiscordWebhookMessage();
    }

    /**
     * @param PlayerPreLoginEvent $event
     * @return void
     */
    public function onPreLogin(PlayerPreLoginEvent $event): void
    {
        $playerInfo = $event->getPlayerInfo();
        if (BetterBan::isBanned($playerInfo->getUsername())) {
            $pl = BetterBan::getInstance();
            $entry = $pl->useMySQL() ? $pl->getMySQLNameBans()->getEntry($playerInfo->getUsername()) : $pl->getServer()->getNameBans()->getEntry($playerInfo->getUsername());
            $reason = str_replace(["{source}", "{expires}", "{reason}", "{line}"], [$entry->getSource(), $entry->getExpires() !== null ? BetterBan::getInstance()->toPrettyFormat($entry->getExpires(), (BetterBan::getInstance()->getConfig("use-legacy-format") ?? false)) : "Never", $entry->getReason(), "\n"], BetterBan::getInstance()->getConfig()->get("you-are-banned-logout"));
            $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_BANNED, $reason);
        }
    }

    /**
     * @param PlayerLoginEvent $event
     */
    public function onLogin(PlayerLoginEvent $event): void
    {
        $player = $event->getPlayer();
        if (BetterBan::isBanned($player->getName())) {
            $pl = BetterBan::getInstance();
            $entry = $pl->useMySQL() ? $pl->getMySQLNameBans()->getEntry($player->getName()) : $pl->getServer()->getNameBans()->getEntry($player->getName());
            $reason = str_replace(["{source}", "{expires}", "{reason}", "{line}"], [$entry->getSource(), $entry->getExpires() !== null ? BetterBan::getInstance()->toPrettyFormat($entry->getExpires(), (BetterBan::getInstance()->getConfig("use-legacy-format") ?? false)) : "Never", $entry->getReason(), "\n"], BetterBan::getInstance()->getConfig()->get("you-are-banned-logout"));
            $player->kick($reason);
            $event->cancel();
        }
    }
}