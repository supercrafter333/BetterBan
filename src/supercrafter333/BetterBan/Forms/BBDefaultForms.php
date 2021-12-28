<?php

namespace supercrafter333\BetterBan\Forms;

use DateTime;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\element\Slider;
use dktapps\pmforms\element\Toggle;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\command\Command;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\player\Player;
use supercrafter333\BetterBan\BetterBan;
use supercrafter333\BetterBan\Events\BBBanEvent;
use supercrafter333\BetterBan\Events\BBBanIpEvent;
use supercrafter333\BetterBan\Events\BBEditbanEvent;
use supercrafter333\BetterBan\Events\BBEditipbanEvent;

class BBDefaultForms
{
    public static function openMenuForm(): MenuForm
    {
        return new MenuForm(
            "§c§lBetterBan",
            "Please select an option.",
            [
                new MenuOption("Ban\n§7Ban a player"),
                new MenuOption("Ip-Ban\n§7Ban a Ip-Address"),
                new MenuOption("Edit Ban\n§7Edit a Ban"),
                new MenuOption("Edit Ip-Ban\n§7Edit a Ip-Ban"),
                new MenuOption("Pardon\n§7Unban a player"),
                new MenuOption("Pardon-Ip\n§7Unban a Ip-Address"),
                new MenuOption("Kick\n§7Kick a player"),
            ],
            function (Player $submitter, int $selected): void {
                switch ($selected) {
                    case 0:
                        $submitter->sendForm(self::banForm());
                        return;
                    case 1:
                        $submitter->sendForm(self::banIpForm());
                        return;
                    case 2:
                        $submitter->sendForm(self::editbanForm());
                        return;
                    case 3:
                        $submitter->sendForm(self::editipbanForm());
                        return;
                    case 4:
                        $submitter->sendForm(self::pardonForm());
                        return;
                    case 5:
                        $submitter->sendForm(self::pardonIpForm());
                        return;
                    case 6:
                        $submitter->sendForm(self::kickForm());
                        return;
                }
                self::closeUI($submitter);
                return;
            },
            function (Player $submitter): void {
                self::closeUI($submitter);
                return;
            });
    }

