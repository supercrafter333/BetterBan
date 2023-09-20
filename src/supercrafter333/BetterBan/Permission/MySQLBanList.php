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

namespace supercrafter333\BetterBan\Permission;

use pocketmine\permission\BanEntry;
use function is_bool;
use function is_null;
use function strtolower;

/**
 * Class MySQLBanList
 * @package supercrafter333\BetterBan\Permission
 */
class MySQLBanList {
	public const TABLE_NAMEBANS = 'banned_players';
	public const TABLE_IPBANS = 'banned_ips';

	private \mysqli $db;

	/** @var string[] $settings */
	private array $settings;

	private string $table;

	/**
	 * MySQLBanList constructor.
	 * @param string[] $settings
	 */
	public function __construct(array $settings, string $table) {
		$this->settings = $settings;
		$this->table = $table;
		$this->db = new \mysqli($settings['host'], $settings['user'], $settings['password'], $settings['database'], $settings['port']);
		if ($this->db->connect_errno) {
			throw new \RuntimeException('[BetterBan] ' . $this->db->connect_error);
		}
		$this->init();
	}

	private function init() : void {
		if ($this->table === self::TABLE_NAMEBANS) {
			$this->db->query('CREATE TABLE IF NOT EXISTS banned_players(target VARCHAR(255) NOT NULL, creationDate VARCHAR(255) NOT NULL, source VARCHAR(255) NOT NULL, expirationDate VARCHAR(255), reason TEXT NOT NULL)');
		} elseif ($this->table === self::TABLE_IPBANS) {
			$this->db->query('CREATE TABLE IF NOT EXISTS banned_ips(target VARCHAR(255) NOT NULL, creationDate VARCHAR(255) NOT NULL, source VARCHAR(255) NOT NULL, expirationDate VARCHAR(255), reason TEXT NOT NULL)');
		}
	}

	private function reconnect() : bool {
		if (!$this->db->ping()) {
			$settings = $this->settings;
			$this->db = new \mysqli($settings['host'], $settings['user'], $settings['password'], $settings['database']);
			if ($this->db->connect_errno) {
				return false;
			}
		}
		return true;
	}

	public function close() : void {
		try {
			$this->db->close();
		} catch(\Exception $e) {
		}
	}

	public function getEntry(string $target) : ?BanEntry {
		if (!$this->reconnect()) {
			throw new \mysqli_sql_exception('Could not connect to the database!');
		}
		$this->removeExpired();
		if ($this->table === self::TABLE_IPBANS) {
			$stmt = $this->db->prepare('SELECT * FROM banned_ips WHERE target=?;');
		} else {
			$stmt = $this->db->prepare('SELECT * FROM banned_players WHERE target=?;');
		}
		if (!$stmt) {
			return null;
		}
		$target = strtolower($target);
		$stmt->bind_param('s', $target);
		$state = $stmt->execute();
		if (!$state) {
			return null;
		}
		$result = $stmt->get_result();
		if (!$result) {
			return null;
		}
		$stmt->close();
		$data = $result->fetch_assoc();
		if (is_null($data)) {
			return null;
		}
		return self::fromAssocArray($data);
	}

	/**
	 * @return BanEntry[]
	 */
	public function getEntries(bool $removeExpired = true) : array {
		if (!$this->reconnect()) {
			throw new \mysqli_sql_exception('Could not connect to the database!');
		}
		if ($removeExpired) {
			$this->removeExpired();
		}
		if ($this->table === self::TABLE_IPBANS) {
			$result = $this->db->query('SELECT * FROM banned_ips;');
		} else {
			$result = $this->db->query('SELECT * FROM banned_players;');
		}
		if (is_bool($result)) {
			return [];
		}
		$entries = [];
		while ($row = $result->fetch_assoc()) {
			$entries[] = self::fromAssocArray($row);
		}
		return $entries;
	}

	public function isBanned(string $target) : bool {
		if (!$this->reconnect()) {
			throw new \mysqli_sql_exception('Could not connect to the database!');
		}
		$this->removeExpired();
		if ($this->table === self::TABLE_IPBANS) {
			$stmt = $this->db->prepare('SELECT * FROM banned_ips WHERE target=?;');
		} else {
			$stmt = $this->db->prepare('SELECT * FROM banned_players WHERE target=?;');
		}
		if (!$stmt) {
			throw new \mysqli_sql_exception('Error while preparing a mysql statement!');
		}
		$target = strtolower($target);
		$stmt->bind_param('s', $target);
		$stmt->execute();
		$result = $stmt->get_result();
		if (!$result) {
			throw new \mysqli_sql_exception('An error has occurred!');
		}
		$stmt->close();
		return $result->num_rows === 1;
	}

