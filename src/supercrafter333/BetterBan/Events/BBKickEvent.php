<?php

namespace supercrafter333\BetterBan\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;
use supercrafter333\BetterBan\BetterBan;

/**
 * Class BBKickEvent
 * @package supercrafter333\BetterBan\Events
 */
class BBKickEvent extends Event implements Cancellable
{
    use CancellableTrait;

    /**
     * @var Player
     */
    protected $target;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string|null
     */
    protected $reason;

    /**
     * BBKickEvent constructor.
     * @param Player $target
     * @param string $source
     */
    public function __construct(Player $target, string $source, string $reason = null)
    {
        $this->eventName = "BBKickEvent";
        $this->target = $target;
        $this->source = $source;
        $this->reason = $reason;
    }

    /**
     * @return Player
     */
    public function getTarget(): Player
    {
        return $this->target;
    }

    /**
     * @return string
     */
    public function getTargetName(): string
    {
        return $this->target->getName();
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @param Player $target
     */
    public function setTarget(Player $target): void
    {
        $this->target = $target;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    /**
     * @param string $reason
     */
    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    /**
     * Kick the Target Player
     */
    public function kickTarget(): void
    {
        if (!$this->isCancelled()) {
            $cfg = BetterBan::getInstance()->getConfig();
            if ($this->reason !== null) {
                $this->target->kick(str_replace(["{reason}", "{source}", "{line}"], [$this->reason, $this->source, "\n"], $cfg->get("kicked-with-reason-message")));
                return;
            } else {
                $this->target->kick(str_replace(["{source}", "{line}"], [$this->source, "\n"], $cfg->get("kicked-message")));
                return;
            }
        } else {
            BetterBan::getInstance()->getLogger()->warning("Can't kick " . $this->getTargetName() . " because the kick event is cancelled!");
            return;
        }
    }

    /**
     * Send the Discord-Webhook Message
     */
    public function sendDiscordWebhookMessage(): void
    {
        BetterBan::getInstance()->sendKickMessageToDC($this->getTargetName(), $this->source);
    }
}