<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;

class BaninfoCommand extends Command implements PluginIdentifiableCommand
{

    private $pl;

    public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = [])
    {
        $this->pl = BetterBan::getInstance();
        parent::__construct("baninfo", "See the ban-informations of a banned player", "§4Usage:§r /baninfo <player>", ["baninformation"]);
    }

    public function execute(CommandSender $s, string $commandLabel, array $args): void
    {
        if (empty($args)) {
            $s->sendMessage($this->usageMessage);
            return;
        }
        $pl = $this->pl;
        $name = implode(" ", $args);
        $server = $pl->getServer();
        $nameBans = $server->getNameBans();
        if ($nameBans->getEntry($name) === null) {
            $s->sendMessage(str_replace(["{name}"], [$name], $pl->getConfig()->get("error-not-banned")));
        }
        $ban = $nameBans->getEntry($name);
        $source = $ban->getSource();
        $date = $ban->hasExpired() ? $ban->getExpires()->format("Y.m.d H:i:s") : "§8---";
        $reason = $ban->getReason();
        $log = $pl->getBanLogOf($name);
        $s->sendMessage(str_replace(["{name}", "{source}", "{date}", "{reason}", "{log}", "{line}"], [$name, $source, $date, $reason, $log, "\n"], $pl->getConfig()->get("baninfo-message-list")));
        return;
    }

    public function getPlugin(): Plugin
    {
        return $this->pl;
    }
}