<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBEditbanEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;

/**
 * Class EditbanCommand
 * @package supercrafter333\BetterBan\Commands
 */
class EditipbanCommand extends Command implements PluginIdentifiableCommand
{

    /**
     * @var BetterBan
     */
    private $pl;

    /**
     * EditbanCommand constructor.
     * @param string $ip
     * @param string $description
     * @param string|null $usageMessage
     * @param array $aliases
     */
    public function __construct(string $ip, string $description = "", string $usageMessage = null, array $aliases = [])
    {
        $this->pl = BetterBan::getInstance();
        $this->setPermission("BetterBan.editipban.cmd");
        parent::__construct("editipban", "Add or reduce the time of a ip-ban", "§4Use:§r /editipban <ip-address> <addbantime|reducebantime> <time>", $aliases);
    }

    /**
     * @param CommandSender $s
     * @param string $commandLabel
     * @param array $args
     * @throws \Exception
     */
    public function execute(CommandSender $s, string $commandLabel, array $args): void
    {
        if (!$this->testPermission($s)) {
            return;
        }
        $plugin = $this->pl;
        $cfg = $plugin->getConfig();
        if (empty($args) && $s instanceof Player) {
            $s->sendForm(BBDefaultForms::editipbanForm());
            return;
        }
        if (count($args) < 3) {
            $s->sendMessage($this->usageMessage);
            return;
        }
        $server = $plugin->getServer();
        if (!$server->getIPBans()->isBanned($args[0])) {
            $s->sendMessage(str_replace(["{ip}"], [$args[0]], $cfg->get("error-not-ipbanned")));
            return;
        }
        $playerip = $args[0];
        if ($args[1] !== "addbantime" && $args[1] !== "reducebantime") {
            $s->sendMessage($this->usageMessage);
            return;
        }
        $ban = $server->getIPBans()->getEntry($args[0]);
        $oldDate = $ban->getExpires();
        if ($oldDate === null) {
            $s->sendMessage(str_replace(["{ip}"], [$args[0]], $cfg->get("error-no-iptempban-found")));
            return;
        }
        $ipBans = $server->getIPBans();
        $option = $args[1];
        $ebEvent = new BBEditbanEvent($playerip);
        $ebEvent->call();
        if ($ebEvent->isCancelled()) {
            Command::broadcastCommandMessage($s, "Ban editing cancelled because the BBEditipbanEvent is cancelled!", true);
            return;
        }
        if ($option === "addbantime") {
            $information = $plugin->stringToTimestampAdd($args[2], $oldDate);
            $date = $information[0];
            $newDate = $date;
            $ban->setExpires($newDate);
            $server->getIPBans()->save(true);
            //$server->getLogger()->info("§7§o[Added time to ban: " . $playerip . " +" . $args[2] . "]");
            Command::broadcastCommandMessage($s, "§7§o[Added time to ip-ban: " . $playerip . " +" . $args[2] . "]", true);
            return;
        }
        if ($option === "reducebantime") {
            $information = $plugin->stringToTimestampReduce($args[2], $oldDate);
            $date = $information[0];
            $newDate = $date;
            //$clipboard = ["time" => $ban->getExpires(), "reason" => $ban->getReason(), "source" => $ban->getSource(), "ip" => $ban->getip(), "created" => $ban->getCreated()];
            $ban->setExpires($newDate);
            $server->getIPBans()->save(true);
            Command::broadcastCommandMessage($s, "§7§o[Reduced time for ip-ban: " . $playerip . " -" . $args[2] . "]", true);
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