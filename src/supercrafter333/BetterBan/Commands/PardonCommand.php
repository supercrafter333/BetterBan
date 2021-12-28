<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use supercrafter333\BetterBan\Commands\BetterBanOwnedCommand;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\KnownTranslationKeys;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBPardonEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;

/**
 * Class PardonCommand
 * @package supercrafter333\BetterBan\Commands
 */
class PardonCommand extends BetterBanOwnedCommand
{

    /**
     * PardonCommand constructor.
     * @param string $name
     */
    public function __construct(string $name){
        parent::__construct(
            $name,
            KnownTranslationFactory::pocketmine_command_unban_player_description(),
            KnownTranslationFactory::commands_unban_usage(),
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

        Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_unban_success($args[0]));

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