    public static function banForm(): CustomForm
    {
        return new CustomForm(
            "§c§lBetterBan",
            [
                new Label("l1", "Here you can configure the ban.\n"),
                new Label("l2", "§eInformation: §rIf you leave all time configs on 0, the player will be permanently banned."),
                new Input("name", "Name", "Name of the player"),
                new Input("reason", "Reason", "Reason of the ban"),
                new Slider("mins", "Minutes", 0, 60),
                new Slider("hours", "Hours", 0, 24),
                new Slider("days", "Days", 0, 30),
                new Slider("months", "Months", 0, 12),
                new Slider("years", "Years", 0, 100),
                new Label("l4", "Click on §8Send§r to submit the ban.")
            ],
            function (Player $submitter, CustomFormResponse $response): void {
                $res = $response->getAll();
                if (!isset($res["name"]) || !isset($res["reason"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rPlease fill in the required fields (Name and Reason)!");
                    return;
                }
                if (!self::isResOkay($res["name"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rThe §eName§r isn't allowed (Empty)!");
                    return;
                }
                if (!self::isResOkay($res["reason"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rThe §eReason§r isn't allowed (Empty)!");
                    return;
                }
                $name = $res["name"]; //Input
                $reason = $res["reason"]; //Input
                $mins = $res["mins"]; //Slider
                $hours = $res["hours"]; //Slider
                $days = $res["days"]; //Slider
                $months = $res["months"]; //Slider
                $years = $res["years"]; //Slider
                if ($mins == 0 && $hours == 0 && $days == 0 && $months == 0 && $years == 0) {
                    $submitter->getServer()->dispatchCommand($submitter, "ban $name $reason");
                } else {
                    $pl = BetterBan::getInstance();
                    $cfg = $pl->getConfig();
                    $banEvent = new BBBanEvent($submitter->getName(), $name, $reason);
                    $banEvent->call();
                    if ($banEvent->isCancelled()) {
                        Command::broadcastCommandMessage($submitter, "Ban cancelled because the BBBanEvent is cancelled!", true);
                        return;
                    }
                    $bantime = new DateTime('now');
                    if ($mins !== 0) $bantime->modify("+$mins minutes");
                    if ($hours !== 0) $bantime->modify("+$hours hours");
                    if ($days !== 0) $bantime->modify("+$days days");
                    if ($months !== 0) $bantime->modify("+$months months");
                    if ($years !== 0) $bantime->modify("+$years years");
                    $pl->addBanToBanlog($name);
                    if ($pl->useMySQL()) {
                        $pl->getMySQLNameBans()->addBan($name, $reason, $bantime, $submitter->getName());
                    } else {
                        $submitter->getServer()->getNameBans()->addBan($name, $reason, $bantime, $submitter->getName());
                    }
                    if (($player = $submitter->getServer()->getPlayerExact($name)) instanceof Player) {
                        $player->kick($reason !== "" ? str_replace(["{reason}", "{time}", "{line}"], [$reason, $bantime->format("Y.m.d H:i:s"), "\n"], $cfg->get("kick-message-with-time")) : $cfg->get("kick-message"));
                        Command::broadcastCommandMessage($submitter, KnownTranslationFactory::commands_ban_success($player !== null ? $player->getName() : $name));
                        $submitter->sendMessage("[Time] Banned!");
                    }
                }
            },
            function (Player $submitter): void {
                self::closeUI($submitter);
                return;
            });
    }

    public static function banIpForm(): CustomForm
    {
        return new CustomForm(
            "§c§lBetterBan",
            [
                new Label("l1", "Here you can configure the ip-ban.\n"),
                new Label("l2", "§eInformation: §rIf you leave all time configs on 0, the Ip-Address will be permanently banned."),
                new Input("ip", "Ip-Address", "Ip-Address of the player"),
                new Input("reason", "Reason", "Reason of the ban"),
                new Slider("mins", "Minutes", 0, 60),
                new Slider("hours", "Hours", 0, 24),
                new Slider("days", "Days", 0, 30),
                new Slider("months", "Months", 0, 12),
                new Slider("years", "Years", 0, 100),
                new Label("l4", "Click on §8Send§r to submit the ban.")
            ],
            function (Player $submitter, CustomFormResponse $response): void {
                $res = $response->getAll();
                if (!isset($res["ip"]) || !isset($res["reason"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rPlease fill in the required fields (Ip-Address and Reason)!");
                    return;
                }
                if (!self::isResOkay($res["ip"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rThe §eIp-Address§r isn't allowed (Empty)!");
                    return;
                }
                if (!self::isResOkay($res["reason"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rThe §eReason§r isn't allowed (Empty)!");
                    return;
                }
                $ip = $res["ip"]; //Input
                $reason = $res["reason"]; //Input
                $mins = $res["mins"]; //Slider
                $hours = $res["hours"]; //Slider
                $days = $res["days"]; //Slider
                $months = $res["months"]; //Slider
                $years = $res["years"]; //Slider
                if ($mins == 0 && $hours == 0 && $days == 0 && $months == 0 && $years == 0) {
                    $submitter->getServer()->dispatchCommand($submitter, "banip $ip $reason");
                } else {
                    $pl = BetterBan::getInstance();
                    $cfg = $pl->getConfig();
                    $banEvent = new BBBanIpEvent($ip, $submitter->getName(), $reason);
                    $banEvent->call();
                    if ($banEvent->isCancelled()) {
                        Command::broadcastCommandMessage($submitter, "Ban cancelled because the BBBanIpEvent is cancelled!", true);
                        return;
                    }
                    $bantime = new DateTime('now');
                    if ($mins !== 0) $bantime->modify("+$mins minutes");
                    if ($hours !== 0) $bantime->modify("+$hours hours");
                    if ($days !== 0) $bantime->modify("+$days days");
                    if ($months !== 0) $bantime->modify("+$months months");
                    if ($years !== 0) $bantime->modify("+$years years");
                    $player = null;
                    foreach ($submitter->getServer()->getOnlinePlayers() as $onlinePlayer) {
                        if ($onlinePlayer->getNetworkSession()->getIp() === $ip) {
                            $player = $onlinePlayer;
                        }
                    }
                    if (BetterBan::getInstance()->useMySQL()) {
                        BetterBan::getInstance()->getMySQLIpBans()->addBan($ip, $reason, $bantime, $submitter->getName());
                    } else {
                        $player->getServer()->getIPBans()->addBan($ip, $reason, $bantime, $submitter->getName());
                    }
                    $submitter->getServer()->getNetwork()->blockAddress($ip, -1);
                    if ($player instanceof Player) {
                        $player->kick(str_replace(["{reason}", "{time}", "{line}"], [$reason, $bantime->format("Y.m.d H:i:s"), "\n"], $cfg->get("kick-ip-message-with-time")));
                        Command::broadcastCommandMessage($submitter, KnownTranslationFactory::commands_banip_success_players((string)$player->getNetworkSession()->getIp(), $player->getName()), true);
                        $submitter->sendMessage("[Time] Banned!");
                    }
                }
            },
            function (Player $submitter): void {
                self::closeUI($submitter);
                return;
            });
    }

    public static function editbanForm(): CustomForm
    {
        return new CustomForm(
            "§c§lBetterBan",
            [
                new Label("l1", "Here you can edit a ban."),
                new Input("name", "Name", "Name of the banned player"),
                new Slider("mins", "Minutes", 0, 60),
                new Slider("hours", "Hours", 0, 24),
                new Slider("days", "Days", 0, 30),
                new Slider("months", "Months", 0, 12),
                new Slider("years", "Years", 0, 100),
                new Toggle("reduce", "Reduce time?", false),
                new Label("l2", "Click on §8Send§r to submit the changes.")
            ],
            function (Player $submitter, CustomFormResponse $response): void {
                $res = $response->getAll();
                if (!isset($res["name"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rPlease fill in the required fields (Name and Reason)!");
                    return;
                }
                if (!self::isResOkay($res["name"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rThe §eName§r isn't allowed (Empty)!");
                    return;
                }
                $pl = BetterBan::getInstance();
                $cfg = $pl->getConfig();
                $name = $res["name"]; //Input
                $mins = $res["mins"]; //Slider
                $hours = $res["hours"]; //Slider
                $days = $res["days"]; //Slider
                $months = $res["months"]; //Slider
                $years = $res["years"]; //Slider
                $server = $pl->getServer();
                if (!$server->getNameBans()->isBanned($name)) {
                    $submitter->sendMessage(str_replace(["{name}"], [$name], $cfg->get("error-not-banned")));
                    return;
                }
                $ban = $server->getNameBans()->getEntry($name);
                $oldDate = $ban->getExpires();
                if ($oldDate === null) {
                    $submitter->sendMessage(str_replace(["{name}"], [$name], $cfg->get("error-no-tempban-found")));
                    return;
                }
                $nameBans = $server->getNameBans();
                $ebEvent = new BBEditbanEvent($name);
                $ebEvent->call();
                if ($ebEvent->isCancelled()) {
                    Command::broadcastCommandMessage($submitter, "Ban editing cancelled because the BBEditbanEvent is cancelled!", true);
                    return;
                }
                if ($mins == 0 && $hours == 0 && $days == 0 && $months == 0 && $years == 0) {
                    $submitter->sendMessage("§4Ban cancelled!! §rThe §eTimes§r are all set to 0!");
                    return;
                } else {
                    $bantime = $oldDate;
                    ## add ##
                    if ($res["reduce"] == false) {
                        if ($mins !== 0) $bantime->modify("+$mins minutes");
                        if ($hours !== 0) $bantime->modify("+$hours hours");
                        if ($days !== 0) $bantime->modify("+$days days");
                        if ($months !== 0) $bantime->modify("+$months months");
                        if ($years !== 0) $bantime->modify("+$years years");
                    }
                    ######
                    ## reduce ##
                    if ($res["reduce"]) {
                        if ($mins !== 0) $bantime->modify("-$mins minutes");
                        if ($hours !== 0) $bantime->modify("-$hours hours");
                        if ($days !== 0) $bantime->modify("-$days days");
                        if ($months !== 0) $bantime->modify("-$months months");
                        if ($years !== 0) $bantime->modify("-$years years");
                    }
                    ######
                    $ebEvent = new BBEditbanEvent($name);
                    $ebEvent->call();
                    if ($ebEvent->isCancelled()) {
                        Command::broadcastCommandMessage($submitter, "Ban editing cancelled because the BBEditbanEvent is cancelled!", true);
                        return;
                    }
                    $ban->setExpires($bantime);
                    $nameBans->save(true);
                    Command::broadcastCommandMessage($submitter, "Ban of $name was successfully updated!", true);
                }
            },
            function (Player $submitter): void {
                self::closeUI($submitter);
                return;
            });
    }

    public static function editipbanForm(): CustomForm
    {
        return new CustomForm(
            "§c§lBetterBan",
            [
                new Label("l1", "Here you can edit a ip-ban."),
                new Input("ip", "Ip-Address"),
                new Slider("mins", "Minutes", 0, 60),
                new Slider("hours", "Hours", 0, 24),
                new Slider("days", "Days", 0, 30),
                new Slider("months", "Months", 0, 12),
                new Slider("years", "Years", 0, 100),
                new Toggle("reduce", "Reduce time?", false),
                new Label("l2", "Click on §8Send§r to submit the changes.")
            ],
            function (Player $submitter, CustomFormResponse $response): void {
                $res = $response->getAll();
                if (!isset($res["ip"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rPlease fill in the required fields (Ip-Address and Reason)!");
                    return;
                }
                if (!self::isResOkay($res["ip"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rThe §eIp-Address§r isn't allowed (Empty)!");
                    return;
                }
                $pl = BetterBan::getInstance();
                $cfg = $pl->getConfig();
                $ip = $res["ip"]; //Input
                $mins = $res["mins"]; //Slider
                $hours = $res["hours"]; //Slider
                $days = $res["days"]; //Slider
                $months = $res["months"]; //Slider
                $years = $res["years"]; //Slider
                $server = $pl->getServer();
                if (!$server->getIPBans()->isBanned($ip)) {
                    $submitter->sendMessage(str_replace(["{ip}"], [$ip], $cfg->get("error-not-ipbanned")));
                    return;
                }
                $ban = $server->getIPBans()->getEntry($ip);
                $oldDate = $ban->getExpires();
                if ($oldDate === null) {
                    $submitter->sendMessage(str_replace(["{ip}"], [$ip], $cfg->get("error-no-iptempban-found")));
                    return;
                }
                $ipBans = $server->getIPBans();
                $ebEvent = new BBEditipbanEvent($ip);
                $ebEvent->call();
                if ($ebEvent->isCancelled()) {
                    Command::broadcastCommandMessage($submitter, "Ban editing cancelled because the BBEditbanEvent is cancelled!", true);
                    return;
                }
                if ($mins == 0 && $hours == 0 && $days == 0 && $months == 0 && $years == 0) {
                    $submitter->sendMessage("§4Ban cancelled!! §rThe §eTimes§r are all set to 0!");
                    return;
                } else {
                    $bantime = $oldDate;
                    ## add ##
                    if ($res["reduce"] == false) {
                        if ($mins !== 0) $bantime->modify("+$mins minutes");
                        if ($hours !== 0) $bantime->modify("+$hours hours");
                        if ($days !== 0) $bantime->modify("+$days days");
                        if ($months !== 0) $bantime->modify("+$months months");
                        if ($years !== 0) $bantime->modify("+$years years");
                    }
                    ######
                    ## reduce ##
                    if ($res["reduce"]) {
                        if ($mins !== 0) $bantime->modify("-$mins minutes");
                        if ($hours !== 0) $bantime->modify("-$hours hours");
                        if ($days !== 0) $bantime->modify("-$days days");
                        if ($months !== 0) $bantime->modify("-$months months");
                        if ($years !== 0) $bantime->modify("-$years years");
                    }
                    ######
                    $ebEvent = new BBEditipbanEvent($ip);
                    $ebEvent->call();
                    if ($ebEvent->isCancelled()) {
                        Command::broadcastCommandMessage($submitter, "Ip-Ban editing cancelled because the BBEditipbanEvent is cancelled!", true);
                        return;
                    }
                    $ban->setExpires($bantime);
                    $ipBans->save(true);
                    Command::broadcastCommandMessage($submitter, "Ip-Ban of $ip was successfully updated!", true);
                }
            },
            function (Player $submitter): void {
                self::closeUI($submitter);
                return;
            });
    }

    public static function pardonForm(): CustomForm
    {
        return new CustomForm(
            "§c§lBetterBan",
            [
                new Label("l1", "Here you can unban a player."),
                new Input("name", "Name", "Name of the banned player"),
                new Label("l2", "Click on §8Send§r to submit the changes.")
            ],
            function (Player $submitter, CustomFormResponse $response): void {
                if (!self::isResOkay($response->getAll()["name"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rThe §eName§r isn't allowed (Empty)!");
                    return;
                }
                $submitter->getServer()->dispatchCommand($submitter, "pardon " . $response->getAll()["name"]);
                return;
            },
            function (Player $submitter): void {
                self::closeUI($submitter);
                return;
            });
    }

    public static function pardonIpForm(): CustomForm
    {
        return new CustomForm(
            "§c§lBetterBan",
            [
                new Label("l1", "Here you can unban a Ip-Address."),
                new Input("ip", "Ip-Adress", "Ip-Address of the banned Ip-Address"),
                new Label("l2", "Click on §8Send§r to submit the changes.")
            ],
            function (Player $submitter, CustomFormResponse $response): void {
                if (!self::isResOkay($response->getAll()["ip"])) {
                    $submitter->sendMessage("§4Ban cancelled!! §rThe §eIp-Address§r isn't allowed (Empty)!");
                    return;
                }
                $submitter->getServer()->dispatchCommand($submitter, "pardonip " . $response->getAll()["ip"]);
                return;
            },
            function (Player $submitter): void {
                self::closeUI($submitter);
                return;
            });
    }

    public static function kickForm(): CustomForm
    {
        return new CustomForm(
            "§c§lBetterBan",
            [
                new Label("l1", "Here you can kick a player from the server."),
                new Input("name", "Name", "Name of the player"),
                new Input("reason", "Reason", "Reason of the kick"),
                new Label("l2", "Click on §8Send§r to submit the changes.")
            ],
            function (Player $submitter, CustomFormResponse $response): void {
                $res = $response->getAll();
                if (!isset($res["name"]) || !isset($res["reason"])) {
                    $submitter->sendMessage("§4Kick cancelled!! §rPlease fill in the required fields (Name and Reason)!");
                    return;
                }
                if (!self::isResOkay($res["name"])) {
                    $submitter->sendMessage("§4Kick cancelled!! §rThe §eName§r isn't allowed (Empty)!");
                    return;
                }
                if (!self::isResOkay($res["reason"])) {
                    $submitter->sendMessage("§4Kick cancelled!! §rThe §eReason§r isn't allowed (Empty)!");
                    return;
                }
                $name = $res["name"]; //Input
                $reason = $res["reason"]; //Input
                $submitter->getServer()->dispatchCommand($submitter, "kick " . $name . " " . $reason);
                return;
            },
            function (Player $submitter): void {
                self::closeUI($submitter);
                return;
            });
    }

    private static function closeUI(Player $player): void
    {
        $player->sendMessage("Successfully closed the form!");
        return;
    }

    /*private static function banCancelled(Player $player): void
    {
        $player->sendMessage("You've successfully cancelled the action!");
        return;
    }*/

    private static function isResOkay(?string $res): bool
    {
        if ($res == "" || $res == null) return false;
        return true;
    }
}