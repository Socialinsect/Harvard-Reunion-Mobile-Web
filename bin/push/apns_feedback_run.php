#!/usr/bin/php
<?php

require_once realpath(dirname(__FILE__)).'/../../lib/Kurogo.php';
$Kurogo = Kurogo::sharedInstance();
$Kurogo->initialize();

includePackage('Push');

$server = new ApplePushServerConnection();

$server->openFeedbackConnection();
$messages = $server->getFeedbackMessages();

foreach ($messages as $message) {
    error_log("received deactivation message:\n".print_r($messsage, true));
    $deviceToken = $message['device_token'];
    $unixtime = $message['unixtime'];
    PushClientDevice::deactivateDeviceForToken($deviceToken, 'ios');
}

$server->closeFeedbackConnection();

