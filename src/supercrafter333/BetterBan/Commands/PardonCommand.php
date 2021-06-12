<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBPardonEvent;

class PardonCommand extends Command implements PluginIdentifiableCommand
{

    public function __construct(string $name){
        parent::__construct(
            $name,
            "%pocketmine.command.unban.player.description",
            "%commands.unban.usage",
            ["unban"]
        );
        $this->setPermission("pocketmine.command.unban.player");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if(!$this->testPermission($sender)){
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

        $sender->getServer()->getNameBans()->remove($args[0]);

        Command::broadcastCommandMessage($sender, new TranslationContainer("commands.unban.success", [$args[0]]));

        return true;
    }

    public function getPlugin(): Plugin
    {
        return BetterBan::getInstance();
    }
}