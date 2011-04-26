<?php

class ApplePushClientDevice extends PushClientDevice
{
  protected $platform = 'ios';

  protected function verifyDeviceTokenFormat($deviceToken) {
    preg_match('/^[0-9a-f]{64}$/', $deviceToken, $matches);
    return (sizeof($matches) > 0);
  }
}


