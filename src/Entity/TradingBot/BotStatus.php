<?php

namespace App\Entity\TradingBot;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TradingBot\BotStatusRepository::class)]
#[ORM\Table(
    name: "bot_status",
    indexes: [
        new ORM\Index(name: "idx_status_created_at", columns: ["created_at"]),
        new ORM\Index(name: "idx_status_run_id", columns: ["run_id"])
    ]
)]
class BotStatus
{
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_WARNING = 'WARNING';
    public const STATUS_ERROR = 'ERROR';
    public const STATUS_OFFLINE = 'OFFLINE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "version", type: "string", length: 20)]
    private string $version;

    #[ORM\Column(name: "run_id", type: "string", length: 64, nullable: true)]
    private ?string $runId = null;

    #[ORM\Column(name: "status", type: "string", length: 30)]
    private string $status = self::STATUS_RUNNING;

    #[ORM\Column(name: "last_price", type: "decimal", precision: 20, scale: 8, nullable: true)]
    private ?string $lastPrice = null;

    #[ORM\Column(name: "execution_time_ms", type: "integer", nullable: true)]
    private ?int $executionTimeMs = null;

    #[ORM\Column(name: "cron_exit_code", type: "integer", nullable: true)]
    private ?int $cronExitCode = null;

    #[ORM\Column(name: "processed_candles", type: "integer", nullable: true)]
    private ?int $processedCandles = null;

    #[ORM\Column(name: "memory_usage_mb", type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?string $memoryUsageMb = null;

    #[ORM\Column(name: "peak_memory_mb", type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?string $peakMemoryMb = null;

    #[ORM\Column(name: "error_message", type: "text", nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: "timestamp", type: "integer")]
    private int $timestamp = 0;

    #[ORM\Column(name: "created_at", type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->timestamp = time();
        $this->version = '1.5.0';

        $this->runId = class_exists(\Symfony\Component\Uid\Uuid::class)
            ? \Symfony\Component\Uid\Uuid::v4()->toRfc4122()
            : bin2hex(random_bytes(18));
    }

    // --- GETTERS ---
    public function getId(): ?int { return $this->id; }
    public function getVersion(): string { return $this->version; }
    public function getRunId(): ?string { return $this->runId; }
    public function getStatus(): string { return $this->status; }
    public function getLastPrice(): ?string { return $this->lastPrice; }
    public function getExecutionTimeMs(): ?int { return $this->executionTimeMs; }
    public function getCronExitCode(): ?int { return $this->cronExitCode; }
    public function getProcessedCandles(): ?int { return $this->processedCandles; }
    public function getMemoryUsageMb(): ?string { return $this->memoryUsageMb; }
    public function getPeakMemoryMb(): ?string { return $this->peakMemoryMb; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function getTimestamp(): int { return $this->timestamp; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // --- SETTERS ---
    public function setVersion(string $version): self { $this->version = $version; return $this; }
    public function setRunId(?string $runId): self { $this->runId = $runId; return $this; }
    public function setStatus(string $status): self
    {
        $allowed = [
            self::STATUS_RUNNING,
            self::STATUS_SUCCESS,
            self::STATUS_WARNING,
            self::STATUS_ERROR,
            self::STATUS_OFFLINE
        ];
        if (in_array($status, $allowed, true)) {
            $this->status = $status;
        }
        return $this;
    }
    public function setLastPrice(?string $price): self { $this->lastPrice = $price; return $this; }
    public function setExecutionTimeMs(?int $time): self { $this->executionTimeMs = $time; return $this; }
    public function setCronExitCode(?int $code): self { $this->cronExitCode = $code; return $this; }
    public function setProcessedCandles(?int $count): self { $this->processedCandles = $count; return $this; }
    public function setMemoryUsageMb(?string $mb): self { $this->memoryUsageMb = $mb; return $this; }
    public function setPeakMemoryMb(?string $mb): self { $this->peakMemoryMb = $mb; return $this; }
    public function setErrorMessage(?string $message): self { $this->errorMessage = $message; return $this; }
    public function setTimestamp(int $timestamp): self { $this->timestamp = $timestamp; return $this; }
}
