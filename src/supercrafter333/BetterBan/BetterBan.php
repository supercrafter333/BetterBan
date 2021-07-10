<?php

namespace supercrafter333\BetterBan;

use CortexPE\DiscordWebhookAPI\Embed;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use DateInterval;
use dktapps\pmforms\BaseForm;
use pocketmine\permission\BanEntry;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use supercrafter333\BetterBan\Commands\BanCommand;
use supercrafter333\BetterBan\Commands\BaninfoCommand;
use supercrafter333\BetterBan\Commands\BanIpCommand;
use supercrafter333\BetterBan\Commands\BanlogCommand;
use supercrafter333\BetterBan\Commands\BetterBanCommand;
use supercrafter333\BetterBan\Commands\EditbanCommand;
use supercrafter333\BetterBan\Commands\EditipbanCommand;
use supercrafter333\BetterBan\Commands\KickCommand;
use supercrafter333\BetterBan\Commands\PardonCommand;
use supercrafter333\BetterBan\Commands\PardonIpCommand;
use supercrafter333\BetterBan\Permission\MySQLBanList;

/**
 * Class BetterBan
 * @package supercrafter333\BetterBan
 */
class BetterBan extends PluginBase
{

    /**
     * @var self
     */
    protected static $instance;

    /**
     * Version of BetterBan
     */
    public const VERSION = "4.0.0-DEV";

    /**
     * @var null
     */
    public static $DISCORD_WEBHOOK_URL = null;

    /** @var MySQLBanList $mysqlBanByName */
    private $mysqlBanByName;

    /** @var MySQLBanList $mysqlBanByIP */
    private $mysqlBanByIP;

    /**
     * On Plugin Loading
     */
    public function onLoad()
    {
        self::$instance = $this;
        $this->saveResource("config.yml");
        $this->versionCheck(self::VERSION);
        if (!class_exists(BaseForm::class)) {
            $this->getLogger()->error("pmforms missing!! Please download BetterBan from Poggit!");
        }
        if (!class_exists(Webhook::class)) {
            $this->getLogger()->error("DiscordWebhookAPI missing!! Please download BetterBan from Poggit!");
        }
        $dc_webhook = $this->getConfig()->get("discord-webhook") !== "" ? $this->getConfig()->get("discord-webhook") : null;
        self::$DISCORD_WEBHOOK_URL = $dc_webhook;
    }

    /**
     * On Plugin Enabling
     */
    public function onEnable()
    {
        $this->mysqlBanByName = new MySQLBanList([], MySQLBanList::TABLE_NAMEBANS); // TODO: Include connection details
        $this->mysqlBanByIP = new MySQLBanList([], MySQLBanList::TABLE_IPBANS); // TODO: Include connection details
        $cmdMap = $this->getServer()->getCommandMap();
        $pmmpBanCmd = $cmdMap->getCommand("ban");
        $pmmpPardonCmd = $cmdMap->getCommand("pardon");
        $pmmpBanIpCmd = $cmdMap->getCommand("ban-ip");
        $pmmpPardonIpCmd = $cmdMap->getCommand("pardon-ip");
        $pmmpKickCmd = $cmdMap->getCommand("kick");
        $cmdMap->unregister($pmmpBanCmd);
        $cmdMap->unregister($pmmpPardonCmd);
        $cmdMap->unregister($pmmpBanIpCmd);
        $cmdMap->unregister($pmmpPardonIpCmd);
        $cmdMap->unregister($pmmpKickCmd);
        $cmdMap->registerAll("BetterBan", [
            new BanCommand("ban"),
            new BanlogCommand("banlog"),
            new BaninfoCommand("baninfo"),
            new EditbanCommand("editban"),
            new PardonCommand("pardon"),
            new BanIpCommand("banip"),
            new EditipbanCommand("editipban"),
            new PardonIpCommand("pardonip"),
            new KickCommand("kick"),
            new BetterBanCommand("betterban")
        ]);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @param $version
     */
    private function versionCheck($version)
    {
        if (!$this->getConfig()->exists("version") || $this->getConfig()->get("version") !== $version) {
            $this->getLogger()->debug("OUTDATED CONFIG.YML!! You config.yml is outdated! Your config.yml will automatically updated!");
            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "oldConfig.yml");
            $this->saveResource("config.yml");
            $this->getLogger()->debug("config.yml Updated for version: §b$version");
        }
    }

