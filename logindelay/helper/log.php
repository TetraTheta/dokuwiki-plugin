<?php

class helper_plugin_logindelay_log extends DokuWiki_Plugin {
  protected $statFile;

  public function __construct($user) {
    global $conf;
    $this->statFile = $conf['cachedir'] . '/logindelay_' . $user . '.log';
  }

  public function calculateDelay() {
    if (!is_file($this->statFile)) {
      return 0;
    }

    $strikes = $this->readStrikes();

    if ($strikes < $this->getConf('maxFailures')) {
      return 0;
    }

    $delay = $this->getConf('initialDelay') * pow(2, $strikes - $this->getConf('maxFailures'));
    $remainingDelay = $delay - (time() - filemtime($this->statFile)) / 60;

    return (int) $remainingDelay >= 0 ? ceil($remainingDelay) : 0;
  }

  public function clearFailStrikes() {
    @unlink($this->statFile);
  }

  public function putFailStrike() {
    $strikes = $this->readStrikes() + 1;
    file_put_contents($this->statFile, $strikes);

    return $strikes;
  }

  public function readStrikes() {
    if (!is_file($this->statFile)) {
      return 0;
    }

    $content = file_get_contents($this->statFile);
    if (false === $content) {
      return 0;
    }

    return (int) $content;
  }
}
