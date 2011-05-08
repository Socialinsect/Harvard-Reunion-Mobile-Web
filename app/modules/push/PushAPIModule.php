<?php

includePackage('Push');

class PushAPIModule extends APIModule {

  protected $id = 'push';

  private function invalidPassKey($e=null) {
    $title = "invalid pass key";
    if ($e) {
      $message = $e->getMessage();
    } else {
      $message = null;
    }
    $error = new KurogoError(401, $title, $message);
    $this->throwError($error);
  }
  
  private function invalidDeviceId() {
    $error = new KurogoError(400, null, "invalid device id");
    $this->throwError($error);
  }
  
  protected function invalidDeviceToken($e=null) {
    $title = "invalid device token";
    if ($e) {
      $message = $e->getMessage();
    } else {
      $message = null;
    }
    $error = new KurogoError(400, $title, $message);
    $this->throwError($error);
  }
  
  private function invalidPlatform() {
    $error = new KurogoError(400, null, "platform not set");
    $this->throwError($error);
  }

  public function initializeForCommand() {

    $platform = $this->getArg('platform', null);
    if (!$platform) {
      $this->invalidPlatform();
    }

    if ($this->command == 'register') {
      $device = PushClientDevice::generateNewDevice($platform);

    } else {
      $deviceId = $this->getArg('device_id', false);
      if (!$deviceId) {
        $this->invalidDeviceId();
      }
    
      $passKey = $this->getArg('pass_key', null);
      if (!$passKey) {
        $this->invalidPassKey();
      }

      try {
        $device = PushClientDevice::factory(
          $deviceId, PushClientDevice::SOURCE_HTTP,
          $platform, $passKey);
      } catch (InvalidPassKeyException $e) {
        $this->invalidPassKey();
      }
    }

    switch ($this->command) {
      case 'register':
        $token = $this->getArg('device_token', null);
        try {
          $device->registerDeviceToken($token);
        } catch (InvalidDeviceTokenException $e) {
          $this->invalidDeviceToken($e);
        }
        $response = $device->getDevicePassKey();
        $this->setResponse($response);
        $this->setResponseVersion(1);
        break;
        
      case 'disable':
        $tag = $this->getArg('tag', null);
        if ($tag) {
          $device->disableNotificationsForTag($tag);
          $this->setResponse(true);
          $this->setResponseVersion(1);
        }
        break;

      case 'enable':
        $tag = $this->getArg('tag', null);
        if ($tag) {
          $device->enableNotificationsForTag($tag);
          $this->setResponse(true);
          $this->setResponseVersion(1);
        }
        break;

      case 'messages':
        $notifications = $device->getUnreadNotifications();
        $this->setResponse($notifications);
        $this->setResponseVersion(1);
        break;

      case 'markasread':
        $tags = $this->getArg('tags', null);
        if ($tags) {
          if (!is_array($tags)) {
            $tags = array($tags);
          }
          $device->markNotificationsRead($tags);
          $this->setResponse(true);
          $this->setResponseVersion(1);
        }
        break;

      case 'subscribe':
        break;

      case 'unsubscribe':
        break;

      case 'updatetoken':
        // if we need to register new device passKeys and tokens
        // separately.  may not be necessary.
        $token = $this->getArg('device_token', null);
        try {
          $device->registerDeviceToken($token);
        } catch (InvalidDeviceTokenException $e) {
          $this->invalidDeviceToken($e);
        }
        $this->setResponse(true);
        $this->setResponseVersion(1);
        break;

      default:
        $this->invalidCommand();
        break;
    }
  }

}

