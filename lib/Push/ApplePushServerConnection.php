<?php

class ApplePushServerConnection extends PushServerConnection {

  protected $certPath;
  protected $certPass;
  protected $context;
 
  protected $pushSocket = NULL;
  protected $feedbackSocket = NULL;
  
  protected $timeout = 60;

  protected function pushURL() {
    $mode = Kurogo::getSiteVar('PUSH_SANDBOX');
    return $mode ? 'ssl://gateway.sandbox.push.apple.com:2195' : 'ssl://gateway.push.apple.com:2195';
  }

  private function feedbackURL() {
    $mode = Kurogo::getSiteVar('PUSH_SANDBOX');
    return $mode ? 'ssl://gateway.sandbox.push.apple.com:2196' : 'ssl://gateway.push.apple.com:2196';
  }
  
  public function __construct() {
    $this->certPath = Kurogo::getSiteVar('APNS_PUSH_CERTIFICATE');
    $this->certPass = Kurogo::getSiteVar('APNS_PUSH_CERTIFICATE_PASS');

    $this->context = stream_context_create();
    stream_context_set_option($this->context, 'ssl', 'local_cert', $this->certPath); 
    stream_context_set_option($this->context, 'ssl', 'passphrase', $this->certPass);  
  }

  public function isPushOpen() {
    return $this->pushSocket !== NULL;
  }

  public function sendPushNotification(
    PushClientDevice $device, $messageData)
  {
    error_log("attempting to send message {$messageData['message']} to device {$messageData['device_id']}");
  
    $token = $device->getDeviceToken();
    if (!$token) {
      error_log("could not send message: invalid device token");
      $device->markNotificationUndeliverable($messageData['message_id']);
      return false;
    }
    
    if (!$device->notificationsEnabledForTag($messageData['tag'])) {
      error_log("user has disabled notifications for this tag:\n"
          .print_r($messageData, true));
      $device->markNotificationUndeliverable($messageData['message_id']);
      return false;
    }
  
    $messageDict = array(
      // standard payload fields
      'aps' => array(
        'alert' => $messageData['message'],
        'badge' => (int)$messageData['badge_value'],
        'sound' => 'default',
        ),
      // custom payload fields
      'tag' => $messageData['tag'],
      );

    $payload = json_encode($messageDict);

error_log(print_r($payload, true));
    
    //  1B command = 0
    //  2B token length = 32
    // 32B token
    //  2B payload length
    // ??B payload
    $message = chr(0) . chr(0) . chr(32) 
      . pack('H*', $device->getDeviceToken()) 
      . chr(0) . chr(strlen($payload)) . $payload;

    // write data to socket
    if (!fwrite($this->pushSocket, $message)) {
      d_error("failed to send payload=$payload");
      $device->markNotificationUndeliverable($message['message_id']);
      return False;
    }
    
    $device->markNotificationSent($messageData['message_id']);
    return True;
  }

  public function openPushConnection() {
    if (!$this->pushSocket) {
      $this->pushSocket = $this->openSSLSocket($this->pushURL());
    }
  }

  public function closePushConnection() {
    if ($this->pushSocket) {
      fclose($this->pushSocket);
      $this->pushSocket = NULL;
    }
  }

  private function openSSLSocket($url) {
    $socket = stream_socket_client(
      $url, $error, $errorString, $this->timeout,
      STREAM_CLIENT_CONNECT, $this->context);

    if (!$socket) {
      throw new Exception("Failed to connect to $url (error $error): $errorString");
    }
    return $socket;
  }
  
  /* apple push feedback server */

  public function openFeedbackConnection() {
    if (!$this->feedbackSocket) {
      $this->feedbackSocket = $this->openSSLSocket($this->feedbackURL());
    }
  }

  public function closeFeedbackConnection() {
    if ($this->feedbackSocket) {
      fclose($this->feedbackSocket);
      $this->feedbackSocket = NULL;
    }
  }

  public function getFeedbackMessages() {
    $feedbackMessages = array();

    while ($raw = fread($this->feedbackSocket, 38)) {
      // 4B timestamp, 2B token length, 32B deviceToken                 
      $arr = unpack("H*", $raw); 
      $rawhex = trim(implode("", $arr));
      $time = hexdec(substr($rawhex, 0, 8)); 
     
      $feedbackMessages[] = array(
        'unixtime' => $time,
        'date' => date('Y-m-d H:i', $time),
        'token_length' => hexdec(substr($rawhex, 8, 4)), 
        'device_token' => substr($rawhex, 12, 64)
      );
    }
    return $feedbackMessages;
  }
}
