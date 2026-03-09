<?php
/**
 * Better Infobox Plugin for DokuWiki
 *
 * Syntax:
 *   <infobox [css-class]>
 *   type | key text = value text
 *   ...
 *   </infobox>
 *
 * Types:
 *   title    | Title Text
 *   banner   | namespace:image.jpg
 *   image    | namespace:image.jpg [= Caption]
 *   tab      | namespace:image.jpg = Tab Label
 *   section  | Section Name
 *   collapse | Section Name
 *   text     | Label = Value
 *   wide     | Full-width content
 *   divider  [| Divider text]
 *
 * Spoiler: prefix key or value with ! to make it a spoiler
 *   text | !Hidden Label = !Hidden Value
 *
 * DokuWiki syntax and plugin syntax are fully supported inside key text and value text.
 */

if (!defined('DOKU_INC')) {
  die();
}

class syntax_plugin_betterinfobox extends DokuWiki_Syntax_Plugin {
  public function connectTo($mode) {
    $this->Lexer->addEntryPattern('<infobox\b[^>]*>', $mode, 'plugin_betterinfobox');
  }

  public function getPType() {
    return 'block';
  }

  public function getSort() {
    return 195;
  }

  public function getType() {
    return 'protected';
  }

  public function handle($match, $state, $pos, Doku_Handler $handler) {
    switch ($state) {
      case DOKU_LEXER_ENTER:
        // Extract optional CSS class from <infobox classname>
        $inner = trim(substr($match, 9, -1)); // strip "<infobox" and ">"

        return [$state, ['class' => $inner]];

      case DOKU_LEXER_UNMATCHED:
        return [$state, $this->_parseLines($match)];

      case DOKU_LEXER_EXIT:
        return [$state, null];
    }

    return false;
  }

  public function postConnect() {
    $this->Lexer->addExitPattern('</infobox>', 'plugin_betterinfobox');
  }

  public function render($mode, Doku_Renderer $renderer, $data) {
    if ('xhtml' !== $mode) {
      return false;
    }

    [$state, $payload] = $data;

    switch ($state) {
      case DOKU_LEXER_ENTER:
        $cls = '';

        if (!empty($payload['class'])) {
          $cls = ' ' . hsc($payload['class']);
        }

        $renderer->doc .= '<div class="bib-infobox' . $cls . '">' . DOKU_LF;
        break;

      case DOKU_LEXER_UNMATCHED:
        $this->_renderItems($renderer, $payload);
        break;

      case DOKU_LEXER_EXIT:
        $renderer->doc .= '</div>' . DOKU_LF;
        break;
    }

    return true;
  }

  /**
   * Resolve a DokuWiki media ID to its fetch URL.
   */
  private function _mediaUrl($id) {
    $id = trim($id);

    if ('' === $id) {
      return '';
    }

    // resolve relative media IDs
    resolve_mediaid(getNS(getID()), $id, $exists);

    return ml($id);
  }

  /**
   * Parse the raw content between <infobox> and </infobox>.
   * Each non-empty line is parsed as: type [| key] [ = value]
   * Line starts with '#' will be ignored.
   */
  private function _parseLines($raw) {
    $lines = explode("\n", $raw);
    $items = [];

    foreach ($lines as $line) {
      $line = trim($line);

      if ('' === $line || str_starts_with($line, '#')) {
        continue;
      }

      $type = '';
      $key = '';
      $value = '';

      // Split on first "|" to separate type from the rest
      $pipePos = strpos($line, '|');

      if (false !== $pipePos) {
        $type = strtolower(trim(substr($line, 0, $pipePos)));
        $rest = substr($line, $pipePos + 1);

        // Split the rest on first " = " to separate key from value
        $eqPos = strpos($rest, ' = ');

        if (false !== $eqPos) {
          $key = trim(substr($rest, 0, $eqPos));
          $value = trim(substr($rest, $eqPos + 3));
        } else {
          $key = trim($rest);
        }
      } else {
        // No pipe: could be "type = value" or just "type"
        $eqPos = strpos($line, ' = ');

        if (false !== $eqPos) {
          $type = strtolower(trim(substr($line, 0, $eqPos)));
          $value = trim(substr($line, $eqPos + 3));
        } else {
          $type = strtolower(trim($line));
        }
      }

      // Detect and strip spoiler prefix "!" from key and value
      $keySpoiler = false;
      $valueSpoiler = false;

      if (strlen($key) > 0 && '!' === $key[0]) {
        $keySpoiler = true;
        $key = substr($key, 1);
      }

      if (strlen($value) > 0 && '!' === $value[0]) {
        $valueSpoiler = true;
        $value = substr($value, 1);
      }

      $items[] = [
        'type' => $type,
        'key' => $key,
        'value' => $value,
        'keySpoiler' => $keySpoiler,
        'valueSpoiler' => $valueSpoiler,
      ];
    }

    return $items;
  }

