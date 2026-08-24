<?php
/**
 * PerformanceMonitor Class
 *
 * This class provides tools for measuring and analyzing the performance of PHP code,
 * particularly within the WordPress environment.
 *
 * Usage:
 * 1. Enable memory checking (optional):
 *    PerformanceMonitor::enableMemoryCheck(true);
 *
 * 2. Start a measurement:
 *    $result = PerformanceMonitor::start('operation_name', ['metadata' => 'value']);
 *    if ($result['ok']) {
 *        // Measurement started successfully
 *    } else {
 *        // Handle error: $result['reason']
 *    }
 *
 * 3. End a measurement:
 *    $result = PerformanceMonitor::end('operation_name', ['additional_metadata' => 'value']);
 *    if ($result['ok']) {
 *        // Access measurement data: $result['data']
 *    } else {
 *        // Handle error: $result['reason']
 *    }
 *
 * 4. Get overall summary:
 *    $summary = PerformanceMonitor::getReport();
 *
 * 5. Reset all measurements:
 *    PerformanceMonitor::reset();
 *
 * 6. Get currently running measurements:
 *    $running = PerformanceMonitor::getRunningMeasurements();
 *
 * Features:
 * - Tracks execution time for operations
 * - Optionally tracks memory usage
 * - Supports nested measurements with label hierarchy
 * - Provides detailed summaries grouped by prefix and level
 * - Allows adding metadata to measurements
 * - Includes internal performance tracking of the PerformanceMonitor class itself
 *
 * @package Gutenbricks
 * @subpackage Performance
 */

namespace Gutenbricks;

class PerformanceMonitor
{
  private static $measurements = [];
  private static $totalMeasurements = [];
  private static $check_memory = false;
  private static $internalMeasurement = [
    'count' => 0,
    'total_time_ms' => 0,
    'total_memory_bytes' => 0,
  ];

  private static function internalStart()
  {
    $start = [
      'start_time' => microtime(true),
    ];
    if (self::$check_memory) {
      $start['start_memory'] = memory_get_usage(true);
    }
    return $start;
  }
  
  private static function internalEnd($start)
  {
    $end_time = microtime(true);
    $execution_time = ($end_time - $start['start_time']) * 1000; // Convert to milliseconds
  
    self::$internalMeasurement['count']++;
    self::$internalMeasurement['total_time_ms'] += $execution_time;
  
    if (self::$check_memory) {
      $end_memory = memory_get_usage(true);
      $memory_used = $end_memory - $start['start_memory'];
      self::$internalMeasurement['total_memory_bytes'] += $memory_used;
    }
  }

  public static function enableMemoryCheck($enable = true)
  {
    self::$check_memory = $enable;
  }

  public static function start($label, array $metadata = [])
  {

    if (!gutenbricks_is_dev_env()) {
      return;
    }

    $internal_start = self::internalStart();

    if (isset(self::$measurements[$label])) {
      self::internalEnd($internal_start);
      return [
        'ok' => false,
        'reason' => "Measurement '$label' is already running."
      ];
    }

    $measurement = [
      'label' => $label,
      'start_time' => microtime(true),
      'start_memory' => self::$check_memory ? memory_get_usage(true) : 0,
      'metadata' => $metadata,
    ];

    self::$measurements[$label] = $measurement;

    self::internalEnd($internal_start);
    return [
      'ok' => true,
      'data' => $measurement
    ];
  }

  public static function end($label, array $additionalMetadata = [])
  {
    if (!gutenbricks_is_dev_env()) {
      return;
    }

    $internal_start = self::internalStart();

    if (!isset(self::$measurements[$label])) {
      self::internalEnd($internal_start);
      return [
        'ok' => false,
        'reason' => "No measurement found for label '$label'. Make sure to call start() before end()."
      ];
    }

    $end_time = microtime(true);
    $end_memory = self::$check_memory ? memory_get_usage(true) : 0;

    $start = self::$measurements[$label];
    $execution_time = ($end_time - $start['start_time']) * 1000; // Convert to milliseconds
    $memory_used = self::$check_memory ? $end_memory - $start['start_memory'] : 0;

    $result = [
      'label' => $label,
      'execution_time_ms' => $execution_time,
      'metadata' => array_merge($start['metadata'], $additionalMetadata),
    ];

    if (self::$check_memory) {
      $result['memory_used_bytes'] = $memory_used;
      $result['memory_used_kb'] = round($memory_used / 1024, 2);
    }

    unset(self::$measurements[$label]);

    // Update total measurements
    if (!isset(self::$totalMeasurements[$label])) {
      self::$totalMeasurements[$label] = [
        'count' => 0,
        'total_time_ms' => 0,
        'total_memory_bytes' => 0,
        'last_metadata' => $result['metadata'],
      ];
    }
    self::$totalMeasurements[$label]['count']++;
    self::$totalMeasurements[$label]['total_time_ms'] += $execution_time;
    if (self::$check_memory) {
      self::$totalMeasurements[$label]['total_memory_bytes'] += $memory_used;
    }

    // Record the last metadata
    self::$totalMeasurements[$label]['last_metadata'] = $result['metadata'];

    // Calculate and add total measurement to result
    $total = self::$totalMeasurements[$label];
    $result['total'] = [
      'count' => $total['count'],
      'total_time_ms' => $total['total_time_ms'],
      'average_time_ms' => $total['total_time_ms'] / $total['count'],
    ];
    if (self::$check_memory) {
      $result['total']['total_memory_bytes'] = $total['total_memory_bytes'];
      $result['total']['total_memory_kb'] = round($total['total_memory_bytes'] / 1024, 2);
      $result['total']['average_memory_bytes'] = $total['total_memory_bytes'] / $total['count'];
      $result['total']['average_memory_kb'] = round($result['total']['average_memory_bytes'] / 1024, 2);
    }

    self::internalEnd($internal_start);
    return [
      'ok' => true,
      'data' => $result
    ];
  }

