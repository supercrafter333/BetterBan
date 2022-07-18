<?php

namespace supercrafter333\BetterBan\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBKickEvent;
use supercrafter333\BetterBan\Forms\BBDefaultForms;

class KickCommand extends BetterBanOwnedCommand
{
    public function __construct(string $name){
        parent::__construct(
            $name,
            KnownTranslationFactory::pocketmine_command_kick_description(),
			KnownTranslationFactory::commands_kick_usage()
        );
        $this->setPermission(DefaultPermissionNames::COMMAND_KICK);
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

        if(($player = $sender->getServer()->getPlayerExact($name)) instanceof Player){
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
                Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_kick_success_reason($player->getName(), $reason));
            }else{
                Command::broadcastCommandMessage($sender, KnownTranslationFactory::commands_kick_success($player->getName()));
            }
        }else{
            $sender->sendMessage(KnownTranslationFactory::commands_generic_player_notFound()->prefix(TextFormat::RED));
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