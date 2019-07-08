<?php
declare(strict_types=1);

namespace DealerinspireLaravelPerformanceMonitor;

use Psr\Log\LoggerInterface;

class PerformanceMonitor
{
    /**
     * @var LoggerInterface
     */
    protected $log;

    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
    }

    /**
     * Execute our performance monitoring checks
     *
     * @return void
     */
    public function execute()
    {
        if (config('performancemonitor.enable_execution_time_check')) {
            $this->checkApplicationExecutionTime();
        }

        if (config('performancemonitor.enable_memory_limit_check')) {
            $this->checkMemoryThreshold();
        }
    }

    /**
     * Checks if the application execution time is greater than the threshold
     *
     * @return void
     */
    public function checkApplicationExecutionTime(): void
    {
        $executionTime = (microtime(true) - LARAVEL_START);
        $maxExecutionTime = config('performancemonitor.execution_time_max_seconds');
        if ($executionTime > $maxExecutionTime) {
            $this->log->error(sprintf('Long-running process detected. Script run time: %d seconds. Execution Warning Time Limit: %d seconds', $executionTime, $maxExecutionTime));
        }
    }

    /**
     * Checks if the application memory usage is greater than the threshold
     *
     * @return void
     */
    public function checkMemoryThreshold(): void
    {
        $memoryUsage = memory_get_peak_usage(true);
        $maxUsagePercent = config('performancemonitor.memory_limit_max_memory_percent');
        $memoryLimit = ini_get('memory_limit');

        if (empty($memoryLimit)) {
            return;
        }

        $memoryLimitInBytes = $this->getLimitAsBytes($memoryLimit);
        $actualUsagePercent = ($memoryUsage / $memoryLimitInBytes) * 100;

        if ($actualUsagePercent >= $maxUsagePercent) {
            $this->log->error(
                sprintf(
                    'Memory usage spike detected. Used memory: %d bytes. Memory Warning Limit: %d bytes (%d%% of available memory)',
                    $memoryUsage,
                    $memoryLimitInBytes * $maxUsagePercent / 100,
                    $actualUsagePercent
                )
            );
        }
    }

    /**
     * Convert a given *byte string to bytes (ex) $limit of "1K" returns 1024)
     *
     * @param string $limit
     * @return int
     */
    protected function getLimitAsBytes(string $limit): int
    {
        $limitInt = (int)$limit;
        $unit = strtolower(str_replace($limitInt, '', $limit));

        switch ($unit) {
            case 'g':
                $limitInt *= (1024 * 1024 * 1024);
                break;
            case 'm':
                $limitInt *= (1024 * 1024);
                break;
            case 'k':
                $limitInt *= 1024;
                break;
        }

        return $limitInt;
    }
}
