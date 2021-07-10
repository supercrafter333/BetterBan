<?php

namespace supercrafter333\BetterBan\Permission;

use pocketmine\permission\BanEntry;

/**
 * Class MySQLBanList
 * @package supercrafter333\BetterBan\Permission
 */
class MySQLBanList
{
    public const TABLE_NAMEBANS = 'banned_players';
    public const TABLE_IPBANS = 'banned_ips';

    /** @var \mysqli $db */
    private $db;

    /** @var array $settings */
    private $settings;

    /** @var string $table */
    private $table;

    /**
     * MySQLBanList constructor.
     * @param string[] $settings
     */
    public function __construct(array $settings, string $table)
    {
        $this->settings = $settings;
        $this->table = $table;
        $this->db = new \mysqli($settings['host'], $settings['user'], $settings['password'], $settings['database']);
        if($this->db->connect_errno)
            throw new \RuntimeException('[BetterBan] ' . $this->db->connect_error);
        $this->init();
    }

    private function init(): void
    {
        if($this->table === self::TABLE_NAMEBANS) {
            $this->db->query('CREATE TABLE IF NOT EXISTS banned_players(target VARCHAR(255) NOT NULL, creationDate VARCHAR(255) NOT NULL, source VARCHAR(255) NOT NULL, expirationDate VARCHAR(255), reason TEXT NOT NULL)');
        } elseif($this->table === self::TABLE_IPBANS) {
            $this->db->query('CREATE TABLE IF NOT EXISTS banned_ips(target VARCHAR(255) NOT NULL, creationDate VARCHAR(255) NOT NULL, source VARCHAR(255) NOT NULL, expirationDate VARCHAR(255), reason TEXT NOT NULL)');
        }
    }

    private function reconnect(): bool
    {
        if(!$this->db->ping()) {
            $settings = $this->settings;
            $this->db = new \mysqli($settings['host'], $settings['user'], $settings['password'], $settings['database']);
            if($this->db->connect_errno)
                return false;
        }
        return true;
    }

    public function getEntry(string $target): ?BanEntry
    {
        if(!$this->reconnect())
            throw new \mysqli_sql_exception('Could not connect to the database!');
        $this->removeExpired();
        if($this->table === self::TABLE_IPBANS) {
            $stmt = $this->db->prepare('SELECT * FROM banned_ips WHERE target=?;');
        } else {
            $stmt = $this->db->prepare('SELECT * FROM banned_players WHERE target=?;');
        }
        if(!$stmt)
            return null;
        $stmt->bind_param('s', $target);
        $state = $stmt->execute();
        if(!$state)
            return null;
        $result = $stmt->get_result();
        if(!$result)
            return null;
        $stmt->close();
        return self::fromAssocArray($result->fetch_assoc());
    }

    /**
     * @return BanEntry[]
     */
    public function getEntries(): array
    {
        if(!$this->reconnect())
            throw new \mysqli_sql_exception('Could not connect to the database!');
        $this->removeExpired();
        if($this->table === self::TABLE_IPBANS) {
            $data = $this->db->query('SELECT * FROM banned_ips;');
        } else {
            $data = $this->db->query('SELECT * FROM banned_players;');
        }
        if(!$data)
            return [];
        $entries = [];
        while ($row = $data->fetch_assoc()) {
            $entries[] = self::fromAssocArray($row);
        }
        return $entries;
    }

    public function isBanned(string $target): bool
    {
        if(!$this->reconnect())
            throw new \mysqli_sql_exception('Could not connect to the database!');
        $this->removeExpired();
        if($this->table === self::TABLE_IPBANS) {
            $stmt = $this->db->prepare('SELECT * FROM banned_ips WHERE target=?;');
        } else {
            $stmt = $this->db->prepare('SELECT * FROM banned_players WHERE target=?;');
        }
        if(!$stmt)
            return false;
        $stmt->bind_param('s', $target);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->num_rows === 1;
    }

    public function add(BanEntry $entry): void
    {
        if(!$this->reconnect())
            throw new \mysqli_sql_exception('Could not connect to the database!');
        $this->addBan($entry->getName(), $entry->getReason(), $entry->getExpires(), $entry->getSource());
    }

    /**
     * TODO: Ban overwrite if target already banned
     */
    public function addBan(string $target, string $reason = null, \DateTime $expires = null, string $source = null): BanEntry
    {
        if(!$this->reconnect())
            throw new \mysqli_sql_exception('Could not connect to the database!');
        $this->removeExpired();
        $entry = new BanEntry($target);
        $entry->setSource($source ?? $entry->getSource());
        $entry->setExpires($expires);
        $entry->setReason($reason ?? $entry->getReason());
        if($this->table === self::TABLE_IPBANS) {
            $stmt = $this->db->prepare('INSERT INTO banned_ips(target, creationDate, source, expirationDate, reason) VALUES(?,?,?,?,?);');
        } else {
            $stmt = $this->db->prepare('INSERT INTO banned_players(target, creationDate, source, expirationDate, reason) VALUES(?,?,?,?,?);');
        }
        $creation = $entry->getCreated()->format(BanEntry::$format);
        $source = $entry->getSource();
        $expires = $entry->getExpires() === null ? "Forever" : $entry->getExpires()->format(BanEntry::$format);
        $reason = $entry->getReason();
        $stmt->bind_param('sssss', $target,$creation, $source, $expires, $reason);
        $stmt->execute();
        $stmt->close();
        return $entry;
    }

    public function remove(string $target): void
    {
        if(!$this->reconnect())
            throw new \mysqli_sql_exception('Could not connect to the database!');
        $this->removeExpired();
        if($this->table === self::TABLE_IPBANS) {
            $stmt = $this->db->prepare('DELETE FROM banned_ips WHERE target=?;');
        } else {
            $stmt = $this->db->prepare('DELETE FROM banned_players WHERE target=?;');
        }
        $stmt->bind_param('s', $target);
        $stmt->execute();
        $stmt->close();
    }

    public function removeExpired(): void
    {
        // TODO: add functionality...
    }

    private static function fromAssocArray(array $data): BanEntry
    {
        $entry = new BanEntry($data['target']);
        $entry->setCreated(\DateTime::createFromFormat(BanEntry::$format, $data['creationDate']));
        $entry->setSource($data['source']);
        $expirationDate = $data['expirationDate'];
        if($expirationDate != null)
            $entry->setExpires(\DateTime::createFromFormat(BanEntry::$format, $expirationDate));
        $entry->setReason($data['reason']);
        return $entry;
    }

}
