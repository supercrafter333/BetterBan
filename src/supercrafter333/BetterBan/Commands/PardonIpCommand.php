<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\plugin\Plugin;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBPardonIpEvent;

class PardonIpCommand extends Command implements PluginIdentifiableCommand
{

    public function __construct(string $name){
        parent::__construct(
            $name,
            "%pocketmine.command.unban.ip.description",
            "%commands.unbanip.usage",
            ["unban-ip"]
        );
        $this->setPermission("pocketmine.command.unban.ip");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if(!$this->testPermission($sender)){
            return true;
        }
        if(count($args) !== 1){
            throw new InvalidCommandSyntaxException();
        }
        $ev = new BBPardonIpEvent($args[0], $sender->getName());
        $ev->call();
        if ($ev->isCancelled()) {
            Command::broadcastCommandMessage($sender, "Unban cancelled because the BBPardonIpEvent is cancelled!", true);
            return true;
        }

        $sender->getServer()->getIPBans()->remove($args[0]);

        Command::broadcastCommandMessage($sender, new TranslationContainer("commands.unbanip.success", [$args[0]]));

        return true;
    }

    public function getPlugin(): Plugin
    {
        return BetterBan::getInstance();
    }
}