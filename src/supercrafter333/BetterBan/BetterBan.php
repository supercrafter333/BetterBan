<?php

/*
 *
 *  ____       _   _            ____
 * |  _ \     | | | |          |  _ \
 * | |_) | ___| |_| |_ ___ _ __| |_) | __ _ _ __
 * |  _ < / _ \ __| __/ _ \ '__|  _ < / _` | '_ \
 * | |_) |  __/ |_| ||  __/ |  | |_) | (_| | | | |
 * |____/ \___|\__|\__\___|_|  |____/ \__,_|_| |_|
 *
 * Copyright (c) 2023 by supercrafter333
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at: https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author supercrafter333
 * @link https://github.com/supercrafter333/BetterBan
 *
 */

declare(strict_types=1);

namespace supercrafter333\BetterBan;

use CortexPE\DiscordWebhookAPI\Embed;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use DateInterval;
use DateTime;
use dktapps\pmforms\BaseForm;
use Exception;
use pocketmine\permission\PermissionManager;
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
use function array_keys;
use function array_values;
use function class_exists;
use function count;
use function intval;
use function ltrim;
use function preg_match_all;
use function preg_replace;
use function rename;
use function str_replace;
use function strlen;
use function strtoupper;
use function substr;
use function trim;

/**
 * Class BetterBan
 * @package supercrafter333\BetterBan
 */
class BetterBan extends PluginBase {
	protected static BetterBan $instance;

	/**
	 * Version of BetterBan
	 */
	public const VERSION = "4.2.2";


	public static $DISCORD_WEBHOOK_URL = null;

	private MySQLBanList $mysqlBanByName;

	private MySQLBanList $mysqlBanByIP;

	/**
	 * On Plugin Loading
	 */
	public function onLoad() : void {
		self::$instance = $this;
		$this->saveResource("config.yml");
		$this->versionCheck($this->getConfig()->get("version") < "4.0.1"); //only update when version is lower than v4.0.1

		$dc_webhook = $this->getConfig()->get("discord-webhook") !== "" ? $this->getConfig()->get("discord-webhook") : null;
		self::$DISCORD_WEBHOOK_URL = $dc_webhook;

		if (!class_exists(BaseForm::class)) {
			$this->getLogger()->error("pmforms missing!! Please download BetterBan from Poggit!");
		}
		if (!class_exists(Webhook::class)) {
			$this->getLogger()->error("DiscordWebhookAPI missing!! Please download BetterBan from Poggit!");
			if (self::$DISCORD_WEBHOOK_URL !== null) {
				$this->getServer()->getPluginManager()->disablePlugin($this);
			}
		}
	}

	/**
	 * On Plugin Enabling
	 */
	public function onEnable() : void {
		if ($this->useMySQL()) {
			$this->mysqlBanByName = new MySQLBanList($this->getMySQLSettings(), MySQLBanList::TABLE_NAMEBANS);
			$this->mysqlBanByIP = new MySQLBanList($this->getMySQLSettings(), MySQLBanList::TABLE_IPBANS);
		}
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

		//Add bypass permission "BetterBan.admin"
		$betterBanAdmin = PermissionManager::getInstance()->getPermission("BetterBan.admin");
		$children = ["BetterBan.editban.cmd", "BetterBan.banlog.cmd", "BetterBan.baninfo.cmd", "BetterBan.editipban.cmd", "BetterBan.betterban.cmd"];
		foreach ($children as $child) {
			$betterBanAdmin->addChild($child, true);
		}
	}


	public function onDisable() : void {
		if (isset($this->mysqlBanByName)) {
			$this->getMySQLNameBans()->close();
		}
		if (isset($this->mysqlBanByIP)) {
			$this->getMySQLIpBans()->close();
		}
	}

	/**
	 * @return static
	 */
	public static function getInstance() : self {
		return self::$instance;
	}


	public static function isBanned(string $name) : bool {
		$pl = self::getInstance();
		if ($pl->useMySQL()) {
			return $pl->getMySQLNameBans()->isBanned($name);
		}
		return $pl->getServer()->getNameBans()->isBanned($name);
	}


	public static function isBannedIp(string $ip) : bool {
		$pl = self::getInstance();
		if ($pl->useMySQL()) {
			return $pl->getMySQLIpBans()->isBanned($ip);
		}
		return $pl->getServer()->getIPBans()->isBanned($ip);
	}


	public static function pmformsExists() : bool {
		return class_exists(\dktapps\pmforms\BaseForm::class);
	}

	/**
	 * @throws Exception
	 */
	public static function pmformsNotFoundError() {
		throw new Exception("Can't find virion pmforms (https://github.com/dktapps-pm-pl/pmforms)! Please download BetterBan from poggit (https://poggit.pmmp.io/p/BetterBan).");
	}


