<?php

// represents a pool of ApplePushServerConnection
// objects.  each connection sends messages to many
// devices in series.  this server object just keeps
// references to several connection objects so we can
// send messages in parallel.

define("APNS_PUSH_TIMEOUT", 90*60);

// wait .5 seconds to determine if the previous payload
// caused an APNs server disconnect.
define("APNS_MINIMUM_WAIT", 0.5);

class ApplePushServerConnectionPool
{
  protected $connections;
  protected $lastConnectionTimes = array();
  protected $poolSize = 0;

  public function __construct($poolSize) {
    for ($cnt = 0; $cnt < $poolSize; $cnt++) {
      $this->connections[] = new ApplePushServerConnection();
      $this->lastConnectionTimes[] = 0;
    }
    $this->poolSize = $poolSize;
  }

  public function sendPushNotification($device, $messageData) {
    $connectionIndex = $this->getNextConnectionIndex();
    $connection = $this->connections[$connectionIndex];
    $lastConnectionTime = $this->lastConnectionTimes[$connectionIndex];

    $connectionAge = microtime(True) - $lastConnectionTime;
    if ($connection->isPushOpen()) {
      if (($connectionAge) > APNS_PUSH_TIMEOUT) {
        // since PHP does not let us implement SO_KEEPALIVE for SSL sockets we timeout the sockets
        $connection->closePushConnection();
      }
    }

    if (!$connection->isPushOpen()) {
      // connecting and disconnecting too rapidly maybe interpreted by apple as
      // a denial of service attack
      sleep(1);  
      $connection->openPushConnection();
    }
     
    // waiting gives apple time to disconnect the connection if the previous
    // message failed, i.e. waiting prevents silent failures
    if ($connectionAge < APNS_MINIMUM_WAIT) {
      usleep(intval(1000000*APNS_MINIMUM_WAIT));
    }

    $connection->sendPushNotification($device, $messageData);
    
    $this->lastConnectionTimes[$connectionIndex] = microtime(True);
  }
  
  private function getNextConnectionIndex() {
    $time = microtime(True);
    $oldest = NULL;
    $oldestIndex = NULL;

    // try to use the oldest connection which has not yet timed out
    // and which is older than the minimum wait time.
    foreach ($this->lastConnectionTimes as $index => $lastTime) {
      $age = $time - $lastTime;
      if ($age > APNS_MINIMUM_WAIT && $age < APNS_PUSH_TIMEOUT) {
	    if (!$oldest || $time < $oldest) {
          $oldest = $time;
          $oldestIndex = $index;
        }
      }
    }

    if ($oldestIndex !== NULL) {
      // found a usable connection
      return $oldestIndex;
    }

    // no reusable connection found just find the oldest one
    $oldest = min($this->lastConnectionTimes);
    return array_search($oldest, $this->lastConnectionTimes);
  }

  public function close() {
    foreach ($this->connections as $connection) {
      if ($connection->isPushOpen()) {
        $connection->closePushConnection();
      }
    }
  }
}

