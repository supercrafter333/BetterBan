<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBKickEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;

class KickCommand extends Command implements PluginIdentifiableCommand
{
    public function __construct(string $name){
        parent::__construct(
            $name,
            "%pocketmine.command.kick.description",
            "%commands.kick.usage"
        );
        $this->setPermission("pocketmine.command.kick");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if(!$this->testPermission($sender)){
            return true;
        }

        if (empty($args) && $sender instanceof Player) {
            $sender->sendForm(BBDefaultForms::kickForm());
            return true;
        }

        if(count($args) === 0){
            throw new InvalidCommandSyntaxException();
        }

        $name = array_shift($args);
        $reason = trim(implode(" ", $args));

        if(($player = $sender->getServer()->getPlayer($name)) instanceof Player){
            $newReason = $reason == "" ? null : $reason;
            $ev = new BBKickEvent($player, $sender->getName(), $newReason);
            $ev->call();
            if ($ev->isCancelled()) {
                Command::broadcastCommandMessage($sender, "Kick cancelled because the BBKickEvent is cancelled!", true);
                return true;
            }
            if (!$ev->isCancelled()) {
                $ev->kickTarget();
            }
            if($reason !== ""){
                Command::broadcastCommandMessage($sender, new TranslationContainer("commands.kick.success.reason", [$player->getName(), $reason]));
            }else{
                Command::broadcastCommandMessage($sender, new TranslationContainer("commands.kick.success", [$player->getName()]));
            }
        }else{
            $sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.player.notFound"));
        }

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