  /**
   * Render all parsed items, grouping consecutive "tab" items together.
   * Sections and collapsible sections wrap subsequent rows in a container.
   */
  private function _renderItems($renderer, $items) {
    $sectionOpen = false; // whether a section/collapse body <div> is open
    $tabGroup = [];
    $tabCounter = 0; // unique counter for tab group IDs
    $collapseCounter = 0; // unique counter for collapse IDs

    $count = count($items);

    for ($i = 0; $i < $count; ++$i) {
      $item = $items[$i];

      // -- Tab grouping: collect consecutive tab items -----------------------
      if ('tab' === $item['type']) {
        $tabGroup[] = $item;
        $isLast = !isset($items[$i + 1]) || 'tab' !== $items[$i + 1]['type'];
        if ($isLast) {
          ++$tabCounter;
          $this->_renderTabGroup($renderer, $tabGroup, $tabCounter);
          $tabGroup = [];
        }

        continue;
      }

      // -- Close open section body before a new section/collapse/divider -----
      if (in_array($item['type'], ['section', 'collapse']) && $sectionOpen) {
        $renderer->doc .= '</div>' . DOKU_LF; // close .bib-section-body
        $sectionOpen = false;
      }

      // -- Render by type ----------------------------------------------------
      switch ($item['type']) {
        case 'title':
          $renderer->doc .= '<div class="bib-title">';
          $renderer->doc .= $this->_wiki($item['key'], $item['keySpoiler']);
          $renderer->doc .= '</div>' . DOKU_LF;
          break;

        case 'banner':
          $src = $this->_mediaUrl($item['key']);
          $renderer->doc .= '<div class="bib-banner">';
          $renderer->doc .= '<img src="' . $src . '" alt="" loading="lazy" />';
          $renderer->doc .= '</div>' . DOKU_LF;
          break;

        case 'image':
          if (str_starts_with($item['key'], 'http://') || str_starts_with($item['key'], 'https://')) {
            $src = $item['key'];
          } else {
            $src = $this->_mediaUrl($item['key']);
          }
          $renderer->doc .= '<div class="bib-image">';
          $renderer->doc .= '<img src="' . $src . '" alt="" loading="lazy" />';
          if ('' !== $item['value']) {
            $renderer->doc .= '<div class="bib-image-caption">';
            $renderer->doc .= $this->_wiki($item['value'], $item['valueSpoiler']);
            $renderer->doc .= '</div>';
          }
          $renderer->doc .= '</div>' . DOKU_LF;
          break;

        case 'section':
          $renderer->doc .= '<div class="bib-section-header">';
          $renderer->doc .= $this->_wiki($item['key'], $item['keySpoiler']);
          $renderer->doc .= '</div>' . DOKU_LF;
          $renderer->doc .= '<div class="bib-section-body">' . DOKU_LF;
          $sectionOpen = true;
          break;

        case 'collapse':
          $collapseCounter++;
          $cid = 'bib-collapse-' . $collapseCounter . '-' . mt_rand(1000, 9999);
          $renderer->doc .= '<div class="bib-collapse-header" data-bib-target="' . $cid . '">';
          $renderer->doc .= '<span class="bib-collapse-icon"></span>';
          $renderer->doc .= $this->_wiki($item['key'], $item['keySpoiler']);
          $renderer->doc .= '</div>' . DOKU_LF;
          $renderer->doc .= '<div class="bib-section-body bib-collapsed" id="' . $cid . '">' . DOKU_LF;
          $sectionOpen = true;
          break;

        case 'text':
          $renderer->doc .= '<div class="bib-row">';
          $renderer->doc .= '<div class="bib-key">';
          $renderer->doc .= $this->_wiki($item['key'], $item['keySpoiler']);
          $renderer->doc .= '</div>';
          $renderer->doc .= '<div class="bib-value">';
          $renderer->doc .= $this->_wiki($item['value'], $item['valueSpoiler']);
          $renderer->doc .= '</div>';
          $renderer->doc .= '</div>' . DOKU_LF;
          break;

        case 'wide':
          // "wide" puts the content in a full-width cell.
          // Content can come from key or value (whichever is non-empty).
          $content = '' !== $item['key'] ? $item['key'] : $item['value'];
          $spoiler = '' !== $item['key'] ? $item['keySpoiler'] : $item['valueSpoiler'];
          $renderer->doc .= '<div class="bib-row bib-wide">';
          $renderer->doc .= '<div class="bib-value">';
          $renderer->doc .= $this->_wiki($content, $spoiler);
          $renderer->doc .= '</div>';
          $renderer->doc .= '</div>' . DOKU_LF;
          break;

        case 'divider':
          $renderer->doc .= '<div class="bib-divider">';

          if ('' !== $item['key']) {
            $renderer->doc .= '<span>' . $this->_wiki($item['key'], $item['keySpoiler']) . '</span>';
          }

          $renderer->doc .= '</div>' . DOKU_LF;
          break;

        default:
          // Unknown type: silently ignore
          break;
      }
    }

    // Close any trailing open section body
    if ($sectionOpen) {
      $renderer->doc .= '</div>' . DOKU_LF;
    }
  }