	private function versionCheck(bool $update = true) {
		if (!$this->getConfig()->exists("version") || $this->getConfig()->get("version") !== self::VERSION) {
			if ($update == true) {
				$this->getLogger()->debug("OUTDATED CONFIG.YML!! You config.yml is outdated! Your config.yml will automatically updated!");
				rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "oldConfig.yml");
				$this->saveResource("config.yml");
				$this->getLogger()->debug("config.yml Updated for version: §b" . (self::VERSION) . "");
			} else {
				$this->getLogger()->warning("Your config.yml is outdated but that's not so bad.");
			}
		}
	}


	public function getMySQLNameBans() : MySQLBanList {
		return $this->mysqlBanByName;
	}

	/**
	 * @return MySQLBanList
	 */
	public function getMySQLIpBans() {
		return $this->mysqlBanByIP;
	}


	public function getBanLogs() : Config {
		return new Config($this->getDataFolder() . "banLogs.yml", Config::YAML);
	}


	public function useMySQL() : bool {
		if ($this->getConfig()->get("use-MySQL") !== "true") {
			return false;
		}
		return true;
	}


	public function getMySQLSettings() : array {
		return $this->getConfig()->get("MySQL", []);
	}


	public function addBanToBanlog(string $playerName) {
		$log = $this->getBanLogs();
		if ($log->exists($playerName)) {
			$log->set($playerName, intval($log->get($playerName) + 1));
		} else {
			$log->set($playerName, 1);
		}
		$log->save();
	}


	public function getBanLogOf(string $playerName) : int {
		return $this->getBanLogs()->exists($playerName) ? $this->getBanLogs()->get($playerName) : 0;
	}

	/**
	 * @throws Exception
	 */
	public function stringToTimestamp(string $string) : ?array {
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
		$t = new DateTime();
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
	 * @throws Exception
	 */
	public function stringToTimestampAdd(string $string, DateTime $time) : ?array {
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
	 * @throws Exception
	 */
	public function stringToTimestampReduce(string $string, DateTime $time) : ?array {
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
	 * Pretty Format instead of use DateTime()->format() function.
	 * DateTime::diff()->format() is suitable creating date format.
	 */
	public function toPrettyFormat(DateTime $duration, bool $legacy = false) : string {
		if ($legacy) {
			return $duration->format($this->getConfig()->get("legacy-dateformat", "d.m.Y H:i:s"));
		}

		$now = new DateTime('NOW');
		$interval = $duration->diff($now);
		$output = $this->getConfig()->get("dateformat", "{year} year(s), {month} month(s), {day} day(s), {hours} hour(s), {minute} minute(s)");
		// Subtitute {PREFIX}, {YEARS}, etc... to a literal format.

		$dateformat = [
			"{PREFIX}" => "%",
			"{prefix}" => "%",
			"{YEAR}" => "%Y",
			"{year}" => "%y",
			"{MONTH}" => "%M",
			"{month}" => "%m",
			"{DAY}" => "%D",
			"{day}" => "%d",
			"{TOTAL_DAYS}" => "%a",
			"{total_days}" => "%a",
			"{HOUR}" => "%H",
			"{hour}" => "%h",
			"{MINUTE}" => "%I",
			"{minute}" => "%i",
			"{SECOND}" => "%S",
			"{second}" => "%s",
			"{MICROSECOND}" => "%F",
			"{microsecond}" => "%f",
			"{SIGN}" => "%R",
			"{sign}" => "%r"
		];

		$output = str_replace(array_keys($datetime), array_values($datetime), $output);
		return $interval->format($output);
	}


	public function sendBanMessageToDC(string $banned, string $source, string $reason) {
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
			$embed->setTimestamp(new DateTime('now'));
			$msg->addEmbed($embed);
			$webhook->send($msg);
		}
	}


	public function sendBanUpdatedMessageToDC(string $banned) {
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
			$embed->setTimestamp(new DateTime('now'));
			$msg->addEmbed($embed);
			$webhook->send($msg);
		}
	}


	public function sendPardonMessageToDC(string $target, string $source) {
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
			$embed->setTimestamp(new DateTime('now'));
			$msg->addEmbed($embed);
			$webhook->send($msg);
		}
	}


	public function sendIpBanMessageToDC(string $ip, string $source, string $reason) {
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
			$embed->setTimestamp(new DateTime('now'));
			$msg->addEmbed($embed);
			$webhook->send($msg);
		}
	}


	public function sendIpBanUpdatedMessageToDC(string $ip) {
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
			$embed->setTimestamp(new DateTime('now'));
			$msg->addEmbed($embed);
			$webhook->send($msg);
		}
	}


	public function sendPardonIpMessageToDC(string $ip, string $source) {
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
			$embed->setTimestamp(new DateTime('now'));
			$msg->addEmbed($embed);
			$webhook->send($msg);
		}
	}


	public function sendKickMessageToDC(string $target, string $source) {
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
			$embed->setTimestamp(new DateTime('now'));
			$msg->addEmbed($embed);
			$webhook->send($msg);
		}
	}
}