    /**
     * @return MySQLBanList
     */
    public function getMySQLNameBans()
    {
        return $this->mysqlBanByName;
    }

    /**
     * @return MySQLBanList
     */
    public function getMySQLIPBans()
    {
        return $this->mysqlBanByIP;
    }

    /**
     * @return Config
     */
    public function getBanLogs()
    {
        return new Config($this->getDataFolder() . "banLogs.yml", Config::YAML);
    }

    /**
     * @param string $playerName
     */
    public function addBanToBanlog(string $playerName)
    {
        $log = $this->getBanLogs();
        if ($log->exists($playerName)) {
            $log->set($playerName, intval($log->get($playerName) + 1));
            $log->save();
        } else {
            $log->set($playerName, 1);
            $log->save();
        }
    }

    /**
     * @param string $playerName
     * @return int
     */
    public function getBanLogOf(string $playerName): int
    {
        $bans = $this->getBanLogs()->exists($playerName) ? $this->getBanLogs()->get($playerName) : 0;
        return $bans;
    }

    /**
     * @param string $string
     * @return array|null
     * @throws \Exception
     */
    public function stringToTimestamp(string $string): ?array
    {
        /**
         * Rules:
         * Integers without suffix are considered as seconds
         * "s" is for seconds
         * "m" is for minutes
         * "h" is for hours
         * "d" is for days
         * "w" is for weeks
         * "mo" is for months
         * "y" is for years
         */
        if (trim($string) === "") {
            return null;
        }
        $t = new \DateTime();
        preg_match_all("/[0-9]+(y|mo|w|d|h|m|s)|[0-9]+/", $string, $found);
        if (count($found[0]) < 1) {
            return null;
        }
        $found[2] = preg_replace("/[^0-9]/", "", $found[0]);
        foreach ($found[2] as $k => $i) {
            switch ($c = $found[1][$k]) {
                case "y":
                case "w":
                case "d":
                    $t->add(new DateInterval("P" . $i . strtoupper($c)));
                    break;
                case "mo":
                    $t->add(new DateInterval("P" . $i . strtoupper(substr($c, 0, strlen($c) - 1))));
                    break;
                case "h":
                case "m":
                case "s":
                    $t->add(new DateInterval("PT" . $i . strtoupper($c)));
                    break;
                default:
                    $t->add(new DateInterval("PT" . $i . "S"));
                    break;
            }
            $string = str_replace($found[0][$k], "", $string);
        }
        return [$t, ltrim(str_replace($found[0], "", $string))];
    }

    /**
     * @param string $string
     * @param \DateTime $time
     * @return array|null
     * @throws \Exception
     */
    public function stringToTimestampAdd(string $string, \DateTime $time): ?array
    {
        /**
         * Rules:
         * Integers without suffix are considered as seconds
         * "s" is for seconds
         * "m" is for minutes
         * "h" is for hours
         * "d" is for days
         * "w" is for weeks
         * "mo" is for months
         * "y" is for years
         */
        if (trim($string) === "") {
            return null;
        }
        $t = $time;
        preg_match_all("/[0-9]+(y|mo|w|d|h|m|s)|[0-9]+/", $string, $found);
        if (count($found[0]) < 1) {
            return null;
        }
        $found[2] = preg_replace("/[^0-9]/", "", $found[0]);
        foreach ($found[2] as $k => $i) {
            switch ($c = $found[1][$k]) {
                case "y":
                case "w":
                case "d":
                    $t->add(new DateInterval("P" . $i . strtoupper($c)));
                    break;
                case "mo":
                    $t->add(new DateInterval("P" . $i . strtoupper(substr($c, 0, strlen($c) - 1))));
                    break;
                case "h":
                case "m":
                case "s":
                    $t->add(new DateInterval("PT" . $i . strtoupper($c)));
                    break;
                default:
                    $t->add(new DateInterval("PT" . $i . "S"));
                    break;
            }
            $string = str_replace($found[0][$k], "", $string);
        }
        return [$t, ltrim(str_replace($found[0], "", $string))];
    }

