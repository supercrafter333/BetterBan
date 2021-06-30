<?php

namespace supercrafter333\BetterBan\Provider;

/**
 * Interface BBDefaultProvider
 * @package supercrafter333\BetterBan\Provider
 */
interface BBDefaultProvider
{
    //TODO: add provider for MySQL name bans (and IP bans after that)

    /**
     * BBDefaultProvider constructor.
     * @param string $target
     */
    public function __construct(string $target);

    /**
     * @return string
     */
    public function getTarget(): string;

    /**
     * @return \DateTime
     */
    public function getCreated() : \DateTime;

    /**
     * @return void
     */
    public function setCreated(\DateTime $date);

    /**
     * @return string
     */
    public function getSource() : string;

    /**
     * @return void
     */
    public function setSource(string $source);

    /**
     * @return \DateTime|null
     */
    public function getExpires();

    /**
     * @return void
     */
    public function setExpires(\DateTime $date = null);

    /**
     * @return bool
     */
    public function hasExpired() : bool;

    /**
     * @return string
     */
    public function getReason() : string;

    /**
     * @return void
     */
    public function setReason(string $reason);

    /**
     * @return string
     */
    public function getString() : string;

    /**
     * Hacky function to validate \DateTime objects due to a bug in PHP. format() with "Y" can emit years with more than
     * 4 digits, but createFromFormat() with "Y" doesn't accept them if they have more than 4 digits on the year.
     *
     * @link https://bugs.php.net/bug.php?id=75992
     *
     * @throws \RuntimeException if the argument can't be parsed from a formatted date string
     */
    public static function validateDate(\DateTime $dateTime) : void;

    /**
     * @throws \RuntimeException
     */
    public static function parseDate(string $date) : \DateTime;

    /**
     * @throws \RuntimeException
     */
    public static function fromString(string $str) : ?self;

    /**
     * @param string $name
     * @return $this|null
     */
    public function getEntry(string $name) : ?self;

    /**
     * @return self[]
     */
    public function getEntries() : array;

    /**
     * @return bool
     */
    public function isBanned() : bool;

    /**
     * @return void
     */
    public function add(self $entry);

    /**
     * @return void
     */
    public function remove(string $name);

    /**
     * @return void
     */
    public function removeExpired();

    /**
     * @return void
     */
    public function load();

    /**
     * @return void
     */
    public function save(bool $writeHeader = true);
}