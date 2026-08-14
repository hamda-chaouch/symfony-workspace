<?php

namespace App\Entity\TradingBot;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TradingBot\AlarmRepository::class)]
#[ORM\Table(
    name: "alarms",
    indexes: [
        new ORM\Index(name: "idx_alarm_symbol_created", columns: ["symbol", "created_at"]),
        new ORM\Index(name: "idx_alarm_direction", columns: ["direction"]),
        new ORM\Index(name: "idx_alarm_strength", columns: ["strength"]),
        new ORM\Index(name: "idx_alarm_run_id", columns: ["run_id"])
    ]
)]
class Alarm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "version", type: "string", length: 20)]
    private string $version;

    #[ORM\Column(name: "run_id", type: "string", length: 64)]
    private string $runId;

    #[ORM\Column(name: "symbol", type: "string", length: 50)]
    private string $symbol = 'BTC/USDT';

    #[ORM\Column(name: "source", type: "string", length: 100, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(name: "direction", type: "string", length: 10)]
    private string $direction = 'NEUTRAL';

    #[ORM\Column(name: "alarm_type", type: "string", length: 50, nullable: true)]
    private ?string $alarmType = null;

    #[ORM\Column(name: "timeframe_used", type: "string", length: 10, nullable: true)]
    private ?string $timeframeUsed = null;

    #[ORM\Column(name: "price_now", type: "decimal", precision: 20, scale: 8)]
    private string $priceNow = '0.00000000';

    #[ORM\Column(name: "price_1m_ago", type: "decimal", precision: 20, scale: 8)]
    private string $price1mAgo = '0.00000000';

    #[ORM\Column(name: "price_5m_ago", type: "decimal", precision: 20, scale: 8)]
    private string $price5mAgo = '0.00000000';

    #[ORM\Column(name: "speed_1m", type: "decimal", precision: 10, scale: 4)]
    private string $speed1m = '0.0000';

    #[ORM\Column(name: "speed_5m", type: "decimal", precision: 10, scale: 4)]
    private string $speed5m = '0.0000';

    #[ORM\Column(name: "delta_pct", type: "decimal", precision: 10, scale: 4)]
    private string $deltaPct = '0.0000';

    #[ORM\Column(name: "delta_usd", type: "decimal", precision: 20, scale: 8)]
    private string $deltaUsd = '0.00000000';

    #[ORM\Column(name: "strength", type: "smallint", options: ["unsigned" => true])]
    private int $strength = 0;

    #[ORM\Column(name: "entry_confidence", type: "decimal", precision: 10, scale: 4, nullable: true)]
    private ?string $entryConfidence = null;

    #[ORM\Column(name: "timestamp", type: "integer")]
    private int $timestamp = 0;

    #[ORM\Column(name: "created_at", type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->runId = class_exists(\Symfony\Component\Uid\Uuid::class)
            ? \Symfony\Component\Uid\Uuid::v4()->toRfc4122()
            : bin2hex(random_bytes(18));
        $this->timestamp = time();
        $this->version = '1.5.0';
    }

    // --- GETTERS ---
    public function getId(): ?int { return $this->id; }
    public function getVersion(): string { return $this->version; }
    public function getRunId(): string { return $this->runId; }
    public function getSymbol(): string { return $this->symbol; }
    public function getSource(): ?string { return $this->source; }
    public function getDirection(): string { return $this->direction; }
    public function getAlarmType(): ?string { return $this->alarmType; }
    public function getTimeframeUsed(): ?string { return $this->timeframeUsed; }
    public function getPriceNow(): string { return $this->priceNow; }
    public function getPrice1mAgo(): string { return $this->price1mAgo; }
    public function getPrice5mAgo(): string { return $this->price5mAgo; }
    public function getSpeed1m(): string { return $this->speed1m; }
    public function getSpeed5m(): string { return $this->speed5m; }
    public function getDeltaPct(): string { return $this->deltaPct; }
    public function getDeltaUsd(): string { return $this->deltaUsd; }
    public function getStrength(): int { return $this->strength; }
    public function getEntryConfidence(): ?string { return $this->entryConfidence; }
    public function getTimestamp(): int { return $this->timestamp; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // --- SETTERS ---
    public function setVersion(string $version): self { $this->version = $version; return $this; }
    public function setRunId(string $runId): self { $this->runId = $runId; return $this; }
    public function setSymbol(string $symbol): self { $this->symbol = $symbol; return $this; }
    public function setSource(?string $source): self { $this->source = $source; return $this; }
    public function setDirection(string $direction): self { $this->direction = $direction; return $this; }
    public function setAlarmType(?string $type): self { $this->alarmType = $type; return $this; }
    public function setTimeframeUsed(?string $timeframe): self { $this->timeframeUsed = $timeframe; return $this; }
    public function setPriceNow(string $price): self { $this->priceNow = $price; return $this; }
    public function setPrice1mAgo(string $price): self { $this->price1mAgo = $price; return $this; }
    public function setPrice5mAgo(string $price): self { $this->price5mAgo = $price; return $this; }
    public function setSpeed1m(string $speed): self { $this->speed1m = $speed; return $this; }
    public function setSpeed5m(string $speed): self { $this->speed5m = $speed; return $this; }
    public function setDeltaPct(string $pct): self { $this->deltaPct = $pct; return $this; }
    public function setDeltaUsd(string $delta): self { $this->deltaUsd = $delta; return $this; }
    public function setStrength(int $strength): self { $this->strength = max(0, min(100, $strength)); return $this; }
    public function setEntryConfidence(?string $confidence): self { $this->entryConfidence = $confidence; return $this; }
    public function setTimestamp(int $timestamp): self { $this->timestamp = $timestamp; return $this; }
}