	public function add(BanEntry $entry) : void {
		if (!$this->reconnect()) {
			throw new \mysqli_sql_exception('Could not connect to the database!');
		}
		$this->addBan($entry->getName(), $entry->getReason(), $entry->getExpires(), $entry->getSource());
	}

	public function addBan(string $target, string $reason = null, \DateTime $expires = null, string $source = null) : BanEntry {
		if (!$this->reconnect()) {
			throw new \mysqli_sql_exception('Could not connect to the database!');
		}
		$target = strtolower($target);
		$entry = new BanEntry($target);
		$entry->setSource($source ?? $entry->getSource());
		$entry->setExpires($expires);
		$entry->setReason($reason ?? $entry->getReason());
		if ($this->isBanned($target)) {
			$this->overwriteBan($entry);
			return $entry;
		}
		if ($this->table === self::TABLE_IPBANS) {
			$stmt = $this->db->prepare('INSERT INTO banned_ips(target, creationDate, source, expirationDate, reason) VALUES(?,?,?,?,?);');
		} else {
			$stmt = $this->db->prepare('INSERT INTO banned_players(target, creationDate, source, expirationDate, reason) VALUES(?,?,?,?,?);');
		}
		if (!$stmt) {
			throw new \mysqli_sql_exception('Error while preparing a mysql statement!');
		}
		$creation = $entry->getCreated()->format(BanEntry::$format);
		$source = $entry->getSource();
		$expires = $entry->getExpires() === null ? "Forever" : $entry->getExpires()->format(BanEntry::$format);
		$reason = $entry->getReason();
		$stmt->bind_param('sssss', $target, $creation, $source, $expires, $reason);
		$stmt->execute();
		$stmt->close();
		return $entry;
	}

	public function remove(string $target) : void {
		if (!$this->reconnect()) {
			throw new \mysqli_sql_exception('Could not connect to the database!');
		}
		if ($this->table === self::TABLE_IPBANS) {
			$stmt = $this->db->prepare('DELETE FROM banned_ips WHERE target=?;');
		} else {
			$stmt = $this->db->prepare('DELETE FROM banned_players WHERE target=?;');
		}
		if (!$stmt) {
			throw new \mysqli_sql_exception('Error while preparing a mysql statement!');
		}
		$stmt->bind_param('s', $target);
		$stmt->execute();
		$stmt->close();
	}

	public function removeExpired() : void {
		$entries = $this->getEntries(false);
		foreach ($entries as $entry) {
			if ($entry->hasExpired()) {
				$this->remove($entry->getName());
			}
		}
	}

	private function overwriteBan(BanEntry $entry) : void {
		if (!$this->reconnect()) {
			throw new \mysqli_sql_exception('Could not connect to the database!');
		}
		if ($this->table === self::TABLE_IPBANS) {
			$stmt = $this->db->prepare('UPDATE banned_ips SET creationDate=?, source=?, expirationDate=?, reason=? WHERE target=?;');
		} else {
			$stmt = $this->db->prepare('UPDATE banned_players SET creationDate=?, source=?, expirationDate=?, reason=? WHERE target=?;');
		}
		if (!$stmt) {
			throw new \mysqli_sql_exception('Error while preparing a mysql statement!');
		}
		$target = $entry->getName();
		$creation = $entry->getCreated()->format(BanEntry::$format);
		$source = $entry->getSource();
		$expires = $entry->getExpires() === null ? "Forever" : $entry->getExpires()->format(BanEntry::$format);
		$reason = $entry->getReason();
		$stmt->bind_param('sssss', $creation, $source, $expires, $reason, $target);
		$stmt->execute();
		$stmt->close();
	}

	/**
	 * @param string[] $data
	 */
	private static function fromAssocArray(array $data) : BanEntry {
		$creation = \DateTime::createFromFormat(BanEntry::$format, $data['creationDate']);
		$creation = $creation !== false ? $creation : new \DateTime();
		$entry = new BanEntry($data['target']);
		$entry->setCreated($creation);
		$entry->setSource($data['source']);
		$expirationDate = $data['expirationDate'];
		if ($expirationDate != null && strtolower($expirationDate) != 'forever') {
			$expires = \DateTime::createFromFormat(BanEntry::$format, $expirationDate);
			$expires = $expires !== false ? $expires : null;
			$entry->setExpires($expires);
		}
		$entry->setReason($data['reason']);
		return $entry;
	}
}