  /**
   * Render a group of consecutive "tab" items as a tabbed image panel.
   */
  private function _renderTabGroup($renderer, $tabs, $groupId) {
    $gid = 'bib-tabgroup-' . $groupId . '-' . mt_rand(1000, 9999);

    $renderer->doc .= '<div class="bib-tabs" data-bib-tabgroup="' . $gid . '">' . DOKU_LF;

    // Tab buttons
    $renderer->doc .= '<div class="bib-tab-buttons">';

    foreach ($tabs as $idx => $tab) {
      $label = '' !== $tab['value'] ? hsc($tab['value']) : 'Image ' . ($idx + 1);
      $active = 0 === $idx ? ' bib-tab-active' : '';
      $renderer->doc .= '<button class="bib-tab-btn' . $active . '" data-bib-tab="' . $idx . '">';
      $renderer->doc .= $label;
      $renderer->doc .= '</button>';
    }

    $renderer->doc .= '</div>' . DOKU_LF;

    // Tab panels
    foreach ($tabs as $idx => $tab) {
      $src = $this->_mediaUrl($tab['key']);
      $hidden = 0 === $idx ? '' : ' style="display:none;"';
      $renderer->doc .= '<div class="bib-tab-panel" data-bib-tab-panel="' . $idx . '"' . $hidden . '>';
      $renderer->doc .= '<img src="' . $src . '" alt="" loading="lazy" />';
      $renderer->doc .= '</div>' . DOKU_LF;
    }

    $renderer->doc .= '</div>' . DOKU_LF;
  }

  /**
   * Render a piece of wiki text through DokuWiki's full parser pipeline
   * and return inline HTML (with wrapping <p> stripped).
   * If $spoiler is true, wraps the output in a spoiler span.
   */
  private function _wiki($text, $spoiler = false) {
    $text = trim($text);
    if ('' === $text) {
      return '';
    }

    $info = [];
    $html = p_render('xhtml', p_get_instructions($text), $info);

    // Strip the outermost <p>...</p> wrapper that DokuWiki adds,
    // so the result can be used inline.
    $html = preg_replace('/^\s*<p>\s*/s', '', $html);
    $html = preg_replace('/\s*<\/p>\s*$/s', '', $html);
    $html = trim($html);

    if ($spoiler) {
      $html = '<span class="bib-spoiler">' . $html . '</span>';
    }

    return $html;
  }
}
