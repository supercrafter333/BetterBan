<?php

namespace supercrafter333\BetterBan\Provider;

interface BBDefaultProvider
{
    //TODO: add provider for MySQL name bans (and IP bans after that)

    public function __construct(string $target);

    public function geTarget(): string;

    public function getCreated() : \DateTime;

    /**
     * @return void
     */
    public function setCreated(\DateTime $date);

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

    public function hasExpired() : bool;

    public function getReason() : string;

    /**
     * @return void
     */
    public function setReason(string $reason);

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
}