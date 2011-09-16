<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

class SitePushAPIModule extends PushAPIModule {

  private function invalidYear() {
    $title = "invalid year";
    $message = "year param must be specified";
    $error = new KurogoError(400, $title, $message);
    $this->throwError($error);
  }

  public function initializeForCommand() {
  	if ($this->command == 'register') {
      $platform = $this->getArg('platform', null);
      if (!$platform) {
        $this->invalidPlatform();
      }
  
      $device = PushClientDevice::generateNewDevice($platform);
      $token = $this->getArg('device_token', null);
      try {
        $device->registerDeviceToken($token);
      } catch (InvalidDeviceTokenException $e) {
        $this->invalidDeviceToken($e);
      }
       
      $year = $this->getArg('year', null);
      if (!$year) {
        $this->invalidYear();
      }

      $response = $device->getDevicePassKey();
      $this->setResponse($response);
      $this->setResponseVersion(1);

      // automatically subscribe them to messages tagged for their year
      $device->removeAllSubscriptions();
      $device->addSubscription($year);

  	} else {
  		parent::initializeForCommand();
  	}
  }


}


