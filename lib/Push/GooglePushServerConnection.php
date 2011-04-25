<?php

// http://code.google.com/android/c2dm/index.html#server
class GooglePushServerConnection extends PushServerConnection
{
  // this just sends a HTTP POST request
  public function sendPushNotification(
    PushClientDevice $device, $messageData)
  {
    $collapseKey = 'tag';
    $expireTime = $messageData['expire_time'];
    
    // if message will expire soon, send it immediately
    $delayWhileIdle = time() - $expireTime >= 180;
  
    // determine $authToken via login
  
    return false;
  }

  protected function pushURL()
  {
    return 'https://android.apis.google.com/c2dm/send';
  }


























}

