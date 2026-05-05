<?php

if (!defined('DOKU_INC')) {
  die();
}

class syntax_plugin_yearmonth extends DokuWiki_Syntax_Plugin {
  public function connectTo($mode) {
    $this->Lexer->addSpecialPattern('\{\{yearmonth>[^}]*\}\}', $mode, 'plugin_yearmonth');
  }

  public function getPType() {
    return 'normal';
  }

  public function getSort() {
    return 151;
  }

  public function getType() {
    return 'substition';
  }

  public function handle($match, $state, $pos, Doku_Handler $handler) {
    // Remove '{{yearmonth>...}}'
    $inner = substr($match, 12, -2);

    // Parse date|option
    $parts = explode('|', $inner, 2);
    $dateStr = trim($parts[0]);
    $mode = isset($parts[1]) ? strtolower(trim($parts[1])) : 'months';

    return [$dateStr, $mode];
  }

  public function render($format, Doku_Renderer $renderer, $data) {
    if ('xhtml' != $format) {
      return false;
    }

    [$dateStr, $mode] = $data;

    $targetDate = date_create($dateStr);
    $today = date_create('today');

    if (!$targetDate) {
      $renderer->doc .= 'Invalid Date';
      return true;
    }

    $diff = date_diff($targetDate, $today);

    $y = $diff->y;
    $m = $diff->m;
    $d = $diff->d;

    // Calculate total months
    $totalMonths = $y * 12 + $m;
    if ($d >= 15) {
      ++$totalMonths;
    }

    $years = intdiv($totalMonths, 12);
    $months = $totalMonths % 12;
    $days = $d;

    // Mode alias
    $modeMap = [
      'month' => 'months',
      'months' => 'months',
      'day' => 'days',
      'date' => 'days',
      'dates' => 'days',
      'days' => 'days',
      'year' => 'years',
      'years' => 'years',
    ];
    $mode = $modeMap[$mode] ?? 'months';

    // Mode cascading
    if ($mode === 'years') {
      if ($years === 0) {
        $mode = 'months';
      }
      if ($years === 0 && $months === 0) {
        $mode = 'days';
      }
    }
    if ($mode === 'months') {
      if ($years === 0 && $months === 0) {
        $mode = 'days';
      }
    }

    // Generate output
    $parts = [];

    if ($mode === 'years') {
      $parts[] = $years . '년';
    }

    if ($mode === 'months') {
      if ($years > 0) {
        $parts[] = $years . '년';
      }
      if ($months > 0) {
        $parts[] = $months . '개월';
      }
    }

    if ($mode === 'days') {
      if ($years > 0) {
        $parts[] = $years . '년';
      }
      if ($months > 0) {
        $parts[] = $months . '개월';
      }
      $parts[] = $days . '일';
    }

    $output = implode(' ', $parts);

    if ($mode === 'years' || $mode === 'months') {
      $output = '약 ' . $output;
    }

    $renderer->doc .= htmlspecialchars($output);
    return true;
  }
}
