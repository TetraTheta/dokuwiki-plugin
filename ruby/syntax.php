<?php

if (!defined('DOKU_INC')) {
  die();
}

class syntax_plugin_ruby extends DokuWiki_Syntax_Plugin {
  public function connectTo($mode) {
    $this->Lexer->addSpecialPattern('\{\{ruby>[^}|]*\|[^}]*\}\}', $mode, 'plugin_ruby');
  }

  public function getPType() {
    return 'normal';
  }

  public function getSort() {
    return 150;
  }

  public function getType() {
    return 'substition';
  }

  public function handle($match, $state, $pos, Doku_Handler $handler) {
    $inner = substr($match, 7, -2);
    $parts = explode('|', $inner, 2);

    return [trim($parts[0]), isset($parts[1]) ? trim($parts[1]) : ''];
  }

  public function render($format, Doku_Renderer $renderer, $data) {
    if ('xhtml' !== $format) {
      return false;
    }

    [$bottom, $top] = $data;

    $renderer->doc .= '<ruby>' . hsc($bottom) . '<rp>(</rp><rt>' . hsc($top) . '</rt><rp>)</rp></ruby>';

    return true;
  }
}
