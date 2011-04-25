#!/usr/bin/php
<?php

require_once realpath(dirname(__FILE__)).'/../../lib/Kurogo.php';
$Kurogo = Kurogo::sharedInstance();
$Kurogo->initialize('');

includePackage('Push');

define("APNS_PUSH_REST_TIME", 15);

$daemon = new DaemonWrapper("apns_push");
$daemon->start($argv);

$server = new ApplePushServerConnectionPool(APNS_CONNECTIONS_LIMIT);

// this is a daemon so loop forever
d_echo("push daemon activated");

while ($daemon->sleep(APNS_PUSH_REST_TIME)) {
  d_echo("waiting for messages to send...", False);

  $messages = PushDB::getUnsentNotifications('ios');

  // results are ordered by device id.
  // just send the last message and unread count
  $previousDeviceId = null;
  $previousMessage = null;
  $device = null;
  $badgeCount = 0;

  while ($message = $messages->fetch(PDO::FETCH_ASSOC)) {
    $deviceId = $message['device_id'];
    $device = PushClientDevice::factory(
      $deviceId, PushClientDevice::SOURCE_DAEMON, 'ios');

    // TODO this has a bug where if the user has opted out
    // of the last message they won't receive any messages
    if ($deviceId != $previousDeviceId) {
      if ($previousDeviceId) {
        $device = PushClientDevice::factory(
          $previousDeviceId, PushClientDevice::SOURCE_DAEMON, 'ios');
        $server->sendPushNotification($device, $message);
      }

      $badgeCount = 0;
      $previousDeviceId = $deviceId;

    } else if ($previousDeviceId) {
      // don't actually send this message and make sure
      // we don't send it again
      $device = PushClientDevice::factory(
        $deviceId, PushClientDevice::SOURCE_DAEMON, 'ios');
      $device->markNotificationRead($previousMessage['message_id']);
      $badgeCount++;
    }
  
    $previousMessage = $message;

  }

  if ($previousDeviceId) {
    $device = PushClientDevice::factory(
      $previousDeviceId, PushClientDevice::SOURCE_DAEMON, 'ios');
    $server->sendPushNotification($device, $previousMessage, $server, $badgeCount);
  }
}

$server->close();
$daemon->stop();

