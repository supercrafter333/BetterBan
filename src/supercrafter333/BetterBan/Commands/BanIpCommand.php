<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBBanEvent;
use supercrafter333\BetterBan\Events\BBBanIpEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;

/**
 * Class BanIpCommand
 * @package supercrafter333\BetterBan\Commands
 */
class BanIpCommand extends Command implements PluginIdentifiableCommand
{
    /**
     * @var BetterBan
     */
    private $pl;


    /**
     * BanIpCommand constructor.
     * @param string $name
     * @param string $description
     * @param string|null $usageMessage
     * @param array $aliases
     */
    public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = [])
    {
        $this->pl = BetterBan::getInstance();
        parent::__construct($name, "%pocketmine.command.ban.ip.description", "§4Use: §r/banip <ip-address> [reason: ...] [date interval: ...]", ["ban-ip"]);
        $this->setPermission("pocketmine.command.ban.ip");
    }


    /*public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        $pl = BetterBan::getInstance();
        $cfg = $pl->getConfig();
        if (!$this->testPermission($sender)) {
            return true;
        }

        if (count($args) < 1) {
            throw new InvalidCommandSyntaxException();
        }

        if (count($args) == 2 || count($args) == 1) {
            $name = array_shift($args);
            $reason = isset($args[0]) ? $args[0] : "";

            $banEvent = new BBBanEvent($sender->getName(), $name);
            $banEvent->call();
            if ($banEvent->isCancelled()) {
                Command::broadcastCommandMessage($sender, "Ban cancelled because the BBBanEvent is cancelled!", true);
                return true;
            }
            $pl->addBanToBanlog($name);
            $sender->getServer()->getNameBans()->addBan($name, $reason, null, $sender->getName());
            if (($player = $sender->getServer()->getPlayerExact($name)) instanceof Player) {
                $player->kick($reason !== "" ? str_replace(["{reason}", "{line}"], [$args[0], "\n"], $cfg->get("kick-message-with-reason")) . $reason : $cfg->get("kick-message"));
                Command::broadcastCommandMessage($sender, new TranslationContainer("%commands.ban.success", [$player !== null ? $player->getName() : $name]));
                $sender->sendMessage("Banned!");
                $reason2 = $reason === "" ?? null;
            }
        } elseif (count($args) >= 3) {
            $name = array_shift($args);
            $reason = isset($args[0]) ? $args[0] : "";
            if (!$pl->stringToTimestamp(implode(" ", $args))) {
                $sender->sendMessage($cfg->get("use-DateInterval-format"));
                return true;
            }

            $informations = $pl->stringToTimestamp(implode(" ", $args));
            $bantime = $informations[0];
            $reason = $informations[1];
            //if ($args[1] instanceof DateInterval) {
            $banEvent = new BBBanEvent($sender->getName(), $name, $reason);
            $banEvent->call();
            if ($banEvent->isCancelled()) {
                Command::broadcastCommandMessage($sender, "Ban cancelled because the BBBanEvent is cancelled!", true);
                return true;
            }
            $pl->addBanToBanlog($name);
            $sender->getServer()->getNameBans()->addBan($name, $reason, $bantime, $sender->getName());
            if (($player = $sender->getServer()->getPlayerExact($name)) instanceof Player) {
                $player->kick($reason !== "" ? str_replace(["{reason}", "{time}", "{line}"], [$args[0], $bantime->format("Y.m.d H:i:s"), "\n"], $cfg->get("kick-message-with-time")) . $reason : $cfg->get("kick-message"));
                Command::broadcastCommandMessage($sender, new TranslationContainer("%commands.ban.success", [$player !== null ? $player->getName() : $name]));
                $sender->sendMessage("[Time] Banned!");
                $reason2 = $reason === "" ?? null;
            }
        } else {
            $sender->sendMessage($this->usageMessage);
        }
        return true;
    }*/

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     * @throws \Exception
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if(!$this->testPermission($sender)){
            return true;
        }

        if (empty($args) && $sender instanceof Player) {
            $sender->sendForm(BBDefaultForms::banIpForm());
            return true;
        }

        if(count($args) === 0){
            throw new InvalidCommandSyntaxException();
        }

        $value = array_shift($args);
        if (count($args) == 2) {
            $reason = $args[0];
            $expires = null;
        } else {
            $reason = null;
            $expires = null;
        }
        if (count($args) >= 3) {
            $expiresRaw = BetterBan::getInstance()->stringToTimestamp(implode(" ", $args));
            $expires = isset($expiresRaw[0]) == false ? null : $expiresRaw[0];
        } else {
            $expires = null;
        }

        if(preg_match("/^([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])\\.([01]?\\d\\d?|2[0-4]\\d|25[0-5])$/", $value)){
            $ev = new BBBanIpEvent($value, $sender->getName(), $reason);
            $ev->call();
            if ($ev->isCancelled()) {
                Command::broadcastCommandMessage($sender, "Ip-Ban cancelled because the BBBanIpEvent is cancelled!", true);
                return true;
            }
            $this->processIPBan($value, $sender, $reason, $expires);

            Command::broadcastCommandMessage($sender, new TranslationContainer("commands.banip.success", [$value]), true);
        }else{
            if(($player = $sender->getServer()->getPlayer($value)) instanceof Player){
                $this->processIPBan($player->getAddress(), $sender, $reason, $expires);

                Command::broadcastCommandMessage($sender, new TranslationContainer("commands.banip.success.players", [$player->getAddress(), $player->getName()]), true);
            }else{
                $sender->sendMessage(new TranslationContainer("commands.banip.invalid"));

                return false;
            }
        }

        return true;
    }

    /**
     * @param string $ip
     * @param CommandSender $sender
     * @param string|null $reason
     * @param \DateTime|null $expires
     */
    private function processIPBan(string $ip, CommandSender $sender, string $reason = null, \DateTime $expires = null) : void{

        if ($this->pl->useMySQL()) {
            $this->pl->getMySQLIpBans()->addBan($ip, $reason, $expires, $sender->getName());
        } else {
            $sender->getServer()->getIPBans()->addBan($ip, $reason, $expires, $sender->getName());
        }

        foreach($sender->getServer()->getOnlinePlayers() as $player){
            if($player->getAddress() === $ip) {
                $cfg = BetterBan::getInstance()->getConfig();
                BetterBan::getInstance()->addBanToBanlog($player->getName());
                if ($reason == null) {
                    $player->kick(str_replace(["{line}"], ["\n"], $cfg->get("kick-ip-message")));
                } elseif ($expires == null) {
                    $player->kick(str_replace(["{reason}", "{line}"], [$reason, "\n"], $cfg->get("kick-ip-message-with-reason")));
                } else {
                    $player->kick(str_replace(["{reason}", "{time}", "{line}"], [$reason, $expires->format("Y.m.d H:i:s"), "\n"], $cfg->get("kick-ip-message-with-time")));
                }
            }
        }
        $sender->getServer()->getNetwork()->blockAddress($ip, -1);
    }


    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin
    {
        return $this->pl;
    }
}