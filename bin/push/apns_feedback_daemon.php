#!/usr/bin/php
<?php

require_once realpath(dirname(__FILE__)).'/../../lib/Kurogo.php';
$Kurogo = Kurogo::sharedInstance();
$Kurogo->initialize('');

includePackage('Push');

define("APNS_FEEDBACK_REST_TIME", 5*60*60);

$daemon = new DaemonWrapper("apns_feedback");
$daemon->start($argv);

$server = new ApplePushServerConnection();

while ($daemon->sleep(APNS_FEEDBACK_REST_TIME)) {
  // this is a daemon so loop forever
  $server->openFeedbackConnection();
  $messages = $server->getFeedbackMessages();

  foreach($messages as $message) {
    d_echo("received deactivation message:\n".print_r($messsage, true));
    $deviceToken = $message['device_token'];
    $unixtime = $message['unixtime'];
    PushClientDevice::deactivateDeviceForToken($deviceToken, 'ios');
  }
  $server->closeFeedbackConnection();
}

$daemon->stop();


