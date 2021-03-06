#!/usr/bin/php
<?php

require_once realpath(dirname(__FILE__)).'/../../lib/Kurogo.php';
$Kurogo = Kurogo::sharedInstance();
$Kurogo->initialize();

includePackage('Push');

error_log("looking for messages to send");

$count = PushDB::unsentNotificationCount('ios');
if ($count == 0) {
  error_log("no messages found");
  exit(0);
}

// use at least 1 connection and up to 10 connections
$numConnections = min(max(1, intval($count / 10)), 10);
$server = new ApplePushServerConnectionPool($numConnections);

$messages = PushDB::getUnsentNotifications('ios');

// results are ordered by device id.
// just send the last message and unread count
$previousDeviceId = null;
$previousMessage = null;
$device = null;
$message = null;

$badgeCount = 0;

while ($message = $messages->fetch(PDO::FETCH_ASSOC)) {
  error_log("sending message ".print_r($message, true));

  $deviceId = $message['device_id'];

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

$server->close();