  public static function getReport()
  {
    $internal_start = self::internalStart();

    $report = [];

    foreach (self::$totalMeasurements as $label => $data) {
      $parts = explode('/', $label);
      $current = &$report;
      
      foreach ($parts as $index => $part) {
        if (!isset($current[$part])) {
          $current[$part] = [
            'count' => 0,
            'total_time_ms' => 0,
            'total_memory_bytes' => 0,
            'children' => [],
          ];
        }
        
        $current[$part]['count'] += $data['count'];
        $current[$part]['total_time_ms'] += $data['total_time_ms'];
        $current[$part]['total_memory_bytes'] += $data['total_memory_bytes'];
        
        if ($index === count($parts) - 1) {
          $current[$part]['average_time_ms'] = $data['total_time_ms'] / $data['count'];
          if (self::$check_memory) {
            $current[$part]['average_memory_bytes'] = $data['total_memory_bytes'] / $data['count'];
            $current[$part]['total_memory_kb'] = round($data['total_memory_bytes'] / 1024, 2);
            $current[$part]['average_memory_kb'] = round($current[$part]['average_memory_bytes'] / 1024, 2);
          }
          $current[$part]['last_metadata'] = $data['last_metadata'];
        } else {
          $current = &$current[$part]['children'];
        }
      }
    }

    // Add internal measurement
    $report['__internal__'] = [
      'count' => self::$internalMeasurement['count'],
      'total_time_ms' => self::$internalMeasurement['total_time_ms'],
      'average_time_ms' => self::$internalMeasurement['count'] > 0 
        ? self::$internalMeasurement['total_time_ms'] / self::$internalMeasurement['count'] 
        : 0,
      'total_memory_bytes' => self::$internalMeasurement['total_memory_bytes'],
      'average_memory_bytes' => self::$internalMeasurement['count'] > 0 
        ? self::$internalMeasurement['total_memory_bytes'] / self::$internalMeasurement['count'] 
        : 0,
    ];

    if (self::$check_memory) {
      $report['__internal__']['total_memory_kb'] = round(self::$internalMeasurement['total_memory_bytes'] / 1024, 2);
      $report['__internal__']['average_memory_kb'] = round($report['__internal__']['average_memory_bytes'] / 1024, 2);
    }

    self::internalEnd($internal_start);
    return $report;
  }

  public static function reset()
  {
    $internal_start = self::internalStart();
    
    self::$measurements = [];
    self::$totalMeasurements = [];
    
    self::internalEnd($internal_start);
  }


  public static function getTotalMeasurements()
{
  $internal_start = self::internalStart();

  $result = self::$totalMeasurements;

  // Add internal measurements
  $result['__internal__'] = [
    'count' => self::$internalMeasurement['count'],
    'total_time_ms' => self::$internalMeasurement['total_time_ms'],
    'average_time_ms' => self::$internalMeasurement['count'] > 0 
      ? self::$internalMeasurement['total_time_ms'] / self::$internalMeasurement['count'] 
      : 0,
  ];

  if (self::$check_memory) {
    $result['__internal__']['total_memory_bytes'] = self::$internalMeasurement['total_memory_bytes'];
    $result['__internal__']['average_memory_bytes'] = self::$internalMeasurement['count'] > 0 
      ? self::$internalMeasurement['total_memory_bytes'] / self::$internalMeasurement['count'] 
      : 0;
    $result['__internal__']['total_memory_kb'] = round(self::$internalMeasurement['total_memory_bytes'] / 1024, 2);
    $result['__internal__']['average_memory_kb'] = round($result['__internal__']['average_memory_bytes'] / 1024, 2);
  }

  self::internalEnd($internal_start);
  return $result;
}

  public static function getRunningMeasurements()
  {
    $internal_start = self::internalStart();
    $result = array_keys(self::$measurements);
    self::internalEnd($internal_start);
    return $result;
  }
}