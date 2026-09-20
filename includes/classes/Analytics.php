<?php
/**
 * Analytics — dashboard queries: best/slow sellers, revenue trends,
 * category performance. Backs admin/analytics.php and admin/reports.php.
 *
 * NEXT PHASE:
 *   public static function topSellingProducts(string $from, string $to, int $limit = 10): array
 *   public static function slowMovingProducts(int $daysWithNoSale = 30): array
 *   public static function revenueByPeriod(string $granularity, string $from, string $to): array   // 'daily'|'weekly'|'monthly'
 *   public static function categoryPerformance(string $from, string $to): array
 */
class Analytics {}
