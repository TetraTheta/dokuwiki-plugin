<?php

class action_plugin_logindelay_login extends DokuWiki_Action_Plugin {
  public function checkDelay(Doku_Event $event) {
    $user = $event->data['user'];
    if (empty($user)) {
      return;
    }

    $logHelper = new helper_plugin_logindelay_log($user);
    $delay = $logHelper->calculateDelay();
    if ($delay > 0) {
      $event->preventDefault();
      $this->displayMessage($delay);
    }
  }

  public function processLoginAttempt(Doku_Event $event) {
    global $ACT;
    if ('login' !== $ACT) {
      return;
    }

    $authenticatedUser = $_SERVER['REMOTE_USER'] ?? null;
    $loginUser = $event->data['user'];
    $logHelper = new helper_plugin_logindelay_log($loginUser);
    $delay = $logHelper->calculateDelay();
    if ($delay > 0) {
      $this->displayMessage($delay);

      return;
    }

    if ($loginUser && $loginUser !== $authenticatedUser) {
      if ($logHelper->putFailStrike() > $this->getConf('maxFailures')) {
        $delay = $logHelper->calculateDelay();
        $this->displayMessage($delay);
      }

      return;
    }

    $logHelper->clearFailStrikes();
  }

  public function register(Doku_Event_Handler $controller) {
    $controller->register_hook('AUTH_LOGIN_CHECK', 'BEFORE', $this, 'checkDelay');
    $controller->register_hook('AUTH_LOGIN_CHECK', 'AFTER', $this, 'processLoginAttempt');
  }

  protected function displayMessage($allowedRetry) {
    msg(sprintf($this->getLang('errorMessage'), $allowedRetry), -1);
  }
}
