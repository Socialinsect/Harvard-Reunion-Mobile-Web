<?php

class GooglePushClientDevice extends PushClientDevice
{
  protected $platform = 'android';

  protected function verifyDeviceTokenFormat($deviceToken) {
    return false;
  }

}
