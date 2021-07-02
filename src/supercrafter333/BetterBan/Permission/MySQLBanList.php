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

    /** @var string $table */
    private $table;

    /**
     * MySQLBanList constructor.
     * @param string[] $settings
     * @param string $table
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
        $stmt = $this->db->prepare('CREATE TABLE IF NOT EXISTS ? ()'); // TODO: add columns
        $stmt->bind_param('s', $this->table);
        $stmt->execute();
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
