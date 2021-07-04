<?php

namespace supercrafter333\BetterBan\Permission;

use pocketmine\permission\BanEntry;

/**
 * Class MySQLBanList
 * @package supercrafter333\BetterBan\Permission
 */
class MySQLBanList
{

    /** @var \mysqli $db */
    private $db;

    /** @var array $settings */
    private $settings;

    /**
     * MySQLBanList constructor.
     * @param string[] $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->db = new \mysqli($settings['host'], $settings['user'], $settings['password'], $settings['database']);
        if($this->db->connect_errno)
            throw new \RuntimeException('[BetterBan] ' . $this->db->connect_error);
        $this->init();
    }

    private function init(): void
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS bans(target VARCHAR(255) NOT NULL, creationDate VARCHAR(255) NOT NULL, source VARCHAR(255) NOT NULL, expirationDate VARCHAR(255), reason TEXT NOT NULL)');
    }

    public function getEntry(string $target): ?BanEntry
    {
        // TODO: add functionality...
    }

    /**
     * @return BanEntry[]
     */
    public function getEntries(): array
    {
        // TODO: add functionality...
    }

    public function isBanned(): bool
    {
        // TODO: add functionality...
    }

    public function add(BanEntry $entry): void
    {
        // TODO: add functionality...
    }

    public function addBan(string $target, string $reason = null, \DateTime $expires = null, string $source = null): BanEntry
    {
        // TODO: add functionality...
    }

    public function remove(string $name): void
    {
        // TODO: add functionality...
    }

    public function removeExpired(): void
    {
        // TODO: add functionality...
    }

}