    /**
     * @param string $string
     * @param \DateTime $time
     * @return array|null
     * @throws \Exception
     */
    public function stringToTimestampReduce(string $string, \DateTime $time): ?array
    {
        /**
         * Rules:
         * Integers without suffix are considered as seconds
         * "s" is for seconds
         * "m" is for minutes
         * "h" is for hours
         * "d" is for days
         * "w" is for weeks
         * "mo" is for months
         * "y" is for years
         */
        if (trim($string) === "") {
            return null;
        }
        $t = $time;
        preg_match_all("/[0-9]+(y|mo|w|d|h|m|s)|[0-9]+/", $string, $found);
        if (count($found[0]) < 1) {
            return null;
        }
        $found[2] = preg_replace("/[^0-9]/", "", $found[0]);
        foreach ($found[2] as $k => $i) {
            switch ($c = $found[1][$k]) {
                case "y":
                case "w":
                case "d":
                    $t->sub(new DateInterval("P" . $i . strtoupper($c)));
                    break;
                case "mo":
                    $t->sub(new DateInterval("P" . $i . strtoupper(substr($c, 0, strlen($c) - 1))));
                    break;
                case "h":
                case "m":
                case "s":
                    $t->sub(new DateInterval("PT" . $i . strtoupper($c)));
                    break;
                default:
                    $t->sub(new DateInterval("PT" . $i . "S"));
                    break;
            }
            $string = str_replace($found[0][$k], "", $string);
        }
        return [$t, ltrim(str_replace($found[0], "", $string))];
    }

    /**
     * @param string $banned
     * @param string $source
     * @param string $reason
     */
    public function sendBanMessageToDC(string $banned, string $source, string $reason)
    {
        $title = str_replace(["{banned}", "{source}", "{reason}", "{line}"], [$banned, $source, $reason, "\n"], $this->getConfig()->get("ban-title"));
        $message = str_replace(["{banned}", "{source}", "{reason}", "{line}"], [$banned, $source, $reason, "\n"], $this->getConfig()->get("ban-message"));
        $color = $this->getConfig()->get("ban-color");
        if ($this->getConfig()->get("use-discord") == "true" && self::$DISCORD_WEBHOOK_URL !== null) {
            $webhook = new Webhook(self::$DISCORD_WEBHOOK_URL);
            $msg = new Message();
            $embed = new Embed();
            $embed->setTitle($title);
            $embed->setDescription($message);
            $embed->setColor($color);
            $embed->setTimestamp(new \DateTime('now'));
            $msg->addEmbed($embed);
            $webhook->send($msg);
        }
    }

    /**
     * @param string $banned
     */
    public function sendBanUpdatedMessageToDC(string $banned)
    {
        $title = str_replace(["{banned}", "{line}"], [$banned, "\n"], $this->getConfig()->get("ban-updated-title"));
        $message = str_replace(["{banned}", "{line}"], [$banned, "\n"], $this->getConfig()->get("ban-updated-message"));
        $color = $this->getConfig()->get("ban-updated-color");
        if ($this->getConfig()->get("use-discord") == "true" && self::$DISCORD_WEBHOOK_URL !== null) {
            $webhook = new Webhook(self::$DISCORD_WEBHOOK_URL);
            $msg = new Message();
            $embed = new Embed();
            $embed->setTitle($title);
            $embed->setDescription($message);
            $embed->setColor($color);
            $embed->setTimestamp(new \DateTime('now'));
            $msg->addEmbed($embed);
            $webhook->send($msg);
        }
    }

    /**
     * @param string $target
     * @param string $source
     */
    public function sendPardonMessageToDC(string $target, string $source)
    {
        $title = str_replace(["{target}", "{source}", "{line}"], [$target, $source, "\n"], $this->getConfig()->get("pardon-title"));
        $message = str_replace(["{target}", "{source}", "{line}"], [$target, $source, "\n"], $this->getConfig()->get("pardon-message"));
        $color = $this->getConfig()->get("pardon-color");
        if ($this->getConfig()->get("use-discord") == "true" && self::$DISCORD_WEBHOOK_URL !== null) {
            $webhook = new Webhook(self::$DISCORD_WEBHOOK_URL);
            $msg = new Message();
            $embed = new Embed();
            $embed->setTitle($title);
            $embed->setDescription($message);
            $embed->setColor($color);
            $embed->setTimestamp(new \DateTime('now'));
            $msg->addEmbed($embed);
            $webhook->send($msg);
        }
    }

