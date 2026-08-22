<?php

namespace App\Controller\TradingBot;

use App\Entity\TradingBot\Alarm;
use App\Entity\TradingBot\BotStatus;
use App\Repository\TradingBot\AlarmRepository;
use App\Repository\TradingBot\BotStatusRepository;
use App\Repository\TradingBot\MarketSnapshotRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DashboardController extends AbstractController
{
    private string $symbol;
    private int $healthTimeout;
    private string $telegramChannelLink;

    // Constructor injects parameters from services.yaml.
    public function __construct(ParameterBagInterface $params)
    {
        $this->symbol = (string) $params->get('trading.symbol');
        $this->healthTimeout = (int) $params->get('trading.health_timeout');
        $this->telegramChannelLink = (string) $params->get('telegram.channel_link');
    }

    // Main dashboard page.
    #[Route('/trading/alarm/dashboard', name: 'trading_dashboard')]
    public function index(
        MarketSnapshotRepository $snapshotRepo,
        AlarmRepository $alarmRepo,
        BotStatusRepository $statusRepo
    ): Response {

        // 1. Get the latest two snapshots to compute price delta and speed.
        $snapshots = $snapshotRepo->findLatestTwo($this->symbol);

        $executionMetrics = null;
        $deltaSeconds = null;

        if (count($snapshots) >= 2) {
            $current = (float) $snapshots[0]->getPrice();
            $previous = (float) $snapshots[1]->getPrice();
            $delta = $current - $previous;
            $deltaPct = ($previous != 0) ? ($delta / $previous) * 100 : 0;
            $secondsDiff = $snapshots[0]->getTimestamp() - $snapshots[1]->getTimestamp();
            $speed = ($secondsDiff > 0) ? $delta / $secondsDiff : 0;
            $deltaSeconds = $secondsDiff;

            $executionMetrics = [
                'price' => $current,
                'delta' => $delta,
                'delta_pct' => $deltaPct,
                'speed' => $speed,
                'seconds' => $secondsDiff,
            ];
        }

        // 2. Get the latest alarm (if any) and check its validity.
        $latestAlarm = $alarmRepo->findLatest($this->symbol);

        $signalMetrics = null;
        if ($latestAlarm) {
            //$minutesSinceAlarm = (time() - $latestAlarm->getCreatedAt()->getTimestamp()) / 60;
            $minutesSinceAlarm = max(
                0,
               (time() - $latestAlarm->getCreatedAt()->getTimestamp()) / 60
            );

            $strength = $latestAlarm->getStrength();
            // Validity depends on strength: stronger signals last longer.
            if ($strength >= 80) {
                $validityMinutes = 20;
            } elseif ($strength >= 60) {
                $validityMinutes = 15;
            } elseif ($strength >= 40) {
                $validityMinutes = 10;
            } else {
                $validityMinutes = 2;
            }

            if ($minutesSinceAlarm >= 0 && $minutesSinceAlarm <= $validityMinutes) {
                $signalMetrics = [
                    'symbol' => $latestAlarm->getSymbol(),
                    'direction' => $latestAlarm->getDirection(),
                    'alarm_type' => $latestAlarm->getAlarmType(),
                    'strength' => $latestAlarm->getStrength(),
                    'entry_confidence' => $latestAlarm->getEntryConfidence(),
                    'created_at' => $latestAlarm->getCreatedAt(),
                    'validity_minutes' => $validityMinutes,
                ];
            }
        }

        // 3. Get the latest bot status (health).
        $latestStatus = $statusRepo->findLatest();

        // 4. Get the latest 50 alarms for the history table.
        $alarms = $alarmRepo->findLatestHistory($this->symbol);

        // 5. Compute market data age for heartbeat.
        $latestSnapshot = $snapshots[0] ?? null;
        $marketDataAge = $latestSnapshot ? time() - $latestSnapshot->getTimestamp() : null;

        // 6. Human‑readable label for the time between snapshots.
        $lastRunLabel = '--';
        if ($deltaSeconds !== null && $deltaSeconds > 0) {
            if ($deltaSeconds < 60) {
                $lastRunLabel = sprintf('%ds', $deltaSeconds);
            } elseif ($deltaSeconds < 3600) {
                $lastRunLabel = sprintf('%dmin', round($deltaSeconds / 60));
            } else {
                $lastRunLabel = sprintf('%.1fh', $deltaSeconds / 3600);
            }
        }

        // Render the Twig template with all data.
        return $this->render('trading_bot/dashboard.html.twig', [
            'symbol' => $this->symbol,
            'latest_snapshot' => $latestSnapshot,
            'execution_metrics' => $executionMetrics,
            'signal_metrics' => $signalMetrics,
            'latest_status' => $latestStatus,
            'alarms' => $alarms,
            'last_run_label' => $lastRunLabel,
            'market_data_age' => $marketDataAge,
            'telegram_channel_link' => $this->telegramChannelLink,
        ]);
    }

    // Heartbeat endpoint – returns simple health status.
    #[Route('/trading/heartbeat', name: 'trading_heartbeat')]
    public function heartbeat(
        MarketSnapshotRepository $snapshotRepo,
        BotStatusRepository $statusRepo
    ): JsonResponse {
        $latestSnapshot = $snapshotRepo->findLatest($this->symbol);
        $latestStatus = $statusRepo->findLatest();

        $dataAge = null;
        $isHealthy = false;
        if ($latestSnapshot) {
            $dataAge = time() - $latestSnapshot->getTimestamp();
            $isHealthy = $dataAge <= $this->healthTimeout;
        }

        return $this->json([
            'status' => $latestStatus ? $latestStatus->getStatus() : BotStatus::STATUS_OFFLINE,
            'last_price' => $latestSnapshot ? $latestSnapshot->getPrice() : null,
            'data_age_seconds' => $dataAge,
            'is_healthy' => $isHealthy,
            'last_execution' => $latestStatus ? $latestStatus->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'execution_time_ms' => $latestStatus ? $latestStatus->getExecutionTimeMs() : null,
            'processed_candles' => $latestStatus ? $latestStatus->getProcessedCandles() : null,
            'memory_usage_mb' => $latestStatus ? $latestStatus->getMemoryUsageMb() : null,
            'error' => $latestStatus ? $latestStatus->getErrorMessage() : null,
        ]);
    }

    // AJAX endpoint to refresh the alarm history table.
    #[Route('/trading/alarms/latest', name: 'trading_alarms_latest')]
    public function latestAlarms(
        AlarmRepository $alarmRepo
    ): JsonResponse {
        $alarms = $alarmRepo->findLatestHistory($this->symbol);

        $data = array_map(function (Alarm $alarm) {
            return [
                'id' => $alarm->getId(),
                'symbol' => $alarm->getSymbol(),
                'direction' => $alarm->getDirection(),
                'alarm_type' => $alarm->getAlarmType(),
                'timeframe_used' => $alarm->getTimeframeUsed(),
                'price_now' => $alarm->getPriceNow(),
                'price_5m_ago' => $alarm->getPrice5mAgo(),
                'speed_5m' => $alarm->getSpeed5m(),
                'strength' => $alarm->getStrength(),
                'entry_confidence' => $alarm->getEntryConfidence(),
                'delta_pct' => $alarm->getDeltaPct(),
                'delta_usd' => $alarm->getDeltaUsd(),
                'created_at' => $alarm->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $alarms);

        return $this->json($data);
    }

    // Detailed status endpoint (used for the admin status card).
    #[Route('/trading/status', name: 'trading_status')]
    public function status(
        AlarmRepository $alarmRepo,
        BotStatusRepository $statusRepo
    ): JsonResponse {
        $status = $statusRepo->findLatest();

        if (!$status) {
            return $this->json(['status' => BotStatus::STATUS_OFFLINE, 'message' => 'No status records found.']);
        }

        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        $alarmCount = $alarmRepo->countToday($this->symbol, $today);

        return $this->json([
            'status' => $status->getStatus(),
            'execution_time_ms' => $status->getExecutionTimeMs(),
            'last_price' => $status->getLastPrice(),
            'error_message' => $status->getErrorMessage(),
            'memory_usage_mb' => $status->getMemoryUsageMb(),
            'alarm_count_today' => $alarmCount,
            'created_at' => $status->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
}
