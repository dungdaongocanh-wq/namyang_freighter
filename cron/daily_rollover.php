<?php
/**
 * Cron: chạy hàng ngày lúc 00:05
 * */php cron/daily_rollover.php
 */
define('CRON_RUN', true);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/DateRollover.php';

$result = DateRollover::run();
echo date('Y-m-d H:i:s') . " — Rollover: {$result['rolled_over']} lô → date={$result['date']}\n";