<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;

/**
 * Class EditbanCommand
 * @package supercrafter333\BetterBan\Commands
 */
class EditbanCommand extends Command implements PluginIdentifiableCommand
{

    /**
     * @var BetterBan
     */
    private $pl;

    /**
     * EditbanCommand constructor.
     * @param string $name
     * @param string $description
     * @param string|null $usageMessage
     * @param array $aliases
     */
    public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = [])
    {
        $this->pl = BetterBan::getInstance();
        $this->setPermission("BetterBan.editban.cmd");
        parent::__construct("editban", "Add or reduce the time of a ban", "§4Use:§r /editban <player> <addbantime|reducebantime> <time>", $aliases);
    }

    /**
     * @param CommandSender $s
     * @param string $commandLabel
     * @param array $args
     * @throws \Exception
     */
    public function execute(CommandSender $s, string $commandLabel, array $args): void
    {
        $plugin = $this->pl;
        $cfg = $plugin->getConfig();
        if (count($args) < 3) {
            $s->sendMessage($this->usageMessage);
            return;
        }
        $server = $plugin->getServer();
        if (!$server->getNameBans()->isBanned($args[0])) {
            $s->sendMessage(str_replace(["{name}"], [$args[0]], $cfg->get("error-not-banned")));
            return;
        }
        $playerName = $args[0];
        if ($args[1] !== "addbantime" && $args[1] !== "reducebantime") {
            $s->sendMessage($this->usageMessage);
            return;
        }
        $ban = $server->getNameBans()->getEntry($args[0]);
        $oldDate = $ban->getExpires();
        if ($oldDate === null) {
            $s->sendMessage(str_replace(["{name}"], [$args[0]], $cfg->get("error-no-tempban-found")));
            return;
        }
        $nameBans = $server->getNameBans();
        $option = $args[1];
        if ($option === "addbantime") {
            $information = $plugin->stringToTimestampAdd($args[2], $oldDate);
            $date = $information[0];
            $newDate = $date;
            $ban->setExpires($newDate);
            $server->getNameBans()->save(true);
            $plugin->sendBanUpdatedMessageToDC($playerName);
            if (!$s->isOp()) {
                $s->sendMessage("§7§o[Added time to ban: " . $playerName . " +" . $args[2] . "]");
            }
            $server->getLogger()->info("§7§o[Added time to ban: " . $playerName . " +" . $args[2] . "]");
            foreach ($plugin->getServer()->getOps() as $ops) {
                $op = $plugin->getServer()->getPlayer($ops);
                if ($op instanceof Player) {
                    $op->sendMessage("§7§o[Added time to ban: " . $playerName . " +" . $args[2] . "]");
                }
            }
            return;
        }
        if ($option === "reducebantime") {
            $information = $plugin->stringToTimestampReduce($args[2], $oldDate);
            $date = $information[0];
            $newDate = $date;
            //$clipboard = ["time" => $ban->getExpires(), "reason" => $ban->getReason(), "source" => $ban->getSource(), "name" => $ban->getName(), "created" => $ban->getCreated()];
            $ban->setExpires($newDate);
            $server->getNameBans()->save(true);
            $plugin->sendBanUpdatedMessageToDC($playerName);
            if (!$s->isOp()) {
                $s->sendMessage("§7§o[Reduced time for ban: " . $playerName . " -" . $args[2] . "]");
            }
            $server->getLogger()->info("§7§o[Reduced time for ban: " . $playerName . " -" . $args[2] . "]");
            foreach ($plugin->getServer()->getOps() as $ops) {
                $op = $plugin->getServer()->getPlayer($ops);
                if ($op instanceof Player) {
                    $op->sendMessage("§7§o[Reduced time for ban: " . $playerName . " -" . $args[2] . "]");
                }
            }
            return;
        }
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin
    {
        return $this->pl;
    }
}