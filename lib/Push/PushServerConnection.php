<?php

abstract class PushServerConnection
{

  abstract protected function pushURL();
  
  // return boolean indicating success
  abstract public function sendPushNotification(
    PushClientDevice $device,
    $messageData // fetched row from notifications table
    );

}
