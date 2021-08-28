<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;

/**
 * Class BaninfoCommand
 * @package supercrafter333\BetterBan\Commands
 */
class BaninfoCommand extends Command implements PluginIdentifiableCommand
{

    /**
     * @var BetterBan
     */
    private $pl;

    /**
     * BaninfoCommand constructor.
     * @param string $name
     * @param string $description
     * @param string|null $usageMessage
     * @param array $aliases
     */
    public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = [])
    {
        $this->pl = BetterBan::getInstance();
        parent::__construct("baninfo", "See the ban-informations of a banned player", "§4Usage:§r /baninfo <player>", ["baninformation"]);
    }

    /**
     * @param CommandSender $s
     * @param string $commandLabel
     * @param array $args
     */
    public function execute(CommandSender $s, string $commandLabel, array $args): void
    {
        if (!$this->testPermission($s)) {
            return;
        }
        if (empty($args)) {
            $s->sendMessage($this->usageMessage);
            return;
        }
        $pl = $this->pl;
        $cfg = $pl->getConfig();
        $name = implode(" ", $args);
        $server = $pl->getServer();
        if (!$server->getNameBans()->isBanned($args[0])) {
            $s->sendMessage(str_replace(["{name}"], [$args[0]], $cfg->get("error-not-banned")));
            return;
        }
        $nameBans = $server->getNameBans();
        if (!$server->getNameBans()->isBanned($name)) {
            $s->sendMessage(str_replace(["{name}"], [$name], $cfg->get("error-not-banned")));
            return;
        }
        $ban = $nameBans->getEntry($name);
        $source = $ban->getSource() === "(Unknown)" ? "§8---" : $ban->getSource();
        $date = $ban->hasExpired() ? $ban->getExpires()->format("Y.m.d H:i:s") : "§8---";
        $reason = $ban->getReason();
        $log = $pl->getBanLogOf($name);
        $s->sendMessage(str_replace(["{name}", "{source}", "{date}", "{reason}", "{log}", "{line}"], [$name, $source, $date, $reason, (string)$log, "\n"], $pl->getConfig()->get("baninfo-message-list")));
        return;
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin
    {
        return $this->pl;
    }
}