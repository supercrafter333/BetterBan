<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use supercrafter333\BetterBan\BetterBan;

/**
 * Custom command class for BetterBan.
 */
abstract class BetterBanOwnedCommand extends Command implements PluginOwned
{

    /**
     * @return Plugin|BetterBan
     */
    public function getOwningPlugin(): Plugin
    {
        return BetterBan::getInstance();
    }
}