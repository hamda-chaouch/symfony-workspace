<?php

namespace App\Entity\TradingBot;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TradingBot\MarketSnapshotRepository::class)]
#[ORM\Table(
    name: "market_snapshot",
    indexes: [
        new ORM\Index(name: "idx_market_symbol_timestamp", columns: ["symbol", "timestamp"]),
        new ORM\Index(name: "idx_market_created_at", columns: ["created_at"]),
        new ORM\Index(name: "idx_market_run_id", columns: ["run_id"])
    ]
)]
class MarketSnapshot
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

    #[ORM\Column(name: "timeframe", type: "string", length: 10, options: ["default" => "1m"])]
    private string $timeframe = '1m';

    #[ORM\Column(name: "open", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $open = null;

    #[ORM\Column(name: "high", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $high = null;

    #[ORM\Column(name: "low", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $low = null;

    #[ORM\Column(name: "price", type: "decimal", precision: 20, scale: 8)]
    private string $price = '0.00000000';

    #[ORM\Column(name: "close", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $close = null;

    #[ORM\Column(name: "volume", type: "decimal", precision: 20, scale: 8)]
    private string $volume = '0.00000000';

    #[ORM\Column(name: "volume_avg20", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $volumeAvg20 = null;

    #[ORM\Column(name: "volume_ratio", type: "decimal", precision: 10, scale: 4, nullable: true)]
    private ?string $volumeRatio = null;

    #[ORM\Column(name: "ema7", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $ema7 = null;

    #[ORM\Column(name: "ema25", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $ema25 = null;

    #[ORM\Column(name: "ema90", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $ema90 = null;

    #[ORM\Column(name: "rsi14", type: "decimal", precision: 10, scale: 4, nullable: true)]
    private ?string $rsi14 = null;

    #[ORM\Column(name: "atr14", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $atr14 = null;

    #[ORM\Column(name: "bid", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $bid = null;

    #[ORM\Column(name: "ask", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $ask = null;

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
        // Version is set via setter from service parameter
        $this->version = '1.5.0';
    }

    // --- GETTERS ---
    public function getId(): ?int { return $this->id; }
    public function getVersion(): string { return $this->version; }
    public function getRunId(): string { return $this->runId; }
    public function getSymbol(): string { return $this->symbol; }
    public function getTimeframe(): string { return $this->timeframe; }
    public function getOpen(): ?string { return $this->open; }
    public function getHigh(): ?string { return $this->high; }
    public function getLow(): ?string { return $this->low; }
    public function getPrice(): string { return $this->price; }
    public function getClose(): ?string { return $this->close; }
    public function getVolume(): string { return $this->volume; }
    public function getVolumeAvg20(): ?string { return $this->volumeAvg20; }
    public function getVolumeRatio(): ?string { return $this->volumeRatio; }
    public function getEma7(): ?string { return $this->ema7; }
    public function getEma25(): ?string { return $this->ema25; }
    public function getEma90(): ?string { return $this->ema90; }
    public function getRsi14(): ?string { return $this->rsi14; }
    public function getAtr14(): ?string { return $this->atr14; }
    public function getBid(): ?string { return $this->bid; }
    public function getAsk(): ?string { return $this->ask; }
    public function getTimestamp(): int { return $this->timestamp; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // --- SETTERS ---
    public function setVersion(string $version): self { $this->version = $version; return $this; }
    public function setRunId(string $runId): self { $this->runId = $runId; return $this; }
    public function setSymbol(string $symbol): self { $this->symbol = $symbol; return $this; }
    public function setTimeframe(string $timeframe): self { $this->timeframe = $timeframe; return $this; }
    public function setOpen(?string $value): self { $this->open = $value; return $this; }
    public function setHigh(?string $value): self { $this->high = $value; return $this; }
    public function setLow(?string $value): self { $this->low = $value; return $this; }
    public function setPrice(string $price): self { $this->price = $price; return $this; }
    public function setClose(?string $value): self { $this->close = $value; return $this; }
    public function setVolume(string $volume): self { $this->volume = $volume; return $this; }
    public function setVolumeAvg20(?string $value): self { $this->volumeAvg20 = $value; return $this; }
    public function setVolumeRatio(?string $value): self { $this->volumeRatio = $value; return $this; }
    public function setEma7(?string $value): self { $this->ema7 = $value; return $this; }
    public function setEma25(?string $value): self { $this->ema25 = $value; return $this; }
    public function setEma90(?string $value): self { $this->ema90 = $value; return $this; }
    public function setRsi14(?string $value): self { $this->rsi14 = $value; return $this; }
    public function setAtr14(?string $value): self { $this->atr14 = $value; return $this; }
    public function setBid(?string $value): self { $this->bid = $value; return $this; }
    public function setAsk(?string $value): self { $this->ask = $value; return $this; }
    public function setTimestamp(int $timestamp): self { $this->timestamp = $timestamp; return $this; }
}
