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
    // Strip wrapper, extract date string
    $dateStr = substr($match, 12, -2);

    return [$dateStr];
  }

  public function render($format, Doku_Renderer $renderer, $data) {
    if ('xhtml' != $format) {
      return false;
    }
    [$dateStr] = $data;
    $targetDate = date_create($dateStr);
    $today = date_create('today');

    if (!$targetDate) {
      $renderer->doc .= 'Invalid Date';
      return true;
    }

    $diff = date_diff($targetDate, $today);
    $totalMonths = $diff->y * 12 + $diff->m;
    if ($diff->d >= 15) {
      ++$totalMonths;
    }

    $years = floor($totalMonths / 12);
    $months = $totalMonths % 12;
    $output = '';
    if ($years > 0) {
      $output .= $years . '년 ';
    }
    $output .= $months . '개월';
    $renderer->doc .= htmlspecialchars($output);
    return true;
  }
}