    /**
     * @param string $ip
     * @param string $source
     * @param string $reason
     */
    public function sendIpBanMessageToDC(string $ip, string $source, string $reason)
    {
        $title = str_replace(["{ip}", "{source}", "{reason}", "{line}"], [$ip, $source, $reason, "\n"], $this->getConfig()->get("banip-title"));
        $message = str_replace(["{ip}", "{source}", "{reason}", "{line}"], [$ip, $source, $reason, "\n"], $this->getConfig()->get("banip-message"));
        $color = $this->getConfig()->get("banip-color");
        if ($this->getConfig()->get("use-discord") == "true" && self::$DISCORD_WEBHOOK_URL !== null) {
            $webhook = new Webhook(self::$DISCORD_WEBHOOK_URL);
            $msg = new Message();
            $embed = new Embed();
            $embed->setTitle($title);
            $embed->setDescription($message);
            $embed->setColor($color);
            $embed->setTimestamp(new \DateTime('now'));
            $msg->addEmbed($embed);
            $webhook->send($msg);
        }
    }

    /**
     * @param string $ip
     */
    public function sendIpBanUpdatedMessageToDC(string $ip)
    {
        $title = str_replace(["{ip}", "{line}"], [$ip, "\n"], $this->getConfig()->get("ipban-updated-title"));
        $message = str_replace(["{ip}", "{line}"], [$ip, "\n"], $this->getConfig()->get("ipban-updated-message"));
        $color = $this->getConfig()->get("ipban-updated-color");
        if ($this->getConfig()->get("use-discord") == "true" && self::$DISCORD_WEBHOOK_URL !== null) {
            $webhook = new Webhook(self::$DISCORD_WEBHOOK_URL);
            $msg = new Message();
            $embed = new Embed();
            $embed->setTitle($title);
            $embed->setDescription($message);
            $embed->setColor($color);
            $embed->setTimestamp(new \DateTime('now'));
            $msg->addEmbed($embed);
            $webhook->send($msg);
        }
    }

    /**
     * @param string $ip
     * @param string $source
     */
    public function sendPardonIpMessageToDC(string $ip, string $source)
    {
        $title = str_replace(["{ip}", "{source}", "{line}"], [$ip, $source, "\n"], $this->getConfig()->get("pardonip-title"));
        $message = str_replace(["{ip}", "{source}", "{line}"], [$ip, $source, "\n"], $this->getConfig()->get("pardonip-message"));
        $color = $this->getConfig()->get("pardonip-color");
        if ($this->getConfig()->get("use-discord") == "true" && self::$DISCORD_WEBHOOK_URL !== null) {
            $webhook = new Webhook(self::$DISCORD_WEBHOOK_URL);
            $msg = new Message();
            $embed = new Embed();
            $embed->setTitle($title);
            $embed->setDescription($message);
            $embed->setColor($color);
            $embed->setTimestamp(new \DateTime('now'));
            $msg->addEmbed($embed);
            $webhook->send($msg);
        }
    }

    /**
     * @param string $target
     * @param string $source
     */
    public function sendKickMessageToDC(string $target, string $source)
    {
        $title = str_replace(["{target}", "{source}", "{line}"], [$target, $source, "\n"], $this->getConfig()->get("kick-dc-title"));
        $message = str_replace(["{target}", "{source}", "{line}"], [$target, $source, "\n"], $this->getConfig()->get("kick-dc-message"));
        $color = $this->getConfig()->get("kick-dc-color");
        if ($this->getConfig()->get("use-discord") == "true" && self::$DISCORD_WEBHOOK_URL !== null) {
            $webhook = new Webhook(self::$DISCORD_WEBHOOK_URL);
            $msg = new Message();
            $embed = new Embed();
            $embed->setTitle($title);
            $embed->setDescription($message);
            $embed->setColor($color);
            $embed->setTimestamp(new \DateTime('now'));
            $msg->addEmbed($embed);
            $webhook->send($msg);
        }
    }
}