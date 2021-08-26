<?php

namespace supercrafter333\BetterBan\Commands;

use DateInterval;
use DateTime;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBBanEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;

/**
 * Class BanCommand
 * @package supercrafter333\BetterBan\Commands
 */
class BanCommand extends Command implements PluginIdentifiableCommand
{

    /**
     * @var BetterBan
     */
    private $pl;

    /**
     * BanCommand constructor.
     * @param string $name
     * @param string $description
     * @param string|null $usageMessage
     * @param array $aliases
     */
    public function __construct(string $name, string $description = "", string $usageMessage = null, array $aliases = [])
    {
        $this->pl = BetterBan::getInstance();
        parent::__construct($name, "%pocketmine.command.ban.player.description", "§4Use: §r/ban <name> [reason: ...] [date interval: ...]");
        $this->setPermission("pocketmine.command.ban.player");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        $pl = BetterBan::getInstance();
        $cfg = $pl->getConfig();
        if (!$this->testPermission($sender)) {
            return true;
        }

        if (empty($args) && $sender instanceof Player) {
            $sender->sendForm(BBDefaultForms::banForm());
            return true;
        }

        if (count($args) < 1) {
            throw new InvalidCommandSyntaxException();
        }

        if (count($args) == 2 || count($args) == 1) {
            $name = array_shift($args);
            $reason = isset($args[0]) ? $args[0] : "";

            $banEvent = new BBBanEvent($name, $sender->getName());
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
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin
    {
        return $this->pl;
    }
}