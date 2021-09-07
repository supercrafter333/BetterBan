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
use supercrafter333\BetterBan\Events\BBPardonEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;

/**
 * Class PardonCommand
 * @package supercrafter333\BetterBan\Commands
 */
class PardonCommand extends Command implements PluginIdentifiableCommand
{

    /**
     * PardonCommand constructor.
     * @param string $name
     */
    public function __construct(string $name){
        parent::__construct(
            $name,
            "%pocketmine.command.unban.player.description",
            "%commands.unban.usage",
            ["unban"]
        );
        $this->setPermission("pocketmine.command.unban.player");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if(!$this->testPermission($sender)){
            return true;
        }
        if (empty($args) && $sender instanceof Player) {
            $sender->sendForm(BBDefaultForms::pardonForm());
            return true;
        }
        if(count($args) !== 1){
            throw new InvalidCommandSyntaxException();
        }
        $ev = new BBPardonEvent($args[0], $sender->getName());
        $ev->call();
        if ($ev->isCancelled()) {
            Command::broadcastCommandMessage($sender, "Unban cancelled because the BBPardonEvent is cancelled!", true);
            return true;
        }

        $pl = BetterBan::getInstance();
        if ($pl->useMySQL()) {
            $pl->getMySQLNameBans()->remove($args[0]);
        } else {
            $pl->getServer()->getNameBans()->remove($args[0]);
        }

        Command::broadcastCommandMessage($sender, new TranslationContainer("commands.unban.success", [$args[0]]));

        return true;
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin
    {
        return BetterBan::getInstance();
    }
}