<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;

/**
 * Class BaninfoCommand
 * @package supercrafter333\BetterBan\Commands
 * @method testPermission(CommandSender $s)
 */
class BaninfoCommand extends BetterBanOwnedCommand
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
        $this->setPermission('BetterBan.baninfo.cmd');
        parent::__construct("baninfo", "See the ban-informations of a banned player", "§4Use:§r /baninfo <player>", ["baninformation"]);
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
        $name = implode(" ", $args);
        $server = $pl->getServer();
        $nameBans = $pl->useMySQL() ? $pl->getMySQLNameBans() : $server->getNameBans();
        if ($nameBans->getEntry($name) === null) {
            //$s->sendMessage(str_replace(["{name}"], [$name], $pl->getConfig()->get("error-not-banned")));
            $s->sendMessage(str_replace(["{name}", "{log}", "{line}"], [$name, (string)$pl->getBanLogOf($name), "\n"], $pl->getConfig()->get("baninfo-not-banned")));
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